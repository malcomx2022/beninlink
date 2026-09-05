<?php

namespace App\Imports;

use App\Enums\ParcelStatus;
use App\Enums\DeliveryType;
use App\Enums\DeliveryTime;
use App\Enums\Status;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

use Illuminate\Support\Str;


/**
 * Import Excel de colis, depuis le back-office ou depuis le panneau marchand.
 *
 * Ce fichier portait cinq defauts, tous constates avant correction (§15 de
 * `docs/REVUE_FEDAPAY_ET_WORKFLOWS.md`) :
 *
 * 1. **Aucun debit du portefeuille.** Pour un marchand au portefeuille, le
 *    debit a la creation *est* la facturation : un import de deux cents colis
 *    n'etait facture nulle part. Le meme fichier saisi ligne a ligne dans le
 *    formulaire, lui, debitait.
 * 2. **Aucun `company_id`** sur les colis crees — le champ n'etait meme pas
 *    assignable sur le modele. Les colis importes etaient donc invisibles a
 *    tous les ecrans `companywise()` du back-office : le transporteur ne les
 *    voyait pas pour les affecter a un ramassage.
 * 3. **Le marchand venait de la colonne `merchant_id` du fichier**, sans aucun
 *    controle. Un marchand qui ecrivait l'identifiant d'un autre dans son
 *    tableur creait des colis au compte de cet autre — et, une fois le debit
 *    branche, aurait vide son portefeuille.
 * 4. **Un bareme a lui.** L'import recalculait frais COD et TVA de son cote,
 *    au lieu du `ChargeCalculator` unique impose par S2 : le meme colis
 *    n'etait pas facture au meme prix selon qu'on l'importait ou qu'on le
 *    saisissait. C'est le montant importe qui est desormais debite.
 * 5. **Une colonne en moins et il mourait.** Chaque colonne etait lue crument
 *    (`$row['pickup_lat']`), y compris les facultatives : retirer du fichier
 *    une colonne dont on n'a pas l'usage levait une `ErrorException` au lieu
 *    d'un message utilisable. Les deux fichiers modeles distribues
 *    (`public/sample-parcel/`) les portent toutes, et n'ont pas les memes :
 *    celui du panneau marchand n'a ni `shop_id`, ni `merchant_id`.
 *
 * La regle d'appartenance est desormais explicite et suit celle du reste du
 * socle : un marchand n'importe que **pour lui-meme** ; le back-office importe
 * pour un marchand **de sa societe**, et seulement pour lui.
 *
 * Tout ou rien : Laravel Excel enveloppe deja l'import dans une transaction
 * (`config('excel.transactions.handler')`), donc un refus de solde a la
 * ligne 137 annule les 136 precedentes. C'est le comportement d'origine pour
 * les erreurs de validation, et c'est le bon : un import a moitie passe est
 * indiscernable d'un import complet, et le marchand qui relance son fichier
 * pour « finir » duplique ce qui etait deja entre — rien dans le fichier ne
 * permet de dedoublonner.
 */
class ParcelImport implements ToModel, WithHeadingRow ,WithValidation , SkipsEmptyRows
{
    use Importable;

    /**
     * Ligne de donnees en cours, pour situer un refus dans le fichier.
     * `ToModel` ne transmet pas le numero de ligne ; on le compte.
     */
    private int $ligne = 0;

