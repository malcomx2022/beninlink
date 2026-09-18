<?php

namespace Tests\Feature;

use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use App\Services\Invoicing\SettlementStatement;
use App\Services\Parcel\ChargeCalculator;
use App\Models\Backend\DeliveryZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Le FCFA n'a pas de subdivision — mais un pourcentage d'un entier n'est pas
 * entier. 18 % de 1 680 F font 302,40 F.
 *
 * Avant le 2026-09-18, la décimale survivait : elle passait de `vat_amount` à
 * `current_payable`, puis aux colonnes du relevé. Or le relevé **imprimé**
 * arrondit chaque ligne (`SettlementStatement::int()`) : le document et la base
 * ne disaient donc pas le même montant. Mesuré alors sur le jeu pilote,
 * 12 colis sur 35 portaient une TVA non entière, et 2 relevés sur 5 différaient
 * de leur propre ligne de 0,40 F — la forme exacte de l'anomalie à 14,40 F
 * trouvée sur D9.
 *
 * L'arrondi vit désormais dans `ChargeCalculator::percentage()`, le seul endroit
 * où un taux devient des francs. Ces tests le tiennent, et surtout tiennent la
 * conséquence : **le relevé et sa ligne en base annoncent le même net.**
 */
class VatRoundingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $marchand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->marchand = Merchant::firstOrFail();
        // Depuis l'étape 6, le devis passe par une zone, et `DeliveryZone` est
        // scopée par `settings()` : sans locataire courant, aucune zone n'est
        // trouvée et le calcul refuse — à raison.
        Sanctum::actingAs($this->marchand->user, ['merchant']);
    }

    /** Un devis dont la TVA tomberait sur des centimes sans l'arrondi. */
    private function devis(float $encaissement): array
    {
        $zone = DeliveryZone::where('company_id', $this->marchand->company_id)
            ->where('code', DeliveryZone::COTONOU)->firstOrFail();

        return app(ChargeCalculator::class)->calculate(
            $this->marchand->fresh(), 1, 1, $encaissement, null, false, $zone->id,
        );
    }

    public function test_every_amount_the_calculator_returns_is_a_whole_franc(): void
    {
        foreach ([10000, 17350, 23456, 60000, 1] as $encaissement) {
            $devis = $this->devis((float) $encaissement);

            foreach (['cod_amount', 'vat_amount', 'total_delivery_amount', 'current_payable'] as $champ) {
                $this->assertSame(
                    (float) (int) $devis[$champ],
                    (float) $devis[$champ],
                    "« $champ » porte des centimes pour un encaissement de $encaissement F",
                );
            }
        }
    }

    /**
     * L'invariant qui compte : ce que le relevé annonce au marchand est ce que
     * sa ligne en base porte. C'est cette assertion qui rougit si l'arrondi
     * repart de `percentage()`.
     */
    public function test_the_statement_and_its_stored_row_announce_the_same_net(): void
    {
        $devis = $this->devis(8000);
        $this->assertGreaterThan(0, $devis['vat_amount'], 'le cas doit produire une TVA');

        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->marchand->company_id,
            'merchant_id' => $this->marchand->id,
            'merchant_shop_id' => \App\Models\MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha K.',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 8000,
            'total_delivery_amount' => $devis['total_delivery_amount'],
            'vat_amount' => $devis['vat_amount'],
            'cod_amount' => $devis['cod_amount'],
            'current_payable' => $devis['current_payable'],
            'status' => \App\Enums\ParcelStatus::DELIVERED,
            'tracking_id' => 'BL-TVA-1',
            'delivery_date' => now()->subDay(),
        ]);
        $parcel->save();

        $this->artisan('invoice:generate')->assertSuccessful();

        $invoice = Invoice::where('merchant_id', $this->marchand->id)->firstOrFail();
        $totaux = SettlementStatement::for($invoice)['totals'];

        $this->assertSame(
            (int) round((float) $invoice->total_charge),
            $totaux['fees_ttc'],
            'les frais du relevé doivent égaler ceux de sa ligne en base',
        );
        $this->assertSame(
            (int) round((float) $invoice->current_payable),
            $totaux['net'],
            'le net du relevé doit égaler celui de sa ligne en base',
        );

        // …et la ligne en base ne porte elle-même aucun centime.
        $this->assertSame((float) (int) $invoice->total_charge, (float) $invoice->total_charge);
        $this->assertSame((float) (int) $invoice->current_payable, (float) $invoice->current_payable);
    }
}
