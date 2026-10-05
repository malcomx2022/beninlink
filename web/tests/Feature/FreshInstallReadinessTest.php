<?php

namespace Tests\Feature;

use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\DeliveryZoneCountry;
use App\Models\Backend\GeneralSettings;
use App\Services\Pricing\ZoneCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S86** — une installation neuve ne doit pas échouer son premier déploiement.
 *
 * L'installateur web enchaîne `migrate:refresh` puis `db:seed`. Les semences
 * créent **deux** sociétés (« We Courier », « Company »), et jusqu'à S86
 * `DeliveryChargeSeeder` ne posait zones et grille que pour la seconde. Or
 * `deploy.sh` exécute `beninlink:tarification-prete` **avant** de migrer, et la
 * commande sort en erreur dès qu'une société n'a aucune zone : le premier
 * déploiement automatique de toute installation neuve s'arrêtait là, à chaque
 * tentative, sans qu'un mot du message dise « votre base vient d'être amorcée ».
 *
 * Le guide de mise en service et le job de répétition (S80) contournaient tous
 * deux le piège **à la main** — `zones-tarifaires --societe=1 --installer` après
 * `db:seed`. La répétition rejouait donc le contournement, pas l'installation.
 *
 * Ces tests fixent l'autre forme : les semences posent le **cadre** (zones,
 * délais, forfaits CEDEAO) de **chaque** société qu'elles créent, et le constat
 * sort en succès sur une base qui vient d'être amorcée. La grille de
 * démonstration reste sur la société 2 : les montants appartiennent au
 * transporteur, et un cadre sans montants est « prête » pour l'audit.
 */
class FreshInstallReadinessTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    /** La société de démonstration du socle, la seule à recevoir une grille. */
    private const DEMONSTRATION = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    public function test_the_seeds_create_more_than_one_company(): void
    {
        // Le piège n'existe que parce qu'il y a plusieurs sociétés : si le jeu
        // d'amorçage n'en créait qu'une, les deux tests suivants ne mesureraient
        // plus rien.
        $this->assertGreaterThan(1, GeneralSettings::count(), 'fixture : les semences du socle créent deux sociétés');
        $this->assertTrue(GeneralSettings::where('id', self::DEMONSTRATION)->exists());
    }

    public function test_every_seeded_company_has_its_zone_frame(): void
    {
        $zones = array_column(ZoneCatalog::ZONES, 0);
        $delais = array_column(ZoneCatalog::DELAIS, 0);
        $pays = array_column(ZoneCatalog::PAYS, 0);

        foreach (GeneralSettings::orderBy('id')->get() as $societe) {
            $message = "société « {$societe->name} » (n° {$societe->id})";

            $this->assertEqualsCanonicalizing(
                $zones,
                DeliveryZone::where('company_id', $societe->id)->pluck('code')->all(),
                "{$message} : les quatre zones du catalogue"
            );
            $this->assertEqualsCanonicalizing(
                $delais,
                DeliveryDelay::where('company_id', $societe->id)->pluck('code')->all(),
                "{$message} : les trois délais"
            );

            $cedeao = DeliveryZone::where('company_id', $societe->id)->where('code', DeliveryZone::CEDEAO)->firstOrFail();
            $this->assertEqualsCanonicalizing(
                $pays,
                DeliveryZoneCountry::where('zone_id', $cedeao->id)->pluck('code')->all(),
                "{$message} : les forfaits CEDEAO"
            );
        }
    }

    public function test_a_freshly_seeded_base_passes_the_pricing_readiness_check(): void
    {
        // Exactement ce que `deploy.sh` exécute avant de migrer, sans option :
        // toutes les sociétés, et un code de sortie qui arrête le déploiement.
        $constat = $this->artisan('beninlink:tarification-prete')
            ->doesntExpectOutputToContain('pas prête')
            ->expectsOutputToContain('tarife par zones');

        foreach (GeneralSettings::pluck('name') as $nom) {
            $constat->expectsOutputToContain($nom);
        }

        $constat->assertExitCode(0);
    }

    public function test_the_demo_grid_stays_on_the_demo_company_only(): void
    {
        // Les montants d'une grille appartiennent au transporteur : le socle n'en
        // pose que pour sa société de démonstration. Les autres reçoivent le cadre
        // et saisissent leur grille dans Réglages → Zones et barème.
        $this->assertGreaterThan(0, DeliveryCharge::where('company_id', self::DEMONSTRATION)->count());
        $this->assertSame(
            0,
            DeliveryCharge::where('company_id', '!=', self::DEMONSTRATION)->count(),
            'une grille a été posée pour une société qui n\'est pas celle de démonstration'
        );
    }
}
