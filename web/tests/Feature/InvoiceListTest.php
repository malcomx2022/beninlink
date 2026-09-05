<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * W4 — la liste des relevés de l'app ne rendait que les relevés déjà réglés.
 *
 * `invoiceLists()` passait **trois** arguments à `where()` :
 * `where('status', InvoiceStatus::PAID, InvoiceStatus::PROCESSING)`. Le
 * deuxième argument étant l'opérateur, Laravel rétablissait silencieusement
 * `where('status', '=', PAID)`. Le filtre `PROCESSING` était perdu, `UNPAID`
 * n'avait jamais été prévu, et il n'y avait aucun tri.
 *
 * Le panneau marchand web sert la même liste par `get()`, tous statuts, du plus
 * récent au plus ancien. L'app consomme désormais la même.
 */
class InvoiceListTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
        $this->merchant = Merchant::firstOrFail();
    }

    private function entetes(): array
    {
        return ['apiKey' => self::API_KEY];
    }

    private function releve(Merchant $merchant, int $statut, string $numero): Invoice
    {
        return Invoice::forceCreate([
            'company_id' => $merchant->company_id,
            'merchant_id' => $merchant->id,
            'invoice_id' => $numero,
            'invoice_date' => now()->toDateString(),
            'total_charge' => 1500,
            'cash_collection' => 50000,
            'current_payable' => 48500,
            'parcels_id' => [],
            'status' => $statut,
        ]);
    }

    private function numerosRendus(): array
    {
        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);

        $reponse = $this->getJson('/api/v10/invoice-list/index', $this->entetes())->assertOk();

        return collect($reponse->json('data'))->pluck('invoice_id')->all();
    }

    /** Le relevé impayé est celui que le marchand attend : il doit être là. */
    public function test_the_list_returns_every_status(): void
    {
        $this->releve($this->merchant, InvoiceStatus::UNPAID, 'INV-IMPAYE');
        $this->releve($this->merchant, InvoiceStatus::PROCESSING, 'INV-EN-COURS');
        $this->releve($this->merchant, InvoiceStatus::PAID, 'INV-PAYE');

        $rendus = $this->numerosRendus();

        $this->assertContains('INV-IMPAYE', $rendus);
        $this->assertContains('INV-EN-COURS', $rendus);
        $this->assertContains('INV-PAYE', $rendus);
        $this->assertCount(3, $rendus);
    }

    public function test_the_most_recent_statement_comes_first(): void
    {
        $this->releve($this->merchant, InvoiceStatus::PAID, 'INV-ANCIEN');
        $this->releve($this->merchant, InvoiceStatus::UNPAID, 'INV-RECENT');

        $this->assertSame(['INV-RECENT', 'INV-ANCIEN'], $this->numerosRendus());
    }

    /** Le cloisonnement par marchand tient : la liste ne montre que la sienne. */
    public function test_another_merchant_statement_is_never_listed(): void
    {
        $this->releve($this->merchant, InvoiceStatus::UNPAID, 'INV-A-MOI');

        $autreUtilisateur = $this->merchant->user->replicate();
        $autreUtilisateur->email = 'voisin-w4@example.test';
        $autreUtilisateur->mobile = '0022997000024';
        $autreUtilisateur->unique_id = 'U-VOISIN-W4';
        $autreUtilisateur->save();

        $voisin = $this->merchant->replicate();
        $voisin->user_id = $autreUtilisateur->id;
        $voisin->merchant_unique_id = 'M-VOISIN-W4';
        $voisin->save();

        $this->releve($voisin, InvoiceStatus::UNPAID, 'INV-AU-VOISIN');

        $rendus = $this->numerosRendus();

        $this->assertSame(['INV-A-MOI'], $rendus);
    }

    /**
     * La liste répondait 500 dès qu'un relevé était à rendre : `InvoiceResource`
     * appelait `->sum()` sur deux propriétés qui n'existent nulle part, donc
     * toujours nulles. L'écran « Factures » ne marchait que vide.
     */
    public function test_the_list_no_longer_fails_on_an_existing_statement(): void
    {
        $releve = $this->releve($this->merchant, InvoiceStatus::UNPAID, 'INV-NET');

        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);
        $reponse = $this->getJson('/api/v10/invoice-list/index', $this->entetes())->assertOk();

        // Le net servi est celui que porte le relevé, celui-là même que le PDF
        // du chantier 4 publie sous « net constaté ».
        $this->assertSame(
            (int) round((float) $releve->current_payable),
            collect($reponse->json('data'))->firstWhere('invoice_id', 'INV-NET')['amount'],
        );
    }

    /** L'app et le panneau web servent exactement la même liste. */
    public function test_the_app_and_the_web_panel_serve_the_same_list(): void
    {
        $this->releve($this->merchant, InvoiceStatus::UNPAID, 'INV-1');
        $this->releve($this->merchant, InvoiceStatus::PROCESSING, 'INV-2');

        Sanctum::actingAs($this->merchant->user->fresh(), ['merchant']);
        $this->actingAs($this->merchant->user->fresh());

        $repo = app(\App\Repositories\Invoice\InvoiceInterface::class);

        $this->assertSame(
            collect($repo->get()->items())->pluck('invoice_id')->all(),
            collect($repo->invoiceLists()->items())->pluck('invoice_id')->all(),
        );
    }
}
