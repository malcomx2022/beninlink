<?php

namespace Tests\Feature;

use App\Enums\BooleanStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ParcelStatus;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use App\Repositories\Invoice\InvoiceInterface;
use App\Services\Invoicing\InvoiceNumbering;
use App\Services\Invoicing\SettlementStatement;
use App\Services\Invoicing\SyscohadaJournal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Chantier 4 — relevés de règlement SYSCOHADA.
 *
 * Couvre, dans l'ordre des consequences du bloc G :
 *   - la numerotation (sequence continue par societe et par exercice) ;
 *   - le net du releve : encaisse COD − frais HT − TVA, egal a ce que la
 *     facture enregistre ;
 *   - l'export journal, equilibre par ecriture ;
 *   - le PDF par lien signe, reserve au marchand proprietaire.
 */
class SettlementStatementTest extends TestCase
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
        // `settings()` resout la societe depuis l'utilisateur connecte : la
        // generation (InvoiceRepository::store) en depend.
        Sanctum::actingAs($this->merchant->user, ['merchant']);
    }

    /** Colis livre : encaisse, sous-total HT des frais, TVA. Le net suit la formule de S2. */
    private function colisLivre(string $tracking, int $collected, int $subtotal, int $vat, int $codFee = 0, int $rate = 18): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client ' . $tracking,
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => $collected,
            'delivery_charge' => $subtotal - $codFee,
            'cod_amount' => $codFee,
            'vat' => $rate,
            'vat_amount' => $vat,
            'total_delivery_amount' => $subtotal,
            'current_payable' => $collected - ($subtotal + $vat),
            'tracking_id' => $tracking,
            'status' => ParcelStatus::DELIVERED,
            'delivery_date' => Carbon::today()->toDateString(),
        ])->save();

        return $parcel;
    }

    private function colisRetourne(string $tracking, int $returnFee): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client ' . $tracking,
            'customer_phone' => '0022996000001',
            'customer_address' => 'Parakou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 8000,
            'return_charges' => $returnFee,
            'tracking_id' => $tracking,
            'status' => ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
            'partial_delivered' => BooleanStatus::NO,
        ])->save();

        return $parcel;
    }

    private function genererReleve(): Invoice
    {
        // 50 000 encaisses, 560 HT (500 livraison + 60 COD), 101 de TVA ; puis
        // 30 000 encaisses, 700 HT, 126 de TVA ; et un retour facture 500.
        $this->colisLivre('BL-1', 50000, 560, 101, 60);
        $this->colisLivre('BL-2', 30000, 700, 126, 300);
        $this->colisRetourne('BL-3', 500);

        $invoice = app(InvoiceInterface::class)->store($this->merchant->id);
        $this->assertInstanceOf(Invoice::class, $invoice, 'la generation doit rendre le releve cree');

        return $invoice->fresh();
    }

    public function test_la_numerotation_est_continue_par_societe_et_par_exercice(): void
    {
        $numbering = app(InvoiceNumbering::class);

        // Societe 2 (prefixe « co » dans le seed), exercice 2026.
        $this->assertSame('CO-2026-000001', $numbering->next(2, Carbon::parse('2026-01-05'))['number']);
        $this->assertSame('CO-2026-000002', $numbering->next(2, Carbon::parse('2026-06-30'))['number']);
        // Nouvel exercice : la sequence repart a 1.
        $this->assertSame('CO-2027-000001', $numbering->next(2, Carbon::parse('2027-01-01'))['number']);
        // Autre societe : sa propre sequence, son propre prefixe.
        $this->assertSame('WE-2026-000001', $numbering->next(1, Carbon::parse('2026-03-01'))['number']);
        // La societe 2 reprend ou elle en etait en 2026.
        $this->assertSame(3, $numbering->next(2, Carbon::parse('2026-12-31'))['sequence']);
    }

    public function test_le_releve_genere_porte_un_numero_conforme_et_le_net_attendu(): void
    {
        $invoice = $this->genererReleve();

        $this->assertMatchesRegularExpression('/^CO-\d{4}-000001$/', $invoice->invoice_id);
        $this->assertSame(Carbon::today()->toDateString(), Carbon::parse($invoice->issued_on)->toDateString());
        $this->assertSame((int) Carbon::today()->format('Y'), (int) $invoice->fiscal_year);
        $this->assertSame(1, (int) $invoice->sequence);
        $this->assertSame(3, $invoice->invoiceParcels()->count());

        $s = SettlementStatement::for($invoice);
        $t = $s['totals'];

        $this->assertSame(80000, $t['collected']);
        // Frais HT = 560 + 700 + 500 (retour) ; TVA = 101 + 126.
        $this->assertSame(1760, $t['fees_ht']);
        $this->assertSame(227, $t['vat']);
        $this->assertSame(1987, $t['fees_ttc']);
        // Net a reverser = encaisse − frais HT − TVA.
        $this->assertSame(80000 - 1760 - 227, $t['net']);
        // ... et c'est exactement ce que la facture enregistre.
        $this->assertSame($t['net'], $t['net_recorded']);
        $this->assertTrue($t['consistent']);

        // Mentions legales des deux parties, lues par identifiant (pas settings()).
        $this->assertSame($this->merchant->business_name, $s['merchant']['name']);
        $this->assertNotSame('', $s['carrier']['name']);
        $this->assertSame(['18'], $s['vat_rates']);
    }

    public function test_une_seconde_generation_le_meme_jour_ne_cree_pas_de_second_releve(): void
    {
        $this->genererReleve();
        $this->colisLivre('BL-4', 10000, 500, 90);

        $this->assertNull(app(InvoiceInterface::class)->store($this->merchant->id));
        $this->assertSame(1, Invoice::count());
        // Le colis non facture attend le prochain releve.
        $this->assertNull(Parcel::where('tracking_id', 'BL-4')->value('invoice_id'));
    }

    public function test_le_journal_syscohada_est_equilibre(): void
    {
        $invoice = $this->genererReleve();

        $lines = SyscohadaJournal::linesFor($invoice);
        // Ventes (3 lignes) + compensation (2) ; pas de reversement tant que non paye.
        $this->assertCount(5, $lines);
        $this->assertSame(array_sum(array_column($lines, 'debit')), array_sum(array_column($lines, 'credit')));
        $this->assertSame('4111', $lines[0]['compte']);
        $this->assertSame(1987, $lines[0]['debit']);
        $this->assertSame('7061', $lines[1]['compte']);
        $this->assertSame(1760, $lines[1]['credit']);
        $this->assertSame('4431', $lines[2]['compte']);
        $this->assertSame(227, $lines[2]['credit']);

        $invoice->status = InvoiceStatus::PAID;
        $invoice->save();

        $lines = SyscohadaJournal::linesFor($invoice->fresh());
        $this->assertCount(7, $lines);
        $this->assertSame(array_sum(array_column($lines, 'debit')), array_sum(array_column($lines, 'credit')));
        $this->assertSame(80000 - 1987, $lines[5]['debit']);

        $csv = SyscohadaJournal::csv([$invoice->fresh()]);
        $this->assertStringStartsWith("\xEF\xBB\xBF" . 'date;journal;piece', $csv);
        $this->assertSame(8, substr_count($csv, "\n")); // en-tete + 7 lignes
    }

    public function test_le_pdf_est_servi_par_lien_signe_au_marchand_proprietaire(): void
    {
        $invoice = $this->genererReleve();
        $entetes = ['apiKey' => self::API_KEY];

        $reponse = $this->getJson('/api/v10/invoice-pdf-link/' . $invoice->id, $entetes)->assertOk();
        $url = $reponse->json('data.url');
        $this->assertStringContainsString('signature=', $url);

        $pdf = $this->get($url);
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        // Sans signature : refuse.
        $this->get('/invoice/statement/' . $invoice->id . '/pdf')->assertStatus(403);

        // Le releve d'un autre marchand est introuvable via l'API.
        $autreUtilisateur = $this->merchant->user->replicate();
        $autreUtilisateur->email = 'voisin@example.test';
        $autreUtilisateur->mobile = '0022997000009';
        $autreUtilisateur->unique_id = 'U-VOISIN';
        $autreUtilisateur->save();
        $voisin = $this->merchant->replicate();
        $voisin->user_id = $autreUtilisateur->id;
        $voisin->merchant_unique_id = 'M-VOISIN';
        $voisin->save();

        // `replicate()` recopie les relations deja chargees : sans `fresh()`,
        // le voisin garderait en memoire le marchand d'origine.
        Sanctum::actingAs($autreUtilisateur->fresh(), ['merchant']);
        $this->getJson('/api/v10/invoice-pdf-link/' . $invoice->id, $entetes)->assertNotFound();
    }
}
