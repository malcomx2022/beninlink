<?php

namespace Tests\Feature;

use App\Enums\BooleanStatus;
use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\VatStatement;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * La livraison partielle — le client ne prend qu'une partie du colis et ne
 * paie donc pas la somme prévue.
 *
 * C'est la seule étape qui **recalcule les frais** au moment de la livraison :
 * les frais COD suivent la somme réellement encaissée, et la TVA suit les
 * frais. Le montant d'origine est conservé dans `old_cash_collection`, sans
 * quoi on ne saurait plus ce qui avait été convenu.
 *
 * Les mouvements de comptes sont ceux de la livraison complète, sur les
 * nouveaux montants.
 */
class PartialDeliveryAccountingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    /** Ce que le client accepte finalement de payer. */
    private const CASH_REDUIT = 12000.0;

    /** Frais COD recalculés : 1 % de 12 000. */
    private const COD_REDUIT = 120.0;

    /** 120 (COD) + 1 000 (livraison) + 0 (emballage, fragile). */
    private const CHARGES_REDUITES = 1120.0;

    /** 18 % de 1 120, arrondis au franc XOF. */
    private const TVA_REDUITE = 202.0;

    private Merchant $marchand;
    private DeliveryMan $livreur;
    private Parcel $colis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->marchand = Merchant::firstOrFail();
        $this->marchand->current_balance = 0;
        $this->marchand->save();

        $this->actingAs($this->marchand->user->fresh());

        $this->livreur = $this->livreur();
        $this->colis = $this->colisConfie($this->marchand, $this->livreur, 'BL-PARTIEL');
    }

    private function livrerPartiellement(?int $id = null, float $encaisse = self::CASH_REDUIT): bool
    {
        return app(ParcelInterface::class)->parcelPartialDelivered(
            $id ?? $this->colis->id,
            new Request(['cash_collection' => $encaisse]),
        );
    }

    private function colisFrais(): Parcel
    {
        return Parcel::find($this->colis->id);
    }

    // ---- le recalcul ------------------------------------------------------

    /**
     * Les frais COD sont un pourcentage de l'encaissement : ils suivent la
     * somme réellement perçue, pas celle qui était prévue. La TVA suit les
     * frais. Facturer les frais d'origine sur un encaissement réduit ferait
     * payer au marchand une commission sur de l'argent qu'il n'a pas reçu.
     */
    public function test_the_charges_follow_the_amount_actually_collected(): void
    {
        $this->assertTrue($this->livrerPartiellement());

        $colis = $this->colisFrais();
        $this->assertSame(self::COD_REDUIT, (float) $colis->cod_amount);
        $this->assertSame(self::CHARGES_REDUITES, (float) $colis->total_delivery_amount);
        $this->assertSame(self::TVA_REDUITE, (float) $colis->vat_amount);
    }

    /** Le net à reverser : ce qui reste au marchand une fois les frais et la TVA pris. */
    public function test_the_payable_is_the_collected_amount_less_charges_and_vat(): void
    {
        $this->livrerPartiellement();

        $this->assertSame(
            self::CASH_REDUIT - self::CHARGES_REDUITES - self::TVA_REDUITE,
            (float) $this->colisFrais()->current_payable,
        );
    }

    /**
     * Le montant convenu au départ est conservé. Sans lui, plus personne ne
     * sait qu'il y a eu un écart — ni le marchand, ni le litige éventuel.
     */
    public function test_the_original_amount_is_kept(): void
    {
        $this->livrerPartiellement();

        $colis = $this->colisFrais();
        $this->assertSame(self::CASH, (float) $colis->old_cash_collection);
        $this->assertSame(self::CASH_REDUIT, (float) $colis->cash_collection);
        $this->assertSame(BooleanStatus::YES, (int) $colis->partial_delivered);
        $this->assertSame(ParcelStatus::PARTIAL_DELIVERED, (int) $colis->status);
    }

    // ---- les comptes ------------------------------------------------------

    /** Le marchand est crédité du net recalculé, jamais de celui d'origine. */
    public function test_the_merchant_is_credited_with_the_recomputed_payable(): void
    {
        $this->livrerPartiellement();

        $this->assertSame(
            self::CASH_REDUIT - self::CHARGES_REDUITES - self::TVA_REDUITE,
            (float) Merchant::find($this->marchand->id)->current_balance,
        );
    }

    /** Le livreur ne doit que ce qu'il a réellement encaissé. */
    public function test_the_deliveryman_owes_only_what_he_collected(): void
    {
        $this->livrerPartiellement();

        $this->assertSame(
            self::COURSE - self::CASH_REDUIT,
            (float) DeliveryMan::find($this->livreur->id)->current_balance,
        );
    }

    /** La TVA déclarée est celle des frais recalculés. */
    public function test_the_vat_booked_is_the_recomputed_one(): void
    {
        $this->livrerPartiellement();

        $this->assertSame(self::TVA_REDUITE, (float) VatStatement::firstOrFail()->amount);
        $this->assertSame(StatementType::INCOME, (int) VatStatement::firstOrFail()->type);
    }

    // ---- ce qui ne doit pas arriver --------------------------------------

    /**
     * Recommencer une livraison partielle recalculait les frais sur le
     * **nouveau** montant déjà réduit, et re-créditait le marchand : deux
     * appels et les comptes ne veulent plus rien dire.
     */
    public function test_a_second_partial_delivery_changes_nothing(): void
    {
        $this->assertTrue($this->livrerPartiellement());
        $solde = (float) Merchant::find($this->marchand->id)->current_balance;

        $this->assertFalse($this->livrerPartiellement(null, 8000));

        $this->assertSame($solde, (float) Merchant::find($this->marchand->id)->current_balance);
        $this->assertSame(self::CASH_REDUIT, (float) $this->colisFrais()->cash_collection);
        $this->assertSame(3, MerchantStatement::count());
    }

    /** Un colis déjà livré en entier ne peut pas être repassé en partiel. */
    public function test_a_delivered_parcel_cannot_be_downgraded_to_partial(): void
    {
        app(ParcelInterface::class)->parcelDelivered($this->colis->id, new Request());
        $solde = (float) Merchant::find($this->marchand->id)->current_balance;

        $this->assertFalse($this->livrerPartiellement());

        $this->assertSame($solde, (float) Merchant::find($this->marchand->id)->current_balance);
        $this->assertSame(ParcelStatus::DELIVERED, (int) $this->colisFrais()->status);
    }

    /** Et le colis d'une autre société reste hors de portée. */
    public function test_a_parcel_of_another_company_cannot_be_partially_delivered(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete();
        $livreurAilleurs = $this->livreur('9', $ailleurs->company_id);
        $colisAilleurs = $this->colisConfie($ailleurs, $livreurAilleurs, 'BL-AILLEURS', [
            'company_id' => $ailleurs->company_id,
        ]);

        $this->assertFalse($this->livrerPartiellement($colisAilleurs->id));

        $this->assertSame(0.0, (float) Merchant::find($ailleurs->id)->current_balance);
        $this->assertSame(0, MerchantStatement::count());
    }

    /**
     * Comme pour la livraison complète, le refus ne bloque pas le rattrapage :
     * l'annulation repose le colis sur « livreur assigné » et remet
     * `partial_delivered` à non, donc l'étape redevient franchissable.
     */
    public function test_a_cancelled_partial_delivery_can_be_redone(): void
    {
        $this->livrerPartiellement();
        $this->assertTrue(app(ParcelInterface::class)->parcelPartialDeliveredCancel($this->colis->id, new Request()));

        $this->assertTrue($this->livrerPartiellement(null, 9000), 'Une livraison partielle annulee doit pouvoir etre refaite.');
        $this->assertSame(9000.0, (float) $this->colisFrais()->cash_collection);
    }

    /** Les écritures sont atomiques : un colis sans livreur n'en laisse aucune. */
    public function test_a_failure_mid_way_leaves_no_half_written_books(): void
    {
        $orphelin = new Parcel();
        $orphelin->forceFill([
            'company_id' => $this->marchand->company_id,
            'merchant_id' => $this->marchand->id,
            'customer_name' => 'Client',
            'customer_address' => 'Cotonou',
            'cash_collection' => self::CASH,
            'delivery_charge' => 1000,
            'cod_charge' => 1,
            'vat' => 18,
            'liquid_fragile_amount' => 0,
            'packaging_amount' => 0,
            'tracking_id' => 'BL-SANS-LIVREUR',
            'status' => ParcelStatus::RECEIVED_WAREHOUSE,
        ])->save();

        $this->assertFalse($this->livrerPartiellement($orphelin->id));

        $this->assertSame(0.0, (float) Merchant::find($this->marchand->id)->current_balance);
        $this->assertSame(0, MerchantStatement::count());
        $this->assertSame(0, ParcelEvent::where('parcel_id', $orphelin->id)->count());
        // Le colis lui-meme ne garde pas les montants recalcules.
        $this->assertSame(ParcelStatus::RECEIVED_WAREHOUSE, (int) Parcel::find($orphelin->id)->status);
        $this->assertSame(self::CASH, (float) Parcel::find($orphelin->id)->cash_collection);
    }
}
