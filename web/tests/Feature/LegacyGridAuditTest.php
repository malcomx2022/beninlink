<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory as DeliveryCategory;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\DeliveryZoneCountry;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\Backend\Parcel;
use App\Repositories\DeliveryZone\DeliveryZoneInterface;
use App\Services\Pricing\LegacyGridAudit;
use App\Services\Pricing\ZoneGridConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **D4** — la porte de l'étape 6.
 *
 * L'étape 6 supprime les quatre colonnes héritées. Elle est irréversible et
 * **sèche** : une fois parties, `ChargeCalculator` n'a plus de repli, et une
 * société qui n'a pas converti son barème ne peut plus créer un colis.
 *
 * Le plan disait « une fois les apps déployées ». C'est une intention, pas une
 * vérification. Ces tests fixent la vérification : ce qui compte comme
 * blocage, ce qui n'est qu'un avertissement, et le fait que la commande
 * **sorte en erreur** tant qu'une société n'est pas prête — pour qu'un
 * déploiement automatisé s'arrête plutôt que de continuer.
 */
class LegacyGridAuditTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $merchant;

    private GeneralSettings $societe;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->merchant = Merchant::firstOrFail();
        $this->societe = GeneralSettings::findOrFail($this->merchant->company_id);
        $this->categoryId = DeliveryCategory::firstOrFail()->id;

        Auth::login($this->merchant->user);
    }

    private function audit(): array
    {
        return app(LegacyGridAudit::class)->auditerSociete($this->societe->fresh());
    }

    private function convertir(): void
    {
        app(ZoneGridConverter::class)->convert((int) $this->societe->id, ZoneGridConverter::SAME_DAY_SURCHARGE);
    }

    // ---- Ce qui bloque -----------------------------------------------------

    public function test_une_societe_sans_zone_nest_pas_prete(): void
    {
        $audit = $this->audit();

        $this->assertFalse(app(LegacyGridAudit::class)->estPrete($audit));
        $this->assertStringContainsString('aucune zone configurée', $audit['blocages'][0]);
    }

    public function test_une_tranche_heritee_sans_equivalent_zone_bloque(): void
    {
        $this->convertir();
        $this->assertTrue(app(LegacyGridAudit::class)->estPrete($this->audit()));

        // Une tranche zonée qui disparaît : le tarif existe dans l'ancien monde
        // et plus dans le nouveau. Après l'étape 6, la création serait refusée.
        $cotonou = DeliveryZone::where('company_id', $this->societe->id)
            ->where('code', DeliveryZone::COTONOU)->firstOrFail();
        DeliveryCharge::where('zone_id', $cotonou->id)->orderBy('weight')->first()->delete();

        $audit = $this->audit();
        $this->assertFalse(app(LegacyGridAudit::class)->estPrete($audit));
        $this->assertStringContainsString('sans tarif zoné', implode(' | ', $audit['blocages']));
    }

    public function test_un_bareme_negocie_reste_sur_les_colonnes_bloque(): void
    {
        $this->convertir();

        $ligne = new MerchantDeliveryCharge();
        $ligne->company_id = $this->societe->id;
        $ligne->merchant_id = $this->merchant->id;
        $ligne->delivery_charge_id = DeliveryCharge::where('company_id', $this->societe->id)
            ->whereNull('zone_id')->orderBy('weight')->firstOrFail()->id;
        $ligne->category_id = $this->categoryId;
        $ligne->weight = 1;
        $ligne->same_day = 900;
        $ligne->status = Status::ACTIVE;
        $ligne->save();

        $audit = $this->audit();
        $this->assertFalse(app(LegacyGridAudit::class)->estPrete($audit));
        // Le marchand perdrait son tarif négocié sans que rien ne le dise.
        $this->assertStringContainsString('négocié', implode(' | ', $audit['blocages']));
    }

    public function test_un_colis_recent_sans_zone_bloque(): void
    {
        $this->convertir();
        $this->assertTrue(app(LegacyGridAudit::class)->estPrete($this->audit()));

        $colis = new Parcel();
        $colis->forceFill([
            'company_id' => $this->societe->id,
            'merchant_id' => $this->merchant->id,
            'tracking_id' => 'AUDIT-1',
            'customer_name' => 'Aïcha Kora',
            'customer_address' => 'Cotonou',
            'customer_phone' => '0022997000041',
            'delivery_type_id' => 2,
            'cash_collection' => 1000,
            'status' => 1,
        ])->save();

        $audit = $this->audit();

        // Le signal le plus honnête sur l'état du parc : un écran ou une app en
        // circulation utilise encore le chemin hérité.
        $this->assertSame(1, $audit['colis_sans_zone']);
        $this->assertFalse(app(LegacyGridAudit::class)->estPrete($audit));
    }

    // ---- Ce qui n'est qu'un avertissement ---------------------------------

    public function test_une_zone_dexport_sans_forfait_avertit_sans_bloquer(): void
    {
        $this->convertir();

        // La conversion pose les forfaits tranchés par le métier ; on les
        // retire pour retrouver le cas d'une zone d'export non tarifée.
        $cedeao = app(DeliveryZoneInterface::class)->zoneExport();
        DeliveryZoneCountry::where('zone_id', $cedeao->id)->delete();

        $audit = $this->audit();

        // Une zone d'export non tarifée ne l'était pas davantage avant
        // l'étape 6 : elle ne peut pas bloquer une suppression qui ne la
        // concerne pas. Mais il faut la voir.
        $this->assertTrue(app(LegacyGridAudit::class)->estPrete($audit));
        $this->assertStringContainsString('aucun pays tarifé', implode(' | ', $audit['avertissements']));
    }

    public function test_les_forfaits_poses_par_la_conversion_levent_lavertissement(): void
    {
        $this->convertir();

        // Togo, Nigeria, Burkina : la décision du métier, écrite par la
        // conversion. L'avertissement n'a donc plus lieu d'être.
        $this->assertSame([], $this->audit()['avertissements']);
    }

    // ---- La commande ------------------------------------------------------

    public function test_la_commande_sort_en_erreur_tant_quune_societe_nest_pas_prete(): void
    {
        // Un déploiement automatisé doit s'arrêter là, pas continuer.
        $this->artisan('beninlink:bareme-herite', ['--societe' => $this->societe->id])
            ->expectsOutputToContain('aucune zone configurée')
            ->assertFailed();
    }

    public function test_la_commande_reussit_une_fois_la_conversion_faite(): void
    {
        $this->convertir();

        $this->artisan('beninlink:bareme-herite', ['--societe' => $this->societe->id])
            ->expectsOutputToContain("l'étape 6 peut être envisagée")
            ->assertSuccessful();
    }
}
