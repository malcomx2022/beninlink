<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S61 — les quatre écrans de recherche et d'impression des colis.
 *
 * Suite de l'arriéré du filet S58 (21 → 17). Les quatre étaient déjà bornés :
 * `parcelSearchs` par S57 (piège du `orWhere`), `parcelMultiplePrintLabel` et
 * `bulkParcels` par S38, `filter`/`filterPrint` depuis toujours. Ils restaient à
 * l'arriéré parce que **lu n'est pas prouvé**.
 *
 * ⚠️ Le marqueur est le **nom du client** : c'est la donnée personnelle que ces
 * écrans rendent, et c'est elle que S57 a vue fuir sur `parcel/specific/search`.
 */
class ParcelSearchSurfaceScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();
    }

    public function test_the_parcel_search_stays_inside_the_company(): void
    {
        $this->colisDe(settings()->id, 'MIEN');
        $this->colisDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/parcel/specific/search', ['search' => 'CLIENT-S61']);

        $this->assertStringContainsString('CLIENT-S61-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('CLIENT-S61-SIEN', $page,
            'la recherche rend le NOM DU CLIENT d\'une AUTRE société — la fuite de S57');
    }

    public function test_the_parcel_filter_stays_inside_the_company(): void
    {
        $this->colisDe(settings()->id, 'MIEN');
        $this->colisDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/parcel/filter', [
            'parcel_date' => now()->subDay()->toDateString() . 'To' . now()->addDay()->toDateString(),
        ]);

        $this->assertStringContainsString('CLIENT-S61-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('CLIENT-S61-SIEN', $page,
            'le filtre des colis rend celui d\'une AUTRE société');
    }

    public function test_the_multiple_label_print_stays_inside_the_company(): void
    {
        $mien = $this->colisDe(settings()->id, 'MIEN');
        $sien = $this->colisDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/parcel/multiple/print/label', ['parcels' => [$mien->id, $sien->id]]);

        $this->assertStringContainsString('CLIENT-S61-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('CLIENT-S61-SIEN', $page,
            'les étiquettes impriment le client d\'une AUTRE société');
    }

    public function test_the_bulk_assign_print_stays_inside_the_company(): void
    {
        $mien = $this->colisDe(settings()->id, 'MIEN');
        $sien = $this->colisDe(self::AUTRE, 'SIEN');

        $page = $this->ecran('/admin/parcel/bulkassign/print', [
            'delivery_man_id' => $this->livreurDe(settings()->id)->id,
            'parcels' => [$mien->id, $sien->id],
        ]);

        $this->assertStringContainsString('CLIENT-S61-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringNotContainsString('CLIENT-S61-SIEN', $page,
            'l\'impression d\'affectation en lot rend le colis d\'une AUTRE société');
    }

    /* ────────────────────────────── le harnais ──────────────────────────── */

    private function ecran(string $uri, array $parametres): string
    {
        $agent = $this->agentDe(settings()->id);
        $agent->permissions = ['parcel_read'];
        $agent->save();

        $reponse = $this->actingAs($agent)->get(self::HOTE . $uri . '?' . http_build_query($parametres));
        $reponse->assertOk();

        return $reponse->getContent();
    }

    private function colisDe(int $societe, string $marque): Parcel
    {
        return Parcel::forceCreate([
            'company_id' => $societe,
            'merchant_id' => $this->marchandDe($societe)->id,
            'tracking_id' => 'SUIVI-S61-' . $marque,
            'invoice_no' => 'F-S61-' . $marque,
            'customer_name' => 'CLIENT-S61-' . $marque,
            'customer_phone' => '0022997' . ord($marque[0]) . '000',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => ParcelStatus::PENDING,
            'priority_type_id' => 1,
            'created_at' => now(),
        ]);
    }

    private function marchandDe(int $societe): Merchant
    {
        $existant = Merchant::where('company_id', $societe)->first();

        if ($existant) {
            return $existant;
        }

        $u = $this->agentDe($societe);
        $u->user_type = UserType::MERCHANT;
        $u->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'business_name' => 'PME S61 ' . $societe, 'current_balance' => 0,
            'opening_balance' => 0, 'wallet_balance' => 0, 'status' => Status::ACTIVE,
        ]);
    }

    private function livreurDe(int $societe): DeliveryMan
    {
        $u = $this->agentDe($societe);
        $u->user_type = UserType::DELIVERYMAN;
        $u->hub_id = Hub::firstWhere('company_id', $societe)?->id ?? Hub::forceCreate([
            'company_id' => $societe, 'name' => 'Entrepot S61 ' . $societe,
            'status' => Status::ACTIVE, 'current_balance' => 0,
        ])->id;
        $u->save();

        return DeliveryMan::forceCreate([
            'company_id' => $societe, 'user_id' => $u->id,
            'status' => Status::ACTIVE, 'delivery_charge' => 500,
        ]);
    }

    private function agentDe(int $societe): User
    {
        $n = User::count();
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent S61';
        $agent->email = 'agent.s61.' . $societe . '.' . $n . '@example.test';
        $agent->mobile = '00229975' . $societe . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }
}
