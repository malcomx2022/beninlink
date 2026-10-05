<?php

namespace Tests\Feature;

use App\Enums\CustomsLevel;
use App\Http\Requests\MerchantPanel\Parcel\StoreRequest as StoreRequestMarchand;
use App\Http\Requests\Parcel\StoreRequest as StoreRequestAdmin;
use App\Models\Backend\CustomsRule;
use App\Models\Backend\Merchant;
use App\Models\MerchantShops;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\SeedsTenant;
use App\Models\Backend\DeliveryZone;
use Tests\TestCase;

/**
 * W3 — le contrôle douanier manquait à la création côté back-office.
 *
 * `CustomsAllowed` dit dans son propre commentaire qu'elle vit dans
 * `StoreRequest` « parce que c'est le seul point commun aux TROIS chemins de
 * création : l'API marchand, le panneau marchand et l'administration ». Le
 * troisième ne la portait pas. Or `ParcelRepository` écrit bel et bien
 * `destination_country` et `customs_category` depuis la requête, dans `store()`,
 * `duplicateStore()` et `update()` : un colis d'export couvert par une règle
 * BLOQUANTE passait par le back-office alors qu'il était refusé partout ailleurs.
 */
class BackofficeCustomsGuardTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private CustomsRule $regleBloquante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        // `CustomsRule::ruleFor()` est scopé par société : sans compte connecté,
        // `settings()` retomberait sur la société 1 et aucune règle ne serait vue.
        Auth::login(Merchant::firstOrFail()->user);

        $this->regleBloquante = CustomsRule::where('level', CustomsLevel::BLOCKING)->firstOrFail();
    }

    /**
     * Le formulaire tel que le contrôleur le valide.
     *
     * La règle `requiredIf` de `customs_category` interroge `request()` : on lie
     * donc la requête au conteneur, comme le fait le contrôleur en vrai.
     */
    private function valider(array $donnees, string $classe = StoreRequestAdmin::class)
    {
        $this->app->instance('request', Request::create('/parcel/store', 'POST', $donnees));

        return Validator::make($donnees, (new $classe())->rules());
    }

    private function baseAdmin(array $douane = []): array
    {
        return [
            'merchant_id' => Merchant::firstOrFail()->id,
            'category_id' => 1,
            'delivery_type_id' => 1,
            'zone_id' => DeliveryZone::where('company_id', Merchant::firstOrFail()->company_id)->where('code', DeliveryZone::COTONOU)->value('id'),
            'customer_name' => 'Aicha Kora',
            'customer_address' => 'Cotonou, Akpakpa',
            'customer_phone' => '0022997000041',
        ] + $douane;
    }

    public function test_the_backoffice_refuses_a_blocked_export(): void
    {
        $validation = $this->valider($this->baseAdmin([
            'destination_country' => $this->regleBloquante->country_code,
            'customs_category' => $this->regleBloquante->goods_category,
        ]));

        $this->assertTrue($validation->fails());
        $this->assertArrayHasKey('customs_category', $validation->errors()->toArray());
    }

    /** Omettre la catégorie contournerait le blocage : elle devient obligatoire. */
    public function test_the_backoffice_demands_a_category_on_an_export(): void
    {
        $validation = $this->valider($this->baseAdmin([
            'destination_country' => $this->regleBloquante->country_code,
        ]));

        $this->assertTrue($validation->fails());
        $this->assertArrayHasKey('customs_category', $validation->errors()->toArray());
    }

    public function test_a_domestic_parcel_is_untouched(): void
    {
        $this->assertFalse($this->valider($this->baseAdmin())->fails());
        $this->assertFalse($this->valider($this->baseAdmin(['destination_country' => 'BJ']))->fails());
    }

    public function test_an_allowed_export_still_passes(): void
    {
        $validation = $this->valider($this->baseAdmin([
            'destination_country' => $this->regleBloquante->country_code,
            'customs_category' => 'categorie-sans-regle-bloquante',
        ]));

        $this->assertFalse($validation->fails(), json_encode($validation->errors()->toArray()));
    }

    /** Les trois chemins de création portent désormais la même règle. */
    public function test_the_backoffice_and_the_merchant_paths_agree(): void
    {
        $douane = [
            'destination_country' => $this->regleBloquante->country_code,
            'customs_category' => $this->regleBloquante->goods_category,
        ];

        $marchand = $this->valider([
            'shop_id' => MerchantShops::firstOrFail()->id,
            'category_id' => 1,
            'delivery_type_id' => 1,
            'zone_id' => DeliveryZone::where('company_id', Merchant::firstOrFail()->company_id)->where('code', DeliveryZone::COTONOU)->value('id'),
            'customer_name' => 'Aicha Kora',
            'customer_address' => 'Cotonou, Akpakpa',
            'customer_phone' => '0022997000041',
        ] + $douane, StoreRequestMarchand::class);

        $this->assertTrue($marchand->fails());
        $this->assertTrue($this->valider($this->baseAdmin($douane))->fails());
    }
}
