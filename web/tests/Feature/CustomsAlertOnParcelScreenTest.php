<?php

namespace Tests\Feature;

use App\Enums\CustomsAlertStatus;
use App\Enums\CustomsLevel;
use App\Enums\UserType;
use App\Models\Backend\CustomsAlert;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S94** — l'alerte douanière sur la fiche colis **web** (back-office et panneau marchand).
 *
 * S82 l'a mise sur `parcel/details/{id}` de l'API, donc dans l'app ; les deux fiches web
 * (`admin/parcel/details/{id}`, `merchant/parcel/details/{id}`) ne la montraient pas : un
 * agent qui ouvrait un colis export ne voyait ni le niveau ni le document exigé, il devait
 * aller le chercher dans « Alertes douanières ». Même règle qu'en S82 : les alertes voyagent
 * **avec le colis déjà vérifié** par le dépôt, un domestique n'en a pas, celles d'un autre
 * colis n'y sont pas. Le back-office les marque traitées (`parcel_update`), le marchand lit.
 */
class CustomsAlertOnParcelScreenTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    /** Le bloc de la fiche ; « Alertes douanières » seul ne suffit pas, le menu latéral le porte sur toute page. */
    private const CARTE = 'id="alertes-douanieres-colis"';

    private const DOCUMENT = 'Certificat de circulation UEMOA';
    private const AUTRE_DOCUMENT = 'Certificat phytosanitaire';

    private Merchant $marchand;
    private Parcel $export;
    private Parcel $autreExport;
    private Parcel $domestique;
    private CustomsAlert $alerte;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        // L'hôte de test sert la société 1 (`settings()` hors requête) ; le marchand de démonstration est en société 2.
        $this->marchand = $this->marchandDe((int) settings()->id);
        $this->export = $this->colis('Export TG');
        $this->autreExport = $this->colis('Second export');
        $this->domestique = $this->colis('Cotonou');

        $this->alerte = $this->alerteSur($this->export, self::DOCUMENT, CustomsLevel::WARNING);
        $this->alerteSur($this->autreExport, self::AUTRE_DOCUMENT, CustomsLevel::BLOCKING);
    }

    public function test_the_back_office_parcel_screen_shows_the_alerts_of_that_parcel_only(): void
    {
        $page = $this->actingAs($this->agent(['parcel_read', 'parcel_update']))
            ->get(self::HOTE . '/admin/parcel/details/' . $this->export->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(self::CARTE, $page);
        $this->assertStringContainsString(__('customs.level_2'), $page, 'le niveau');
        $this->assertStringContainsString('Togo (TG)', $page, 'le pays');
        $this->assertStringContainsString(__('customs.category_textile'), $page, 'la catégorie, traduite');
        $this->assertStringContainsString(self::DOCUMENT, $page, 'le document exigé');
        $this->assertStringContainsString(__('customs.pending'), $page);
        $this->assertStringNotContainsString(self::AUTRE_DOCUMENT, $page, 'l\'alerte de l\'autre colis n\'est pas sur cette fiche');
    }

    public function test_a_domestic_parcel_has_no_customs_card(): void
    {
        $page = $this->actingAs($this->agent(['parcel_read', 'parcel_update']))
            ->get(self::HOTE . '/admin/parcel/details/' . $this->domestique->id)
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(self::CARTE, $page, 'pas de bloc douane sur un domestique');
        $this->assertStringNotContainsString(self::DOCUMENT, $page);
    }

    /** Le bouton « Traitée » suit `parcel_update`, comme la route qu'il appelle. */
    public function test_only_an_agent_who_may_update_parcels_can_resolve_from_the_screen(): void
    {
        $action = 'customs/alerts/' . $this->alerte->id . '/resolve';

        $avec = $this->actingAs($this->agent(['parcel_read', 'parcel_update'], 'traite'))
            ->get(self::HOTE . '/admin/parcel/details/' . $this->export->id)->assertOk()->getContent();
        $this->assertStringContainsString($action, $avec, 'le formulaire de traitement');

        $sans = $this->actingAs($this->agent(['parcel_read'], 'lit'))
            ->get(self::HOTE . '/admin/parcel/details/' . $this->export->id)->assertOk()->getContent();
        $this->assertStringContainsString(self::DOCUMENT, $sans, 'il lit l\'alerte');
        $this->assertStringNotContainsString($action, $sans, 'mais ne peut pas la traiter');
    }

    public function test_resolving_from_the_screen_marks_the_alert_and_the_screen_says_so(): void
    {
        $this->actingAs($this->agent(['parcel_read', 'parcel_update']))
            ->put(self::HOTE . '/admin/customs/alerts/' . $this->alerte->id . '/resolve')
            ->assertRedirect();

        $this->assertSame(CustomsAlertStatus::RESOLVED, $this->alerte->fresh()->status);

        $page = $this->get(self::HOTE . '/admin/parcel/details/' . $this->export->id)->assertOk()->getContent();
        $this->assertStringContainsString(__('customs.resolved'), $page);
        $this->assertStringNotContainsString('customs/alerts/' . $this->alerte->id . '/resolve', $page, 'plus rien à traiter');
    }

    public function test_the_merchant_panel_shows_the_alerts_and_no_resolve_button(): void
    {
        $page = $this->actingAs($this->marchand->user)
            ->get(self::HOTE . '/merchant/parcel/details/' . $this->export->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(self::CARTE, $page);
        $this->assertStringContainsString(self::DOCUMENT, $page);
        $this->assertStringNotContainsString(self::AUTRE_DOCUMENT, $page);
        $this->assertStringNotContainsString('/resolve', $page, 'le marchand lit ; le traitement est au back-office');
    }

    /* ─────────────────────────── fixtures ───────────────────────────────── */

    private function agent(array $permissions, string $suffixe = 'agent'): User
    {
        $agent = new User();
        $agent->company_id = settings()->id;
        $agent->name = 'Agent ' . $suffixe;
        $agent->email = 'agent.s94.' . $suffixe . '.' . uniqid() . '@example.test';
        $agent->mobile = '01970' . rand(10000, 99999);
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->permissions = $permissions;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand export ' . $societe;
        $utilisateur->email = 'marchand.s94.' . $societe . '@example.test';
        $utilisateur->mobile = '0197050001';
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME export ' . $societe,
            'current_balance' => 0,
        ]);
    }

    private function colis(string $client): Parcel
    {
        return Parcel::forceCreate([
            'company_id' => $this->marchand->company_id,
            'merchant_id' => $this->marchand->id,
            'tracking_id' => 'BL-' . uniqid(),
            'customer_name' => $client,
            'customer_phone' => '0197040002',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => 1,
        ]);
    }

    private function alerteSur(Parcel $colis, string $document, int $niveau): CustomsAlert
    {
        return CustomsAlert::create([
            'company_id' => $colis->company_id,
            'merchant_id' => $colis->merchant_id,
            'parcel_id' => $colis->id,
            'country_code' => 'TG',
            'country_name' => 'Togo',
            'goods_category' => 'textile',
            'level' => $niveau,
            'required_document' => $document,
            'message' => 'Message de test',
            'status' => CustomsAlertStatus::PENDING,
        ]);
    }
}
