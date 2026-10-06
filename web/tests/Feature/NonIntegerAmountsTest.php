<?php

namespace Tests\Feature;

use App\Console\Commands\NonIntegerAmountsCommand;
use App\Enums\ParcelStatus;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S107 (T2) — le schéma reste en `decimal(…,2)`, la règle « FCFA entiers » se
 * vérifie dans la base : `beninlink:montants-non-entiers` constate, ne corrige
 * rien, sort en succès.
 */
class NonIntegerAmountsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    public function test_une_base_amorcee_ne_porte_aucune_decimale(): void
    {
        $this->artisan('beninlink:montants-non-entiers')
            ->expectsOutputToContain('Aucun montant stocké avec des décimales')
            ->assertExitCode(0);
    }

    public function test_une_decimale_glissee_en_base_est_constatee_sans_etre_corrigee(): void
    {
        $merchant = Merchant::first();
        $colis = Parcel::forceCreate([
            'company_id' => $merchant->company_id, 'merchant_id' => $merchant->id,
            'tracking_id' => 'BL-DEC-1', 'customer_name' => 'Client', 'customer_phone' => '0022997000000',
            'customer_address' => 'Cotonou', 'status' => ParcelStatus::PENDING, 'priority_type_id' => 1,
            'cash_collection' => 10000, 'current_payable' => 9000,
        ]);
        // Une écriture qui contourne ChargeCalculator : directement en base.
        DB::table('parcels')->where('id', $colis->id)->update(['vat_amount' => 302.40]);

        $this->artisan('beninlink:montants-non-entiers', ['--societe' => $merchant->company_id])
            // `expectsOutputToContain` consomme une ligne par attente, dans l'ordre : une attente par ligne.
            ->expectsOutputToContain('parcels.vat_amount : 1 ligne(s) non entière(s)')
            ->expectsOutputToContain('1 valeur(s) non entière(s). Constat seul : rien n’a été écrit.')
            ->assertExitCode(0);

        $this->assertEquals(302.40, (float) DB::table('parcels')->where('id', $colis->id)->value('vat_amount'), 'un constat ne corrige pas');
    }

    public function test_les_colonnes_surveillees_sont_celles_des_releves(): void
    {
        $this->assertArrayHasKey('parcels', NonIntegerAmountsCommand::COLONNES);
        $this->assertContains('current_payable', NonIntegerAmountsCommand::COLONNES['parcels'], 'le net à reverser est la colonne qui finit sur le relevé');
        $this->assertArrayHasKey('invoices', NonIntegerAmountsCommand::COLONNES);
        $this->assertArrayHasKey('merchants', NonIntegerAmountsCommand::COLONNES);
    }
}
