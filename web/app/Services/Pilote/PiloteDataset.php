<?php

namespace App\Services\Pilote;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Packaging;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\Upload;
use App\Models\MerchantShops;
use App\Models\User;
use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Wallet;
use App\Repositories\Wallet\WalletInterface;
use App\Services\Parcel\ChargeCalculator;
use App\Services\Pricing\ZoneGridConverter;
use App\Services\Parcel\WalletDebit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Jeu de données béninois pour les tests pilotes (recette).
 *
 * Enrichit une société EXISTANTE (celle de l'installation de recette) avec
 * des agences, un barème en FCFA, cinq PME pilotes, trois livreurs et une
 * trentaine de colis répartis sur les statuts. Tout ce qui est créé porte un
 * marqueur (`PIL-` / `LIV-` pour les comptes, préfixe de suivi `PIL`) pour
 * pouvoir être retiré ou recréé (`--reset`).
 *
 * Les montants des colis sortent de `ChargeCalculator`, comme en production :
 * le jeu de données ne « triche » pas sur les frais, la TVA ou le net.
 *
 * ⚠️ Recette uniquement : les mots de passe sont connus et identiques.
 */
class PiloteDataset
{
    public const PASSWORD = 'pilote2026';
    public const TRACKING_PREFIX = 'PIL';

    /**
     * PME réglant par **portefeuille prépayé** (PIL-002), et sa recharge d'ouverture.
     *
     * Sans elle, la recette ne pouvait pas exercer tout un pan du produit : le débit
     * à la création, le refus pour solde insuffisant, la recharge Mobile Money — ni
     * la commande `beninlink:colis-non-debites`, qui s'arrêtait sur « aucun marchand
     * ne règle par portefeuille ». Les quatre autres PME restent en règlement à la
     * livraison : les deux modes doivent être testés.
     */
    public const WALLET_MERCHANT = 2;
    public const WALLET_TOPUP = 150000;

    private const HUBS = [
        ['Cotonou — Ganhi', '0022921301001', 'Rue 209, Ganhi, Cotonou'],
        ['Cotonou — Akpakpa', '0022921301002', 'Carrefour Sègbèya, Akpakpa, Cotonou'],
        ['Abomey-Calavi', '0022921301003', 'Carrefour IITA, Abomey-Calavi'],
        ['Porto-Novo', '0022921301004', 'Quartier Ouando, Porto-Novo'],
        ['Parakou', '0022921301005', 'Quartier Zongo, Parakou'],
    ];

    /** Tranches « jusqu'à N kg » : jour même, lendemain, périphérie, intérieur. */
    private const GRID = [
        1 => [1000, 800, 1500, 2500],
        3 => [1500, 1200, 2000, 3500],
        5 => [2000, 1700, 2800, 4500],
        10 => [3000, 2500, 4000, 6500],
    ];

    private const PACKAGING = [['Sachet', 100], ['Carton', 300], ['Carton renforcé', 500]];

    /** PME pilotes : enseigne, gérant·e, téléphone, IFU, RCCM, CNSS, adresse, agence. */
    private const MERCHANTS = [
        ['Maison Kora Cosmétiques', 'Aïcha Kora', '0022997010001', '3202600010001', 'RB/COT/26 A 10001', '', 'Marché Dantokpa, Cotonou', 0],
        ['Bio Fresh Bénin', 'Rodrigue Hounsou', '0022997010002', '3202600010002', 'RB/COT/26 B 10002', 'CNSS-2026-10002', 'Fidjrossè, Cotonou', 1],
        ['Atelier Sênan Mode', 'Sênan Dossou', '0022997010003', '3202600010003', 'RB/CAL/26 A 10003', '', 'Godomey, Abomey-Calavi', 2],
        ['Librairie Chabi', 'Mariam Chabi', '0022997010004', '3202600010004', 'RB/PNO/26 A 10004', '', 'Avenue Jean Bayol, Porto-Novo', 3],
        ['Électro Bio Parakou', 'Fatoumata Bio', '0022997010005', '3202600010005', 'RB/PKU/26 A 10005', 'CNSS-2026-10005', 'Quartier Banikanni, Parakou', 4],
    ];

    private const DELIVERYMEN = [
        ['Kossi Agbodjan', '0022996020001', 0],
        ['Ismaël Yacoubou', '0022996020002', 2],
        ['Bernadette Sossou', '0022996020003', 3],
    ];

    private const CUSTOMERS = [
        ['Adjoua Mensah', '0022995030001', 'Rue 1042, Cadjèhoun, Cotonou'],
        ['Ibrahim Tidjani', '0022995030002', 'Carrefour Vêdoko, Cotonou'],
        ['Reine Gbaguidi', '0022995030003', 'Zogbadjè, Abomey-Calavi'],
        ['Serge Adjovi', '0022995030004', 'Quartier Tokpota, Porto-Novo'],
        ['Nadège Akpo', '0022995030005', 'Quartier Ladji Farani, Parakou'],
        ['Yao Sodji', '0022995030006', 'Haie Vive, Cotonou'],
    ];

    /** Répartition des colis de chaque PME : statut → type de livraison. */
    private const PARCEL_PLAN = [
        [ParcelStatus::PENDING, 1],
        [ParcelStatus::PICKUP_ASSIGN, 2],
        [ParcelStatus::RECEIVED_WAREHOUSE, 3],
        [ParcelStatus::DELIVERY_MAN_ASSIGN, 1],
        [ParcelStatus::DELIVERY_MAN_ASSIGN, 4],
        [ParcelStatus::DELIVERED, 1],
        [ParcelStatus::RETURN_TO_COURIER, 3],
    ];

    /** @return array{company:string, hubs:int, merchants:array<int,array{business:string,driver_id:string,phone:string}>, deliverymen:array<int,array{name:string,driver_id:string}>, parcels:int, password:string} */
    public function seed(int $companyId, bool $reset = false): array
    {
        $company = GeneralSettings::findOrFail($companyId);

        if ($reset) {
            $this->reset($companyId);
        }
        if (Merchant::where('company_id', $companyId)->where('merchant_unique_id', 'PIL-001')->exists()) {
            throw new \RuntimeException('Le jeu pilote existe déjà pour cette société ; relancer avec --reset pour le recréer.');
        }

        return DB::transaction(function () use ($company, $companyId) {
            $upload = Upload::first();
            $hubs = $this->hubs($companyId);
            $category = $this->grid($companyId);
            $packagings = $this->packagings($companyId);

            $merchants = [];
            foreach (self::MERCHANTS as $i => [$business, $owner, $phone, $ifu, $rccm, $cnss, $address, $hubIndex]) {
                $merchants[] = $this->merchant($companyId, $i + 1, $business, $owner, $phone, $ifu, $rccm, $cnss, $address, $hubs[$hubIndex], $upload);
            }

            foreach ($merchants as $merchant) {
                if ((int) $merchant->wallet_use_activation === Status::ACTIVE) {
                    $this->recharge($merchant);
                }
            }

            $deliverymen = [];
            foreach (self::DELIVERYMEN as $i => [$name, $phone, $hubIndex]) {
                $deliverymen[] = $this->deliveryman($companyId, $i + 1, $name, $phone, $hubs[$hubIndex], $upload);
            }

            // D4 — le jeu de recette naît avec ses zones : sans elles, la
            // recette ne peut pas exercer le nouveau barème (zones, supplément
            // de délai, forfaits CEDEAO). Les montants sont ceux du barème
            // hérité, décision du métier du 2026-09-06.
            app(ZoneGridConverter::class)->convert($companyId, ZoneGridConverter::SAME_DAY_SURCHARGE);

            $parcels = 0;
            foreach ($merchants as $mi => $merchant) {
                foreach (self::PARCEL_PLAN as $pi => [$status, $deliveryType]) {
                    $customer = self::CUSTOMERS[($mi + $pi) % count(self::CUSTOMERS)];
                    $deliveryman = $deliverymen[($mi + $pi) % count($deliverymen)];
                    $this->parcel($companyId, $merchant, $category, $packagings, $customer, $status, $deliveryType, $deliveryman, $mi * 10 + $pi + 1);
                    $parcels++;
                }
            }
            Auth::logout();

            return [
                'company' => $company->name,
                'hubs' => count($hubs),
                'merchants' => array_map(fn (Merchant $m) => [
                    'business' => $m->business_name,
                    'driver_id' => $m->merchant_unique_id,
                    'phone' => (string) $m->user->mobile,
                ], $merchants),
                'deliverymen' => array_map(fn (DeliveryMan $d) => [
                    'name' => $d->user->name,
                    'driver_id' => (string) $d->user->unique_id,
                ], $deliverymen),
                'parcels' => $parcels,
                'password' => self::PASSWORD,
            ];
        });
    }

    /** Retire tout ce que `seed()` a créé pour cette société (colis, comptes, barème, agences). */
    public function reset(int $companyId): void
    {
        DB::transaction(function () use ($companyId) {
            $merchantIds = Merchant::where('company_id', $companyId)->where('merchant_unique_id', 'like', 'PIL-%')->pluck('id');
            $parcelIds = Parcel::where('company_id', $companyId)->where('tracking_id', 'like', self::TRACKING_PREFIX . '%')->pluck('id');
            ParcelEvent::whereIn('parcel_id', $parcelIds)->delete();
            Parcel::whereIn('id', $parcelIds)->delete();
            MerchantShops::whereIn('merchant_id', $merchantIds)->delete();
            // Les mouvements de portefeuille (recharge d'ouverture et débits des
            // colis) référencent le marchand : sans cette ligne, `--reset` échoue
            // sur une contrainte de clé étrangère depuis que PIL-002 règle par
            // portefeuille.
            Wallet::whereIn('merchant_id', $merchantIds)->delete();
            MerchantStatement::whereIn('merchant_id', $merchantIds)->delete();
            Merchant::whereIn('id', $merchantIds)->delete();
            $userIds = User::where('company_id', $companyId)->where(function ($q) {
                $q->where('unique_id', 'like', 'PIL-%')->orWhere('unique_id', 'like', 'LIV-%');
            })->pluck('id');
            DeliveryMan::whereIn('user_id', $userIds)->delete();
            User::whereIn('id', $userIds)->delete();
            $categoryIds = Deliverycategory::where('company_id', $companyId)->where('title', 'Colis standard (kg)')->pluck('id');
            DeliveryCharge::whereIn('category_id', $categoryIds)->delete();
            Deliverycategory::whereIn('id', $categoryIds)->delete();
            Packaging::where('company_id', $companyId)->whereIn('name', array_column(self::PACKAGING, 0))->delete();
            Hub::where('company_id', $companyId)->whereIn('name', array_column(self::HUBS, 0))->delete();
            // Les lignes de barème par zone tombent avec leurs zones (clé
            // étrangère `nullOnDelete` : on les retire explicitement d'abord).
            DeliveryCharge::where('company_id', $companyId)->whereNotNull('zone_id')->delete();
            \App\Models\Backend\DeliveryZone::where('company_id', $companyId)->delete();
            \App\Models\Backend\DeliveryDelay::where('company_id', $companyId)->delete();
        });
    }

    /** @return Hub[] */
    private function hubs(int $companyId): array
    {
        $out = [];
        foreach (self::HUBS as [$name, $phone, $address]) {
            // Pas de firstOrNew : company_id n'est pas assignable en masse sur ces modèles.
            $hub = Hub::where('company_id', $companyId)->where('name', $name)->first() ?? new Hub();
            $hub->company_id = $companyId;
            $hub->name = $name;
            $hub->phone = $phone;
            $hub->address = $address;
            $hub->current_balance = 0;
            $hub->status = Status::ACTIVE;
            $hub->save();
            $out[] = $hub;
        }

        return $out;
    }

    private function grid(int $companyId): Deliverycategory
    {
        $category = Deliverycategory::where('company_id', $companyId)->where('title', 'Colis standard (kg)')->first() ?? new Deliverycategory();
        $category->company_id = $companyId;
        $category->title = 'Colis standard (kg)';
        $category->status = Status::ACTIVE;
        $category->position = 1;
        $category->save();

        $position = 1;
        foreach (self::GRID as $weight => [$sameDay, $nextDay, $subCity, $outsideCity]) {
            $row = DeliveryCharge::where('company_id', $companyId)->where('category_id', $category->id)->where('weight', $weight)->first() ?? new DeliveryCharge();
            $row->company_id = $companyId;
            $row->category_id = $category->id;
            $row->weight = $weight;
            $row->same_day = $sameDay;
            $row->next_day = $nextDay;
            $row->sub_city = $subCity;
            $row->outside_city = $outsideCity;
            $row->position = $position++;
            $row->status = Status::ACTIVE;
            $row->save();
        }

        return $category;
    }

    /** @return Packaging[] */
    private function packagings(int $companyId): array
    {
        $out = [];
        foreach (self::PACKAGING as $i => [$name, $price]) {
            $p = Packaging::where('company_id', $companyId)->where('name', $name)->first() ?? new Packaging();
            $p->company_id = $companyId;
            $p->name = $name;
            $p->price = $price;
            $p->position = $i + 1;
            $p->status = Status::ACTIVE;
            $p->save();
            $out[] = $p;
        }

        return $out;
    }

    private function merchant(int $companyId, int $n, string $business, string $owner, string $phone, string $ifu, string $rccm, string $cnss, string $address, Hub $hub, ?Upload $upload): Merchant
    {
        $code = sprintf('PIL-%03d', $n);

        $user = new User();
        $user->company_id = $companyId;
        $user->name = $owner;
        $user->mobile = $phone;
        $user->email = 'pilote' . $n . '@recette.beninlink.app';
        $user->address = $address;
        $user->password = Hash::make(self::PASSWORD);
        $user->user_type = UserType::MERCHANT;
        $user->hub_id = $hub->id;
        $user->image_id = $upload?->id;
        $user->unique_id = $code;
        $user->save();

        $merchant = new Merchant();
        $merchant->company_id = $companyId;
        $merchant->user_id = $user->id;
        $merchant->business_name = $business;
        $merchant->merchant_unique_id = $code;
        $merchant->ifu = $ifu;
        $merchant->rccm = $rccm;
        $merchant->cnss = $cnss ?: null;
        $merchant->current_balance = 0;
        $merchant->opening_balance = 0;
        $merchant->wallet_balance = 0;
        $merchant->wallet_use_activation = $n === self::WALLET_MERCHANT ? Status::ACTIVE : Status::INACTIVE;
        // Taux de TVA propre à 0 : c'est le taux de la société qui s'applique (D1).
        $merchant->vat = 0;
        $merchant->cod_charges = ['inside_city' => '1', 'sub_city' => '2', 'outside_city' => '3'];
        $merchant->nid_id = $upload?->id;
        $merchant->trade_license = $upload?->id;
        $merchant->address = $address;
        $merchant->save();

        $shop = new MerchantShops();
        $shop->merchant_id = $merchant->id;
        $shop->name = $business;
        $shop->contact_no = $phone;
        $shop->address = $address;
        $shop->status = Status::ACTIVE;
        $shop->default_shop = 1;
        $shop->save();

        return $merchant;
    }

    /**
     * Recharge d'ouverture du portefeuille, par le **chemin réel** : un mouvement en
     * attente, puis son approbation. C'est ce que fait le webhook FedaPay ; le solde
     * ne bouge nulle part ailleurs (`WalletRepository::approved()` est le seul point
     * de crédit du socle).
     */
    private function recharge(Merchant $merchant): void
    {
        $wallet = new Wallet();
        $wallet->company_id = $merchant->company_id;
        $wallet->merchant_id = $merchant->id;
        $wallet->user_id = $merchant->user_id;
        $wallet->amount = self::WALLET_TOPUP;
        $wallet->type = WalletType::INCOME;
        $wallet->status = WalletStatus::PENDING;
        $wallet->payment_method = WalletPaymentMethod::OFFLINE;
        $wallet->source = 'Recharge de recette';
        $wallet->transaction_id = 'PIL-RECHARGE-' . $merchant->merchant_unique_id;
        $wallet->save();

        app(WalletInterface::class)->approved($wallet->id);
    }

    private function deliveryman(int $companyId, int $n, string $name, string $phone, Hub $hub, ?Upload $upload): DeliveryMan
    {
        $user = new User();
        $user->company_id = $companyId;
        $user->name = $name;
        $user->mobile = $phone;
        $user->email = 'livreur' . $n . '@recette.beninlink.app';
        $user->address = $hub->address;
        $user->password = Hash::make(self::PASSWORD);
        $user->user_type = UserType::DELIVERYMAN;
        $user->hub_id = $hub->id;
        $user->image_id = $upload?->id;
        $user->unique_id = sprintf('LIV-%03d', $n);
        $user->save();

        $deliveryman = new DeliveryMan();
        $deliveryman->company_id = $companyId;
        $deliveryman->user_id = $user->id;
        $deliveryman->status = Status::ACTIVE;
        $deliveryman->delivery_charge = 500;
        $deliveryman->pickup_charge = 300;
        $deliveryman->return_charge = 200;
        $deliveryman->opening_balance = 0;
        $deliveryman->current_balance = 0;
        $deliveryman->driving_license_image_id = $upload?->id;
        $deliveryman->save();

        return $deliveryman;
    }

    /** @param Packaging[] $packagings */
    private function parcel(int $companyId, Merchant $merchant, Deliverycategory $category, array $packagings, array $customer, int $status, int $deliveryType, DeliveryMan $deliveryman, int $seq): Parcel
    {
        [$name, $phone, $address] = $customer;
        $weight = [1, 2, 3, 5, 8][$seq % 5];
        $cash = [8000, 12500, 25000, 45000, 60000, 3500][$seq % 6];
        $packaging = $packagings[$seq % count($packagings)];

        // Même calcul qu'en production : le marchand connecté résout société,
        // barème et taux de TVA.
        Auth::login($merchant->user);
        $charges = app(ChargeCalculator::class)->calculate($merchant, $deliveryType, $category->id, $weight, (float) $cash, $packaging->id);

        $shop = MerchantShops::where('merchant_id', $merchant->id)->firstOrFail();
        $parcel = new Parcel();
        $parcel->company_id = $companyId;
        $parcel->merchant_id = $merchant->id;
        $parcel->merchant_shop_id = $shop->id;
        $parcel->pickup_address = $shop->address;
        $parcel->pickup_phone = $shop->contact_no;
        $parcel->customer_name = $name;
        $parcel->customer_phone = $phone;
        $parcel->customer_address = $address;
        $parcel->invoice_no = 'FAC-' . (2600 + $seq);
        $parcel->category_id = $category->id;
        $parcel->weight = $weight;
        $parcel->delivery_type_id = $deliveryType;
        $parcel->packaging_id = $packaging->id;
        $parcel->cash_collection = $cash;
        $parcel->selling_price = $cash;
        $parcel->packaging_amount = $charges['packaging_amount'];
        $parcel->liquid_fragile_amount = $charges['liquid_fragile_amount'];
        $parcel->delivery_charge = $charges['delivery_charge'];
        $parcel->cod_charge = $charges['cod_charge'];
        $parcel->cod_amount = $charges['cod_amount'];
        $parcel->vat = $charges['vat'];
        $parcel->vat_amount = $charges['vat_amount'];
        $parcel->total_delivery_amount = $charges['total_delivery_amount'];
        $parcel->current_payable = $charges['current_payable'];
        $parcel->tracking_id = sprintf('%s%s%03d', self::TRACKING_PREFIX, date('ymd'), $seq);
        $parcel->hub_id = $merchant->user->hub_id;
        $parcel->first_hub_id = $merchant->user->hub_id;
        $parcel->pickup_date = now()->toDateString();
        $parcel->delivery_date = now()->addDay()->toDateString();
        $parcel->status = $status;
        $parcel->note = $seq % 3 === 0 ? 'Appeler avant de livrer.' : null;
        $parcel->save();

        // Un marchand au portefeuille est débité à la création (D7) : le jeu de
        // données doit sortir cohérent, sinon `beninlink:colis-non-debites` signale
        // à juste titre des colis jamais facturés.
        if ((int) $merchant->wallet_use_activation === Status::ACTIVE) {
            app(WalletDebit::class)->apply($parcel);
        }

        $this->events($parcel, $status, $deliveryman, $merchant->user);

        return $parcel;
    }

    /** Événements de suivi cohérents avec le statut atteint. */
    private function events(Parcel $parcel, int $status, DeliveryMan $deliveryman, User $author): void
    {
        $path = match ($status) {
            ParcelStatus::PENDING => [],
            ParcelStatus::PICKUP_ASSIGN => [ParcelStatus::PICKUP_ASSIGN],
            ParcelStatus::RECEIVED_WAREHOUSE => [ParcelStatus::PICKUP_ASSIGN, ParcelStatus::RECEIVED_BY_PICKUP_MAN, ParcelStatus::RECEIVED_WAREHOUSE],
            ParcelStatus::DELIVERY_MAN_ASSIGN => [ParcelStatus::PICKUP_ASSIGN, ParcelStatus::RECEIVED_BY_PICKUP_MAN, ParcelStatus::RECEIVED_WAREHOUSE, ParcelStatus::DELIVERY_MAN_ASSIGN],
            ParcelStatus::DELIVERED => [ParcelStatus::PICKUP_ASSIGN, ParcelStatus::RECEIVED_BY_PICKUP_MAN, ParcelStatus::RECEIVED_WAREHOUSE, ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::DELIVERED],
            ParcelStatus::RETURN_TO_COURIER => [ParcelStatus::PICKUP_ASSIGN, ParcelStatus::RECEIVED_BY_PICKUP_MAN, ParcelStatus::RECEIVED_WAREHOUSE, ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::RETURN_TO_COURIER],
            default => [],
        };

        $minutes = count($path) * 90;
        foreach ($path as $step) {
            $event = new ParcelEvent();
            $event->parcel_id = $parcel->id;
            $event->parcel_status = $step;
            $event->hub_id = $parcel->hub_id;
            $event->created_by = $author->id;
            if (in_array($step, [ParcelStatus::PICKUP_ASSIGN, ParcelStatus::RECEIVED_BY_PICKUP_MAN], true)) {
                $event->pickup_man_id = $deliveryman->id;
            }
            if (in_array($step, [ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::DELIVERED, ParcelStatus::RETURN_TO_COURIER], true)) {
                $event->delivery_man_id = $deliveryman->id;
            }
            $event->created_at = now()->subMinutes($minutes);
            $event->updated_at = $event->created_at;
            $event->save();
            $minutes -= 90;
        }
    }
}
