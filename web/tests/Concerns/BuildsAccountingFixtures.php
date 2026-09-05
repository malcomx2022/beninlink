<?php

namespace Tests\Concerns;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\MerchantShops;
use App\Models\User;

/**
 * Le décor minimal des étapes comptables : un marchand, un livreur, un colis
 * confié, un compte bancaire du transporteur.
 *
 * Les montants sont **choisis pour être vérifiables à la main** plutôt que
 * calculés par le barème : c'est le mouvement des comptes qu'on teste ici, pas
 * la tarification (couverte par `ChargeCalculatorTest`). Un colis à
 * 20 000 F d'encaissement, 1 200 F de frais hors taxe et 216 F de TVA doit
 * laisser 18 584 F au marchand — et si un jour ce n'est plus le cas, le test
 * doit le dire sans qu'on ait à refaire le calcul.
 */
trait BuildsAccountingFixtures
{
    /** Encaissement, frais hors taxe, TVA, net à reverser. */
    protected const CASH = 20000.0;
    protected const CHARGES = 1200.0;
    protected const VAT = 216.0;
    protected const PAYABLE = 18584.0;

    /** Ce que le livreur gagne sur une course. */
    protected const COURSE = 300.0;

    protected function livreur(string $suffixe = '1', ?int $societe = null): DeliveryMan
    {
        $modele = Merchant::firstOrFail()->user;

        $user = $modele->replicate();
        $user->name = 'Livreur ' . $suffixe;
        $user->email = 'livreur-' . $suffixe . '@example.test';
        $user->mobile = '0022997' . str_pad($suffixe, 6, '0', STR_PAD_LEFT);
        $user->unique_id = 'L-' . $suffixe;
        $user->user_type = UserType::DELIVERYMAN;
        $user->company_id = $societe ?? $modele->company_id;
        $user->save();

        return DeliveryMan::forceCreate([
            'company_id' => $societe ?? $modele->company_id,
            'user_id' => $user->id,
            'status' => Status::ACTIVE,
            'delivery_charge' => self::COURSE,
            'pickup_charge' => 0,
            'return_charge' => 0,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);
    }

    /** Un colis prêt à être livré, confié au livreur donné. */
    protected function colisConfie(
        Merchant $marchand,
        DeliveryMan $livreur,
        string $suivi = 'BL-COLIS',
        array $surcharges = [],
    ): Parcel {
        $colis = new Parcel();
        $colis->forceFill(array_merge([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha Kora',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou, Akpakpa',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => self::CASH,
            'delivery_charge' => 1000,
            'cod_charge' => 1,
            'cod_amount' => 200,
            'vat' => 18,
            'vat_amount' => self::VAT,
            'liquid_fragile_amount' => 0,
            'packaging_amount' => 0,
            'total_delivery_amount' => self::CHARGES,
            'current_payable' => self::PAYABLE,
            'tracking_id' => $suivi,
            'status' => ParcelStatus::DELIVERY_MAN_ASSIGN,
        ], $surcharges))->save();

        $this->evenement($colis, $livreur, ParcelStatus::DELIVERY_MAN_ASSIGN);

        return $colis->fresh();
    }

    /** Une étape du cycle de vie : c'est là que la livraison lit son livreur. */
    protected function evenement(Parcel $colis, DeliveryMan $livreur, int $statut): ParcelEvent
    {
        $evenement = new ParcelEvent();
        $evenement->forceFill([
            'parcel_id' => $colis->id,
            'delivery_man_id' => $livreur->id,
            'parcel_status' => $statut,
            'created_by' => auth()->id(),
        ])->save();

        return $evenement;
    }

    /** Le compte bancaire depuis lequel le transporteur règle ses marchands. */
    protected function compteDuTransporteur(int $societe, float $solde = 500000): Account
    {
        return Account::forceCreate([
            'company_id' => $societe,
            'balance' => $solde,
            'account_holder_name' => 'BeninLink',
            'account_no' => 'BJ-0001',
            'opening_balance' => $solde,
            'status' => Status::ACTIVE,
        ]);
    }

    /** Un marchand voisin, dans une autre société : le décor des tests d'isolation. */
    protected function marchandDUneAutreSociete(string $suffixe = 'X'): Merchant
    {
        $modele = Merchant::firstOrFail();

        $societe = new GeneralSettings();
        $societe->forceFill(['name' => 'Autre transporteur ' . $suffixe, 'status' => Status::ACTIVE, 'currency' => 'XOF'])->save();

        $user = $modele->user->replicate();
        $user->email = 'ailleurs-' . $suffixe . '@example.test';
        $user->mobile = '0022995' . str_pad($suffixe === 'X' ? '1' : $suffixe, 6, '0', STR_PAD_LEFT);
        $user->unique_id = 'U-AILLEURS-' . $suffixe;
        $user->company_id = $societe->id;
        $user->save();

        $ailleurs = $modele->replicate();
        $ailleurs->user_id = $user->id;
        $ailleurs->merchant_unique_id = 'M-AILLEURS-' . $suffixe;
        $ailleurs->company_id = $societe->id;
        $ailleurs->current_balance = 0;
        $ailleurs->save();

        return $ailleurs->fresh();
    }
}
