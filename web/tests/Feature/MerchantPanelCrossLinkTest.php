<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as Router;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S70 — les liens du panneau marchand qui visaient l'autre panneau.
 *
 * S41 a posé la garde `panel:merchant` / `panel:back-office` en affirmant que
 * « aucune vue marchande n'appelle une URL `admin/` ». **C'était faux**, et la
 * garde a transformé des liens muets en **403** : cinq routes `admin/` étaient
 * nommées par des vues **vivantes** du panneau marchand.
 *
 * | Vue | `route()` nommée | Ce que le marchand touchait |
 * |---|---|---|
 * | `parcel/create`, `parcel/edit` | `parcel.index` | le bouton **Annuler** |
 * | `parcel/logs` | `parcel.index` | le fil d'Ariane « Colis » |
 * | `reports/total_summery` | `parcel.reports`, `parcel.total.summery.index` | le fil d'Ariane et **Effacer** |
 * | `parcel/index` | `parcel.multiple.print-label` | **Imprimer les étiquettes** en lot |
 * | `reports/*` | `parcel.merchant.get` | rien — latent : le sélecteur n'existe pas dans ces vues |
 *
 * Le dernier cas est celui qui a ouvert le lot (relevé en S69), et c'est le seul
 * qui ne produisait **aucun** 403 : `parcel/filter.js` et `reports.js` initialisent
 * un `select2` sur `#parcelMerchantid`, absent de ces pages. En revanche ces mêmes
 * scripts lisaient `merchantUrl` et `hubUrl` **sans que la vue les définisse** — une
 * `ReferenceError` qui coupait le reste du `document.ready`. Deux défauts de la
 * même famille : **le panneau marchand réutilise les scripts et les routes du
 * back-office comme s'il en faisait partie.**
 *
 * Trois propriétés :
 *  1. aucune vue du panneau marchand ne nomme une route servie sous `admin/` — le
 *     filet lit les vues, pas les intentions ;
 *  2. les écrans concernés répondent 200 à un marchand et ne rendent aucune URL
 *     `admin/` ;
 *  3. l'impression d'étiquettes en lot existe au panneau marchand, et ne rend que
 *     **ses** colis (règle de lot S38 : l'identifiant d'un autre est ignoré).
 */
class MerchantPanelCrossLinkTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    private const VUES_MARCHAND = 'resources/views/backend/merchant_panel';

    private Merchant $mien;
    private Merchant $voisin;
    private Parcel $aMoi;
    private Parcel $aLui;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
        $this->souscrireLeLocataire();

        $this->mien = $this->marchandDe('MIEN');
        $this->voisin = $this->marchandDe('SIEN');
        $this->aMoi = $this->colisPour($this->mien, 'MIEN');
        $this->aLui = $this->colisPour($this->voisin, 'SIEN');
    }

    /* ─────────────── 1. le filet : une vue d'un panneau ne nomme pas l'autre ─── */

    public function test_no_merchant_panel_view_names_a_back_office_route(): void
    {
        $uris = [];
        foreach (Router::getRoutes()->getRoutes() as $route) {
            if ($route->getName()) {
                $uris[$route->getName()] = $route->uri();
            }
        }
        $this->assertArrayHasKey('merchant-panel.parcel.index', $uris, 'les routes du locataire doivent être montées');

        $croises = [];
        foreach (Finder::create()->files()->in(base_path(self::VUES_MARCHAND))->name('*.blade.php') as $vue) {
            preg_match_all("/route\\(\\s*'([A-Za-z0-9._-]+)'/", $vue->getContents(), $m);
            foreach (array_unique($m[1]) as $nom) {
                if (isset($uris[$nom]) && str_starts_with($uris[$nom], 'admin/')) {
                    $croises[] = $vue->getRelativePathname() . " → route('$nom') = admin/… ";
                }
            }
        }

        $this->assertSame([], $croises,
            "Des vues du panneau marchand nomment une route du back-office (403 garanti pour un marchand, S41) :\n - "
            . implode("\n - ", $croises));
    }

    /* ─────────────── 2. les écrans, vus par un marchand ──────────────────────── */

    /** @dataProvider ecrans */
    public function test_a_merchant_screen_answers_ok_and_carries_no_back_office_url(string $uri): void
    {
        $uri = str_replace('{id}', (string) $this->aMoi->id, $uri);

        $reponse = $this->actingAs($this->mien->user)->get(self::HOTE . $uri);
        $reponse->assertOk();

        $this->assertStringNotContainsString(self::HOTE . '/admin/', (string) $reponse->getContent(),
            "l'écran $uri rend une URL du back-office");
    }

    public static function ecrans(): array
    {
        return [
            'liste des colis' => ['/merchant/parcel/index'],
            'création' => ['/merchant/parcel/create'],
            'modification' => ['/merchant/parcel/edit/{id}'],
            'chronologie' => ['/merchant/parcel/logs/{id}'],
            'récapitulatif' => ['/merchant/reports/total-summery'],
            'rapport colis' => ['/merchant/reports/parcel-reports'],
        ];
    }

    /** La prémisse mesurée : la route `admin/` que le bouton visait refuse bien un marchand. */
    public function test_the_back_office_label_route_refuses_a_merchant(): void
    {
        $this->actingAs($this->mien->user)
            ->get(self::HOTE . '/admin/parcel/multiple/print/label?' . http_build_query(['parcels' => [$this->aMoi->id]]))
            ->assertForbidden();
    }

    /* ─────────────── 3. les étiquettes en lot, au panneau marchand ───────────── */

    public function test_the_merchant_label_print_renders_only_its_own_parcels(): void
    {
        $reponse = $this->actingAs($this->mien->user)->get(self::HOTE . '/merchant/parcel/multiple/print/label?'
            . http_build_query(['parcels' => [$this->aMoi->id, $this->aLui->id]]));
        $reponse->assertOk();
        $page = (string) $reponse->getContent();

        $this->assertStringContainsString('CLIENT-S70-MIEN', $page, 'contrôle positif tombé');
        $this->assertStringContainsString($this->aMoi->tracking_id, $page);
        $this->assertStringNotContainsString('CLIENT-S70-SIEN', $page,
            'les étiquettes du panneau marchand impriment le client d\'un AUTRE marchand');
    }

    public function test_the_merchant_label_print_requires_a_selection(): void
    {
        $this->actingAs($this->mien->user)
            ->get(self::HOTE . '/merchant/parcel/multiple/print/label')
            ->assertStatus(302); // la validation renvoie à l'écran, comme l'administration
    }

    /* ─────────────── 4. les scripts partagés ne lisent plus de global absent ─── */

    public function test_the_merchant_scripts_read_no_undefined_global(): void
    {
        // Le code, pas ses commentaires : celui-ci raconte le défaut (leçon de S69).
        $filtre = preg_replace('~//[^\n]*~', '', file_get_contents(public_path('backend/js/merchant_panel/parcel/filter.js')));
        $this->assertStringNotContainsString('merchantUrl', $filtre,
            'le panneau marchand n\'a pas de sélecteur de marchand : ce global n\'y est jamais défini');

        $rapports = file_get_contents(public_path('backend/js/reports/reports.js'));
        foreach (['merchantUrl', 'hubUrl'] as $global) {
            $this->assertStringContainsString("typeof $global !== 'undefined'", $rapports,
                "`reports.js` est chargé par le panneau marchand, qui ne définit pas `$global`");
        }

        foreach (['reports/total_summery', 'reports/parcel_reports'] as $vue) {
            $source = file_get_contents(base_path(self::VUES_MARCHAND . "/$vue.blade.php"));
            $this->assertStringNotContainsString('var merchantUrl', $source);
            $this->assertStringNotContainsString("backend/js/parcel/filter.js", $source,
                'la vue marchande charge le script du panneau marchand, pas celui du back-office');
        }
    }

    /* ─────────────── fixtures ────────────────────────────────────────────────── */

    private function marchandDe(string $marque): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = settings()->id;
        $utilisateur->name = 'Marchand ' . $marque;
        $utilisateur->email = 'marchand.' . strtolower($marque) . '@example.test';
        $utilisateur->mobile = '0022997' . sprintf('%06d', crc32($marque) % 1000000);
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        $marchand = Merchant::forceCreate([
            'company_id' => settings()->id,
            'user_id' => $utilisateur->id,
            'business_name' => 'PME ' . $marque,
            'current_balance' => 0,
            // Les écrans lisent `cod_charges['inside_city']` sans le vérifier ; l'inscription le pose.
            'cod_charges' => ['inside_city' => 1, 'sub_city' => 2, 'outside_city' => 3],
        ]);

        // L'inscription crée toujours une boutique par défaut, et les écrans de
        // création/modification la lisent sans la vérifier (`$shops[0]->id`).
        MerchantShops::forceCreate([
            'merchant_id' => $marchand->id,
            'name' => 'Boutique ' . $marque,
            'contact_no' => '0022997000000',
            'address' => 'Cotonou',
            'status' => Status::ACTIVE,
            'default_shop' => Status::ACTIVE,
        ]);

        return $marchand;
    }

    private function colisPour(Merchant $marchand, string $marque): Parcel
    {
        return Parcel::forceCreate([
            'company_id' => settings()->id,
            'merchant_id' => $marchand->id,
            'tracking_id' => 'BL-S70-' . $marque,
            'customer_name' => 'CLIENT-S70-' . $marque,
            'customer_phone' => '0022997000000',
            'customer_address' => 'Cotonou',
            'cash_collection' => 10000,
            'current_payable' => 9000,
            'status' => ParcelStatus::PENDING,
            'priority_type_id' => 1,
        ]);
    }
}
