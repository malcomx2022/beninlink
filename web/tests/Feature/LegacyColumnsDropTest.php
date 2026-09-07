<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\DeliveryCategory;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\Backend\Parcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * D4, étape 6 — la migration qui retire les quatre colonnes, et son refus.
 *
 * C'est la seule étape **irréversible** du chantier, et la seule **sèche** :
 * les colonnes parties, `ChargeCalculator` n'a plus de repli. Une société qui
 * n'a pas converti ne facturerait plus rien — et le découvrirait au premier
 * colis, pas au déploiement.
 *
 * La migration vérifie donc d'abord, société par société, et **s'arrête**
 * sinon. Ce fichier exerce ce refus : c'est la ceinture de sécurité du dernier
 * instant, celle qui transforme un désastre silencieux en déploiement qui
 * échoue proprement.
 *
 * ⚠️ La migration a déjà tourné quand ces tests s'exécutent (`RefreshDatabase`
 * rejoue toute la pile). On la relit donc depuis le fichier et on rejoue son
 * `up()` sur un schéma remis dans l'état d'avant par `down()` — c'est le seul
 * moyen d'exercer un contrôle qui, par nature, ne se déclenche qu'une fois.
 */
class LegacyColumnsDropTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const CHEMIN = 'migrations/2026_09_07_100000_drop_legacy_delivery_columns.php';

    private Merchant $merchant;

    private GeneralSettings $societe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        $this->merchant = Merchant::firstOrFail();
        $this->societe = GeneralSettings::findOrFail($this->merchant->company_id);
    }

    /** Remet les colonnes, pour pouvoir rejouer la suppression. */
    private function remettreLesColonnes(): void
    {
        (require database_path(self::CHEMIN))->down();
    }

    private function rejouer(): void
    {
        (require database_path(self::CHEMIN))->up();
    }

    /** Une ligne de barème restée sans zone : ce que la migration refuse. */
    private function ligneSansZone(int $poids = 1): DeliveryCharge
    {
        $ligne = new DeliveryCharge();
        $ligne->company_id = $this->societe->id;
        $ligne->category_id = DeliveryCategory::firstOrFail()->id;
        $ligne->weight = $poids;
        $ligne->position = $poids;
        $ligne->status = Status::ACTIVE;
        $ligne->save();

        return $ligne;
    }

    // ---- Ce qu'elle fait ---------------------------------------------------

    public function test_les_quatre_colonnes_ont_disparu_des_deux_tables(): void
    {
        foreach (['delivery_charges', 'merchant_delivery_charges'] as $table) {
            foreach (['same_day', 'next_day', 'sub_city', 'outside_city'] as $colonne) {
                $this->assertFalse(
                    Schema::hasColumn($table, $colonne),
                    "{$table}.{$colonne} devrait avoir disparu",
                );
            }
            // Ce qui reste, c'est le modèle par zones.
            $this->assertTrue(Schema::hasColumn($table, 'zone_id'));
            $this->assertTrue(Schema::hasColumn($table, 'amount'));
        }
    }

    public function test_elle_est_rejouable_sur_un_schema_deja_nettoye(): void
    {
        // Un `migrate` relancé ne doit pas échouer sur des colonnes absentes.
        $this->rejouer();

        $this->assertFalse(Schema::hasColumn('delivery_charges', 'same_day'));
    }

    // ---- Ce qu'elle refuse -------------------------------------------------

    public function test_elle_refuse_une_societe_sans_zone(): void
    {
        $this->remettreLesColonnes();
        DeliveryCharge::where('company_id', $this->societe->id)->delete();
        DeliveryZone::where('company_id', $this->societe->id)->delete();
        $this->ligneSansZone();

        try {
            $this->rejouer();
            $this->fail('La migration devait refuser de retirer les colonnes.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('aucune zone nationale configurée', $e->getMessage());
            // Le message dit quoi faire, et depuis quelle version : personne ne
            // lira le fichier de migration à 3 h du matin.
            $this->assertStringContainsString('beninlink:zones-tarifaires', $e->getMessage());
            $this->assertStringContainsString('version PRÉCÉDENTE', $e->getMessage());
        }

        // Rien n'a été retiré : le schéma est intact.
        $this->assertTrue(Schema::hasColumn('delivery_charges', 'same_day'));
    }

    public function test_elle_refuse_une_tranche_sans_equivalent_zone(): void
    {
        $this->remettreLesColonnes();
        // Une tranche héritée qui n'existe dans aucune zone : après la
        // suppression, ce tarif n'existerait plus nulle part.
        $this->ligneSansZone(97);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/tranche 97 sans tarif zoné/');

        $this->rejouer();
    }

    public function test_elle_refuse_un_bareme_negocie_sans_zone(): void
    {
        $this->remettreLesColonnes();

        $negocie = new MerchantDeliveryCharge();
        $negocie->company_id = $this->societe->id;
        $negocie->merchant_id = $this->merchant->id;
        $negocie->delivery_charge_id = DeliveryCharge::where('company_id', $this->societe->id)->firstOrFail()->id;
        $negocie->category_id = DeliveryCategory::firstOrFail()->id;
        $negocie->weight = 1;
        $negocie->amount = 900;
        $negocie->status = Status::ACTIVE;
        $negocie->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/barème\(s\) négocié\(s\)/');

        $this->rejouer();
    }

    /**
     * Le signal le plus honnête sur l'état du parc : tant que des colis
     * arrivent sans zone, une app en circulation crée encore par le chemin
     * d'avant, quoi que dise le calendrier de déploiement.
     */
    public function test_elle_refuse_tant_que_des_colis_naissent_sans_zone(): void
    {
        $this->remettreLesColonnes();

        $colis = new Parcel();
        $colis->forceFill([
            'company_id' => $this->societe->id,
            'merchant_id' => $this->merchant->id,
            'tracking_id' => 'SANS-ZONE',
            'customer_name' => 'Aïcha Kora',
            'customer_address' => 'Cotonou',
            'customer_phone' => '0022997000041',
            'delivery_type_id' => 2,
            'cash_collection' => 1000,
            'status' => 1,
        ])->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/colis créé\(s\) sans zone/');

        $this->rejouer();
    }

    // ---- Ce qu'elle emporte ------------------------------------------------

    /**
     * Les lignes héritées sont supprimées, pas laissées vides : le contrôle a
     * établi que chacune a son équivalent zoné, et une ligne sans zone ni
     * montant ne serait plus qu'un piège pour le résolveur.
     */
    public function test_elle_supprime_les_lignes_restees_sans_zone(): void
    {
        $this->remettreLesColonnes();

        // Une tranche héritée dont l'équivalent zoné existe : le contrôle
        // passe, et la ligne s'en va.
        $poids = (int) DeliveryCharge::where('company_id', $this->societe->id)
            ->whereNotNull('zone_id')->orderBy('weight')->firstOrFail()->weight;
        $ligne = $this->ligneSansZone($poids);

        $this->rejouer();

        $this->assertNull(DeliveryCharge::find($ligne->id));
        $this->assertGreaterThan(0, DeliveryCharge::where('company_id', $this->societe->id)
            ->whereNotNull('zone_id')->count(), 'la grille zonée est intacte');
    }
}
