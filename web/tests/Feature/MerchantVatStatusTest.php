<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\VatStatus;
use App\Http\Requests\Merchant\StoreRequest;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Repositories\Invoice\InvoiceInterface;
use App\Services\Invoicing\SettlementPdf;
use App\Services\Invoicing\SettlementStatement;
use App\Services\Parcel\VatRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **R7 b (S75)** — exonéré ≠ non renseigné.
 *
 * Un `0` dans `merchants.vat` voulait dire « pas saisi » ; un marchand exonéré
 * ne pouvait pas le dire, et un `0` oublié se lisait comme un `0` voulu sur la
 * facture — l'ambiguïté fiscale que le porteur a nommée. Le statut explicite
 * (`unset` / `taxable` / `exempt`) la ferme, et ce fichier **fige** la
 * distinction : aucune ligne existante n'est reclassée, et l'exonération se
 * voit partout où la TVA s'imprime.
 */
class MerchantVatStatusTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $marchand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->marchand = Merchant::firstOrFail();
        $this->marchand->payment_period = 0;
        $this->marchand->save();
        $this->actingAs($this->marchand->user->fresh());
    }

    private function statut(string $statut, float $taux = 0): Merchant
    {
        $this->marchand->vat_status = $statut;
        $this->marchand->vat = $taux;
        $this->marchand->save();

        return $this->marchand->fresh();
    }

    // ---- la distinction ---------------------------------------------------------

    public function test_non_renseigne_prend_le_taux_de_la_societe_et_exonere_prend_zero(): void
    {
        $this->assertSame(18.0, VatRate::for($this->statut(VatStatus::UNSET, 0)), 'un 0 non renseigné = le taux société (D1)');
        $this->assertSame(0.0, VatRate::for($this->statut(VatStatus::EXEMPT, 0)), 'exonéré = aucune TVA');
        $this->assertSame(0.0, VatRate::for($this->statut(VatStatus::EXEMPT, 18)), 'exonéré même si un taux traîne : le statut gagne');
        $this->assertSame(5.0, VatRate::for($this->statut(VatStatus::TAXABLE, 5)), 'taux propre');
    }

    /** Aucune reclassification rétroactive : une ligne d'avant garde exactement son comportement. */
    public function test_une_ligne_ancienne_garde_son_comportement(): void
    {
        // Avant R7 b, `vat = 5` sans statut appliquait 5 % : c'est toujours vrai.
        $this->assertSame(5.0, VatRate::for($this->statut(VatStatus::UNSET, 5)));
        $this->assertFalse(VatRate::estExonere($this->statut(VatStatus::UNSET, 0)), 'un 0 non renseigné n’est JAMAIS lu comme exonéré');

        // Et la migration ne pose `taxable` que là où un taux était saisi — jamais `exempt`.
        $migration = file_get_contents(base_path('database/migrations/2026_10_03_120000_add_vat_status_to_merchants.php'));
        $this->assertStringContainsString("where('vat', '>', 0)->update(['vat_status' => VatStatus::TAXABLE])", $migration);
        $this->assertStringNotContainsString('VatStatus::EXEMPT', $migration, 'personne ne devient exonéré par migration');
        $this->assertSame(VatStatus::UNSET, VatRate::statut(Merchant::firstOrFail()->fresh()->forceFill(['vat_status' => null])), 'une valeur absente se lit « non renseigné »');
        $this->assertSame(VatStatus::UNSET, VatRate::statut((new Merchant())->forceFill(['vat_status' => 'n-importe-quoi'])));
    }

    public function test_le_formulaire_refuse_un_statut_inconnu(): void
    {
        $regles = ['vat_status' => (new StoreRequest())->rules()['vat_status']];

        $this->assertTrue(Validator::make(['vat_status' => 'exonere'], $regles)->fails());
        foreach (VatStatus::TOUS as $statut) {
            $this->assertFalse(Validator::make(['vat_status' => $statut], $regles)->fails(), $statut);
        }
    }

    // ---- visible partout où la TVA s'imprime -------------------------------------

    private function releveDuMarchand(): \App\Models\Backend\Merchantpanel\Invoice
    {
        $taux = VatRate::for($this->marchand->fresh());
        $colis = new Parcel();
        $colis->forceFill([
            'company_id' => $this->marchand->company_id, 'merchant_id' => $this->marchand->id,
            'customer_name' => 'Client', 'customer_address' => 'Cotonou', 'category_id' => 1, 'delivery_type_id' => 1,
            'cash_collection' => 20000, 'delivery_charge' => 1000, 'cod_amount' => 0,
            'vat' => $taux, 'vat_amount' => (int) round(1000 * $taux / 100), 'total_delivery_amount' => 1000,
            'current_payable' => 20000 - 1000 - (int) round(1000 * $taux / 100),
            'tracking_id' => 'BL-TVA-' . $this->marchand->vat_status, 'status' => ParcelStatus::DELIVERED,
        ])->save();

        $releve = app(InvoiceInterface::class)->store($this->marchand->id);
        $this->assertNotEmpty($releve);

        return $releve->fresh();
    }

    public function test_le_releve_d_un_marchand_exonere_le_dit_en_toutes_lettres(): void
    {
        $this->statut(VatStatus::EXEMPT);
        $statement = SettlementStatement::for($this->releveDuMarchand());

        $this->assertTrue($statement['merchant']['vat_exempt']);
        $this->assertSame(VatStatus::EXEMPT, $statement['merchant']['vat_status']);
        $this->assertSame(0, $statement['totals']['vat']);

        $html = view(SettlementPdf::VUE, ['statement' => $statement])->render();
        $this->assertStringContainsString(e(__('statement.vat_exempt')), $html);
        $this->assertStringNotContainsString(__('statement.vat_none'), $html, 'exonéré n’est pas « aucune TVA » : les deux mentions ne se confondent pas');
    }

    public function test_le_releve_d_un_marchand_non_renseigne_ne_parle_pas_d_exoneration(): void
    {
        $this->statut(VatStatus::UNSET);
        $statement = SettlementStatement::for($this->releveDuMarchand());

        $this->assertFalse($statement['merchant']['vat_exempt']);
        $this->assertSame(['18'], $statement['vat_rates']);
        $this->assertStringNotContainsString(e(__('statement.vat_exempt')), view(SettlementPdf::VUE, ['statement' => $statement])->render());
    }
}
