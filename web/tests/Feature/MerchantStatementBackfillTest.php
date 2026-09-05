<?php

namespace Tests\Feature;

use App\Enums\StatementType;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Le rattachement des lignes de relevé écrites sans marchand.
 *
 * Les treize écritures du cycle de vie d'un colis ne renseignaient pas
 * `merchant_id`, la colonne que lit l'écran « Mes relevés ». Le correctif du
 * code arrête l'hémorragie ; la migration rattrape ce qui est déjà en base.
 *
 * Le rattachement est **sans ambiguïté** : chaque ligne porte son `parcel_id`,
 * et un colis appartient à un marchand et un seul. C'est ce qui le distingue
 * des lignes de `settings` orphelines, qu'on a délibérément laissées telles
 * quelles faute de pouvoir les attribuer (**D-A**).
 */
class MerchantStatementBackfillTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
    }

    private function migration()
    {
        return require database_path('migrations/2026_09_05_140000_backfill_merchant_statement_merchant_id.php');
    }

    private function colis(Merchant $marchand, string $suivi): Parcel
    {
        $colis = new Parcel();
        $colis->forceFill([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Client',
            'customer_address' => 'Cotonou',
            'cash_collection' => 20000,
            'tracking_id' => $suivi,
        ])->save();

        return $colis->fresh();
    }

    /** Une ligne orpheline rattachée à un colis retrouve son marchand. */
    public function test_it_attaches_an_orphan_line_to_the_parcels_merchant(): void
    {
        $marchand = Merchant::firstOrFail();
        $colis = $this->colis($marchand, 'BL-1');

        $ligne = new MerchantStatement();
        $ligne->forceFill([
            'company_id' => $marchand->company_id,
            'parcel_id' => $colis->id,
            'type' => StatementType::INCOME,
            'amount' => 20000,
            'date' => date('Y-m-d'),
        ])->save();

        $this->migration()->up();

        $this->assertSame($marchand->id, (int) MerchantStatement::find($ligne->id)->merchant_id);
    }

    /** Une ligne déjà rattachée n'est pas retouchée. */
    public function test_it_leaves_an_already_attached_line_alone(): void
    {
        $marchand = Merchant::firstOrFail();
        $colis = $this->colis($marchand, 'BL-2');

        $ligne = new MerchantStatement();
        $ligne->forceFill([
            'company_id' => $marchand->company_id,
            'merchant_id' => $marchand->id,
            'parcel_id' => $colis->id,
            'type' => StatementType::EXPENSE,
            'amount' => 1200,
            'date' => date('Y-m-d'),
        ])->save();

        $modifiee = MerchantStatement::find($ligne->id)->updated_at;
        $this->migration()->up();

        $this->assertEquals($modifiee, MerchantStatement::find($ligne->id)->updated_at);
    }

    /**
     * Une ligne sans colis reste orpheline : rien ne dit à qui elle appartient,
     * et on ne devine pas. C'est le cas des ajustements saisis à la main sans
     * marchand.
     */
    public function test_a_line_without_a_parcel_stays_orphan(): void
    {
        $marchand = Merchant::firstOrFail();

        $ligne = new MerchantStatement();
        $ligne->forceFill([
            'company_id' => $marchand->company_id,
            'type' => StatementType::EXPENSE,
            'amount' => 500,
            'date' => date('Y-m-d'),
        ])->save();

        $this->migration()->up();

        $this->assertNull(MerchantStatement::find($ligne->id)->merchant_id);
    }

    /** Elle traite ce qu'il y a, en une passe, sans se marcher dessus. */
    public function test_it_handles_several_merchants_at_once(): void
    {
        $marchand = Merchant::firstOrFail();

        $autreUser = $marchand->user->replicate();
        $autreUser->email = 'voisin@example.test';
        $autreUser->mobile = '0022997000009';
        $autreUser->unique_id = 'U-VOISIN';
        $autreUser->save();

        $voisin = $marchand->replicate();
        $voisin->user_id = $autreUser->id;
        $voisin->merchant_unique_id = 'M-VOISIN';
        $voisin->save();

        foreach ([[$marchand, 'BL-A'], [$voisin, 'BL-B']] as [$proprietaire, $suivi]) {
            $colis = $this->colis($proprietaire, $suivi);
            $ligne = new MerchantStatement();
            $ligne->forceFill([
                'company_id' => $proprietaire->company_id,
                'parcel_id' => $colis->id,
                'type' => StatementType::INCOME,
                'amount' => 1000,
                'date' => date('Y-m-d'),
            ])->save();
        }

        $this->migration()->up();

        $this->assertSame(0, MerchantStatement::whereNull('merchant_id')->count());
        $this->assertSame(1, MerchantStatement::where('merchant_id', $marchand->id)->count());
        $this->assertSame(1, MerchantStatement::where('merchant_id', $voisin->id)->count());
    }
}
