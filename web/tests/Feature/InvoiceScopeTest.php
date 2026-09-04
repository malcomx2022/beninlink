<?php

namespace Tests\Feature;

use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S14 — `GET /invoice-details/{id}` ne doit rendre que les factures du marchand
 * connecté.
 *
 * Le socle faisait `Invoice::find($id)` sans le moindre filtre : changer
 * l'identifiant dans l'URL suffisait à lire la facture d'un concurrent — sa
 * raison sociale, son téléphone, son adresse et ses montants.
 *
 * Le test **exécute l'attaque** : un marchand authentifié demande la facture
 * d'un autre. Il doit obtenir 404, jamais 200.
 */
class InvoiceScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const API_KEY = 'cle-de-test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        config(['rxcourier.api_key' => self::API_KEY]);
    }

    private function invoiceFor(Merchant $merchant, string $reference): Invoice
    {
        $invoice = new Invoice();
        $invoice->forceFill([
            'company_id' => $merchant->company_id,
            'merchant_id' => $merchant->id,
            'invoice_id' => $reference,
            'invoice_date' => now(),
            'total_charge' => 0,
            'cash_collection' => 0,
            'current_payable' => 0,
            'parcels_id' => [],
            'status' => \App\Enums\InvoiceStatus::PAID,
        ])->save();

        return $invoice;
    }

    public function test_un_marchand_lit_sa_propre_facture(): void
    {
        $merchant = Merchant::firstOrFail();
        $invoice = $this->invoiceFor($merchant, 'FAC-SIENNE');

        Sanctum::actingAs($merchant->user, ['merchant']);

        $this->getJson('/api/v10/invoice-details/' . $invoice->id, ['apiKey' => self::API_KEY])
            ->assertOk()
            ->assertJsonPath('data.invoice_id', 'FAC-SIENNE');
    }

    public function test_un_marchand_ne_lit_pas_la_facture_d_un_autre(): void
    {
        $merchant = Merchant::firstOrFail();

        // Second marchand de la même société, avec son propre compte : le
        // scoping par `company_id` seul ne suffirait donc pas à le protéger.
        $otherUser = $merchant->user->replicate();
        $otherUser->email = 'voisin@example.test';
        $otherUser->mobile = '0022997000000';
        $otherUser->unique_id = 'U-VOISIN';
        $otherUser->save();

        $other = $merchant->replicate();
        $other->user_id = $otherUser->id;
        $other->merchant_unique_id = 'M-VOISIN';
        $other->save();

        $invoiceOfOther = $this->invoiceFor($other, 'FAC-VOISIN');

        Sanctum::actingAs($merchant->user, ['merchant']);

        $this->getJson('/api/v10/invoice-details/' . $invoiceOfOther->id, ['apiKey' => self::API_KEY])
            ->assertNotFound();
    }

    public function test_sans_jeton_aucune_facture_n_est_lisible(): void
    {
        $merchant = Merchant::firstOrFail();
        $invoice = $this->invoiceFor($merchant, 'FAC-SANS-JETON');

        $this->getJson('/api/v10/invoice-details/' . $invoice->id, ['apiKey' => self::API_KEY])
            ->assertUnauthorized();
    }
}