    /**
     * Ligne de la feuille, l'en-tete compte pour 1. Approximative si le fichier
     * contient des lignes vides : `SkipsEmptyRows` ne les fait pas passer par
     * `model()`. Elle situe le refus, elle ne le prouve pas.
     */
    public function ligneCourante(): int
    {
        return $this->ligne + 1;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */

     public function RandomTrackingID(){
        return Str::upper(settings()->par_track_prefix).random_int(11111111,99999999);  
    }

    public function model(array $row)
    {
        $this->ligne++;

        // Le marchand n'est PLUS lu dans le fichier quand c'est un marchand qui
        // importe : il n'importe que pour lui-meme. Le back-office, lui, choisit
        // un marchand — mais `companywise()`, donc seulement chez lui.
        $merchant = $this->resoudreMarchand($row);

        if(auth()->user()->merchant):
            $category_id      = 1;
            $delivery_type_id = 2;
            $liquid_fragile   = null;
            $packaging_id     = null;
            $merchantshop     = MerchantShops::where(['merchant_id'=>$merchant->id,'default_shop'=>Status::ACTIVE])->first();
            $shop_id          = $merchantshop->id;
            $pickup_phone     = $merchantshop->contact_no;
            $pickup_address   = $merchantshop->address;
            $pickup_lat       = $merchantshop->merchant_lat;
            $pickup_long      = $merchantshop->merchant_long;
        else:
            $category_id      = $row['category_id'];
            $delivery_type_id = $row['delivery_type_id'];
            $liquid_fragile   = $row['liquid_fragile'] ?? null;
            $packaging_id     = $row['packaging_id'] ?? null;
            $shop_id          = $row['shop_id'];
            $pickup_phone     = $row['pickup_phone'] ?? null;
            $pickup_address   = $row['pickup_address'] ?? null;

            // Facultatives : le modele les porte, mais un fichier qui les a
            // retirees ne doit pas tuer l'import sur une `ErrorException`.
            $pickup_lat       = $row['pickup_lat'] ?? null;
            $pickup_long      = $row['pickup_long'] ?? null;

        endif;

        // S2 — les montants viennent du calculateur unique, comme partout
        // ailleurs depuis 2026-08-18. L'import gardait son propre bareme : le
        // meme colis n'etait pas facture au meme prix importe ou saisi, et
        // c'est le montant de l'import qui sera debite.
        $charges = app(\App\Services\Parcel\ChargeCalculator::class)->calculate(
            $merchant,
            (int) $delivery_type_id,
            blank($category_id) ? null : (int) $category_id,
            $row['weight'] ?? null,
            (float) ($row['cash_collection'] ?? 0),
            blank($packaging_id) ? null : (int) $packaging_id,
            (bool) $liquid_fragile
        );

        $deliveryChargeAmount = $charges['delivery_charge'];
        $liquidFragileAmount  = $charges['liquid_fragile_amount'];
        $packagingAmount      = $charges['packaging_amount'];
        $codAmount            = $charges['cod_amount'];
        $merchantCodCharge    = $charges['cod_charge'];
        $vat                  = $charges['vat'];
        $totalParcelAmount    = $charges['total_delivery_amount'];
        $vatTextAmount        = $charges['vat_amount'];
        $totalCurrentAmount   = $charges['current_payable'];

        $deliveryTime = [
            'pickup'       =>date('Y-m-d'),
            'delivery'     =>date('Y-m-d'),
        ];

        // Pickup & Delivery Time
        if($delivery_type_id == DeliveryType::SAMEDAY){
            if(date('H') < DeliveryTime::LAST_TIME){
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d'),
                    'delivery'     =>date('Y-m-d'),
                ];
            }
            else{
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                ];
            }
        }
        elseif($delivery_type_id == DeliveryType::NEXTDAY){
            if(date('H') < DeliveryTime::LAST_TIME){
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d'),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                ];
            }
            else{
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +2 day')),
                ];
            }
        }
        elseif($delivery_type_id == DeliveryType::SUBCITY){
            if(date('H') < DeliveryTime::LAST_TIME){
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d'),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::SUBCITY .' day')),
                ];
            }
            else{
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::SUBCITY + 1 .' day')),
                ];
            }
        }
        elseif($delivery_type_id == DeliveryType::OUTSIDECITY){
            if(date('H') < DeliveryTime::LAST_TIME){
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d'),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::OUTSIDECITY .' day')),
                ];
            }
            else{
                $deliveryTime = [
                    'pickup'       =>date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day')),
                    'delivery'     =>date('Y-m-d', strtotime(date('Y-m-d') . ' +'. DeliveryTime::OUTSIDECITY + 1 .' day')),
                ];
            }
        }
        // End Pickup & Delivery Time

        $n = (int)floor(microtime(true) * 1000) % 1000000000;
        $parcels = [
            // Sans societe, le colis importe etait invisible a tous les ecrans
            // `companywise()` : cree, facture a personne, et introuvable.
            'company_id'        => $merchant->company_id,
            'merchant_id'       => $merchant->id,
            'first_hub_id'      => $merchant->user->hub_id,
            'hub_id'            => $merchant->user->hub_id,
            'category_id'       => $category_id,
            // `?? null` sur chaque colonne FACULTATIVE. Seules
            // `cash_collection`, `customer_name` et `customer_address` sont
            // exigees par `rules()` : les autres peuvent manquer du fichier
            // sans que l'import ait a mourir.
            'weight'            => $row['weight'] ?? null,
            'invoice_no'        => $row['invoice_no'] ?? null,
            'cash_collection'   => $row['cash_collection'],
            'selling_price'     => $row['selling_price'] ?? null,
            'merchant_shop_id'  => $shop_id,
            'pickup_phone'      => $pickup_phone,
            'pickup_address'    => $pickup_address,
            'pickup_lat'        => $pickup_lat,
            'pickup_long'       => $pickup_long,
            'customer_name'     => $row['customer_name'],
            'customer_phone'    => $row['customer_phone'] ?? null,
            'customer_address'  => $row['customer_address'],
            'customer_lat'      => $row['customer_lat'] ?? null,
            'customer_long'     => $row['customer_long'] ?? null,
            'delivery_type_id'  => $delivery_type_id,
            'pickup_date'       => $deliveryTime['pickup'],
            'delivery_date'     => $deliveryTime['delivery'],
            'vat'               => $vat,
            'vat_amount'        => $vatTextAmount,
            'delivery_charge'   => $deliveryChargeAmount,
            'cod_charge'        => $merchantCodCharge,
            'cod_amount'        => $codAmount,
            'total_delivery_amount'=> $totalParcelAmount,
            'current_payable'   => $totalCurrentAmount,
            'tracking_id'       =>  $this->RandomTrackingID(),
            'note'              => $row['note'] ?? null,
            'packaging_id'      => $packaging_id,
            'packaging_amount'  => $packagingAmount,
            'liquid_fragile_amount' => $liquidFragileAmount,
            'status'            => ParcelStatus::PENDING,
            'created_at'        =>date('Y-m-d H:i:s'),
            'updated_at'        =>date('Y-m-d H:i:s'),
        ];
        $parcel = Parcel::create($parcels);

        // Meme debit, meme plancher de solde que la creation unitaire : c'est
        // la meme facturation. Un refus remonte et annule tout le fichier.
        app(\App\Services\Parcel\WalletDebit::class)->apply($parcel);

        return $parcel;
    }

    /**
     * Le marchand au compte duquel la ligne sera creee.
     *
     * Un marchand connecte : lui-meme, toujours. La colonne `merchant_id` du
     * fichier est ignoree — c'etait la faille.
     * Le back-office : le marchand designe, deja valide `companywise()` par
     * `rules()`, donc present et de la societe courante quand on arrive ici.
     */
    private function resoudreMarchand(array $row): Merchant
    {
        $propre = auth()->user()->merchant;
        if($propre){
            return $propre;
        }

        return Merchant::companywise()->findOrFail($row['merchant_id']);
    }

    public function rules(): array
    {
        if(auth()->user()->merchant):
            $shop_id           = ['numeric'];
            $category_id       = ['numeric'];
            $delivery_type_id  = ['numeric'];
            // Un marchand n'importe que pour lui : la colonne est ignoree,
            // inutile de la contraindre.
            $merchant_id       = ['nullable'];
        else:
            $shop_id           = ['required','numeric'];
            $category_id       = ['required','numeric'];
            $delivery_type_id  = ['required','numeric'];
            // Le back-office designe un marchand — de SA societe. Sans cette
            // regle, `model()` fatalait sur un identifiant absent, et acceptait
            // celui d'un autre transporteur.
            $merchant_id       = ['required','numeric', Rule::exists('merchants','id')->where('company_id', settings()->id)];
        endif;
        return [
            'merchant_id'       => $merchant_id,
            'shop_id'           => $shop_id,
            'cash_collection'   => ['required','numeric'],
            'category_id'       => $category_id,
            'delivery_type_id'  => $delivery_type_id,
            'customer_name'     => ['required','string','max:191'],
            'customer_address'  => ['required','string','max:191'],
        ];
    }


}
