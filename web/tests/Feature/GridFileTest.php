<?php

namespace Tests\Feature;

use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory as DeliveryCategory;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\Merchant;
use App\Services\Pilote\PiloteDataset;
use App\Services\Pricing\GridFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S72** — la grille nationale lue dans un fichier, posée par la commande,
 * partagée avec le jeu pilote, et qui **ne réécrit jamais** ce que le
 * transporteur a ajusté à l'écran.
 *
 * Le point que le métier a tenu à fixer (2026-10-03) : les montants de la
 * grille **ne sont pas figés**. Le fichier est un point de départ ; l'écran
 * *Réglages → Zones et barème* garde le dernier mot, à tout moment, et une
 * relance de la commande (à chaque déploiement) ne ramène aucun montant à la
 * valeur du fichier. C'est `test_un_montant_reajuste_a_l_ecran_survit_a_la_relance`.
 */
class GridFileTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    /** La grille de départ (tarifs du 2026-09-06) : poids => [Cotonou, Périphérie, Intérieur]. */
    private const DEPART = [
        1 => [800, 1500, 2500],
        3 => [1200, 2000, 3500],
        5 => [1700, 2800, 4500],
        10 => [2500, 4000, 6500],
    ];

    private const CATEGORIE = 'Colis standard (kg)';

    private const NATIONALES = [DeliveryZone::COTONOU, DeliveryZone::PERIPHERIE, DeliveryZone::INTERIEUR];

    private int $companyId;

    /** @var list<string> fichiers temporaires à retirer */
    private array $temporaires = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->companyId = (int) Merchant::firstOrFail()->company_id;
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaires as $fichier) {
            @unlink($fichier);
        }
        parent::tearDown();
    }

    // ---- Le fichier versionné -----------------------------------------------

    public function test_le_fichier_versionne_porte_la_grille_de_depart_documentee(): void
    {
        $lignes = app(GridFile::class)->lire(base_path(GridFile::DEFAUT));

        $this->assertCount(4, $lignes);
        foreach ($lignes as $ligne) {
            $this->assertSame(self::CATEGORIE, $ligne['categorie']);
            $this->assertArrayHasKey($ligne['poids_max'], self::DEPART, "tranche {$ligne['poids_max']} kg inattendue");
            $this->assertSame(
                array_combine(self::NATIONALES, self::DEPART[$ligne['poids_max']]),
                $ligne['montants'],
                "tranche {$ligne['poids_max']} kg : montants différents de la grille documentée"
            );
        }
    }

    /** Un chemin relatif se lit depuis `web/`, comme la documentation l'écrit. */
    public function test_le_chemin_relatif_se_lit_depuis_la_racine_de_web(): void
    {
        $this->assertCount(4, app(GridFile::class)->lire(GridFile::DEFAUT));
    }

    // ---- La commande ----------------------------------------------------------

    public function test_la_commande_pose_le_cadre_et_la_grille_et_la_societe_facture(): void
    {
        $this->retirerLesZones();

        $this->artisan('beninlink:zones-tarifaires', $this->arguments())
            ->expectsOutputToContain('4 tranche(s), 1 catégorie(s)')
            ->expectsOutputToContain('Grille : 12 ligne(s) créée(s), 0 conservée(s)')
            ->assertSuccessful();

        $this->assertSame(self::DEPART, $this->grilleEnBase());
        $this->artisan('beninlink:tarification-prete', ['--societe' => $this->companyId])->assertSuccessful();
    }

    public function test_relancer_la_commande_ne_cree_rien_et_conserve_tout(): void
    {
        $this->retirerLesZones();
        $this->artisan('beninlink:zones-tarifaires', $this->arguments())->assertSuccessful();

        $this->artisan('beninlink:zones-tarifaires', $this->arguments())
            ->expectsOutputToContain('Grille : 0 ligne(s) créée(s), 12 conservée(s)')
            ->assertSuccessful();

        $this->assertSame(12, $this->lignes()->count(), 'pas de ligne en double');
        $this->assertSame(1, DeliveryCategory::where('company_id', $this->companyId)->where('title', self::CATEGORIE)->count(), 'pas de catégorie en double');
    }

    /**
     * **La demande du métier** : les montants ne sont pas figés, le transporteur
     * les réajuste à tout moment depuis le back-office, et la commande relancée
     * au déploiement suivant ne les ramène pas à la valeur du fichier.
     */
    public function test_un_montant_reajuste_a_l_ecran_survit_a_la_relance(): void
    {
        $this->retirerLesZones();
        $this->artisan('beninlink:zones-tarifaires', $this->arguments())->assertSuccessful();

        // Le transporteur passe Cotonou ≤ 1 kg de 800 à 950 F dans « Zones et barème ».
        $cotonou = DeliveryZone::where('company_id', $this->companyId)->where('code', DeliveryZone::COTONOU)->firstOrFail();
        $ligne = $this->lignes()->where('zone_id', $cotonou->id)->where('weight', 1)->firstOrFail();
        $this->assertSame(800, (int) $ligne->amount);
        $ligne->amount = 950;
        $ligne->save();

        $this->artisan('beninlink:zones-tarifaires', $this->arguments())->assertSuccessful();

        $this->assertSame(950, (int) $ligne->fresh()->amount, 'le montant ajusté à l’écran garde le dernier mot');
        $attendu = self::DEPART;
        $attendu[1][0] = 950;
        $this->assertSame($attendu, $this->grilleEnBase(), 'rien d’autre n’a bougé');
    }

    public function test_le_constat_sans_installer_n_ecrit_rien(): void
    {
        $this->artisan('beninlink:zones-tarifaires', ['--societe' => $this->companyId, '--grille' => GridFile::DEFAUT])
            ->expectsOutputToContain('Grille : 12 ligne(s) à créer')
            ->assertSuccessful();

        $this->assertSame(0, $this->lignes()->count());
        $this->assertNull(DeliveryCategory::where('company_id', $this->companyId)->where('title', self::CATEGORIE)->first());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function fichiersFautifs(): array
    {
        $entete = "categorie;poids_max;cotonou;peripherie;interieur\n";

        return [
            'montant décimal' => [$entete . "Colis;1;800.5;1500;2500\n", 'FCFA entiers'],
            'colonne cedeao' => ["categorie;poids_max;cotonou;peripherie;interieur;cedeao\nColis;1;800;1500;2500;12000\n", 'forfait par pays'],
            'colonne manquante' => [$entete . "Colis;1;800;1500\n", '4 colonnes, 5 attendues'],
            'tranche en double' => [$entete . "Colis;1;800;1500;2500\nColis;1;900;1600;2600\n", 'déclarée deux fois'],
            'poids non entier' => [$entete . "Colis;1,5;800;1500;2500\n", 'poids maximal entier'],
            'fichier vide' => ["# rien\n", 'vide'],
        ];
    }

    /** @dataProvider fichiersFautifs */
    public function test_un_fichier_fautif_refuse_tout_et_n_ecrit_rien(string $contenu, string $motif): void
    {
        $this->retirerLesZones();

        $this->artisan('beninlink:zones-tarifaires', $this->arguments($this->fichier($contenu)))
            ->expectsOutputToContain('Grille refusée')
            ->expectsOutputToContain($motif)
            ->assertFailed();

        // Tout ou rien : le fichier est lu AVANT la première écriture, les zones
        // elles-mêmes ne sont pas posées.
        $this->assertSame(0, DeliveryZone::where('company_id', $this->companyId)->count());
        $this->assertSame(0, $this->lignes()->count());
    }

    public function test_un_fichier_introuvable_est_refuse(): void
    {
        $this->artisan('beninlink:zones-tarifaires', $this->arguments('database/bareme/nexiste-pas.csv'))
            ->expectsOutputToContain('introuvable')
            ->assertFailed();
    }

    /** « 1 500 » (espace de milliers) est toléré : c'est ainsi qu'un tableur l'écrit. */
    public function test_les_espaces_de_milliers_sont_toleres(): void
    {
        $fichier = $this->fichier("categorie;poids_max;cotonou;peripherie;interieur\nColis; 1 ;800;1 500;2\u{202F}500\n");
        $lignes = app(GridFile::class)->lire($fichier);

        $this->assertSame([DeliveryZone::COTONOU => 800, DeliveryZone::PERIPHERIE => 1500, DeliveryZone::INTERIEUR => 2500], $lignes[0]['montants']);
    }

    // ---- Une seule source avec le jeu pilote -----------------------------------

    public function test_le_jeu_pilote_pose_la_grille_du_meme_fichier(): void
    {
        app(PiloteDataset::class)->seed($this->companyId);

        $this->assertSame(self::DEPART, $this->grilleEnBase(), 'le jeu pilote lit le fichier, pas une copie');

        // Et la source ne garde aucune copie des montants : une grille écrite en
        // dur dans `PiloteDataset` était la dérive que S72 ferme.
        $source = file_get_contents(app_path('Services/Pilote/PiloteDataset.php'));
        $this->assertStringContainsString('GridFile::DEFAUT', $source);
        $this->assertDoesNotMatchRegularExpression('/const\s+GRID\b/', $source);
    }

    /** Le jeu pilote, lui aussi, laisse le dernier mot à l'écran sur une société déjà tarifée. */
    public function test_le_jeu_pilote_ne_reecrit_pas_un_montant_ajuste(): void
    {
        $this->artisan('beninlink:zones-tarifaires', $this->arguments())->assertSuccessful();
        $interieur = DeliveryZone::where('company_id', $this->companyId)->where('code', DeliveryZone::INTERIEUR)->firstOrFail();
        $ligne = $this->lignes()->where('zone_id', $interieur->id)->where('weight', 10)->firstOrFail();
        $ligne->amount = 7000;
        $ligne->save();

        app(PiloteDataset::class)->seed($this->companyId);

        $this->assertSame(7000, (int) $ligne->fresh()->amount);
    }

    // ---- Outils ---------------------------------------------------------------

    private function arguments(string $grille = GridFile::DEFAUT): array
    {
        return ['--societe' => $this->companyId, '--supplement' => 300, '--installer' => true, '--grille' => $grille];
    }

    private function retirerLesZones(): void
    {
        DeliveryCharge::where('company_id', $this->companyId)->delete();
        DeliveryZone::where('company_id', $this->companyId)->delete();
    }

    private function lignes()
    {
        $categorieId = DeliveryCategory::where('company_id', $this->companyId)->where('title', self::CATEGORIE)->value('id') ?? 0;

        return DeliveryCharge::where('company_id', $this->companyId)->where('category_id', $categorieId);
    }

    /** La grille en base, dans la forme de `DEPART` : poids => [Cotonou, Périphérie, Intérieur]. */
    private function grilleEnBase(): array
    {
        $zones = DeliveryZone::where('company_id', $this->companyId)->whereIn('code', self::NATIONALES)->get()->keyBy('code');
        $grille = [];
        foreach ($this->lignes()->get() as $ligne) {
            $rang = array_search($zones->firstWhere('id', $ligne->zone_id)?->code, self::NATIONALES, true);
            $this->assertNotFalse($rang, 'ligne hors des zones nationales');
            $grille[(int) $ligne->weight][$rang] = (int) $ligne->amount;
        }
        ksort($grille);
        foreach ($grille as &$montants) {
            ksort($montants);
        }

        return $grille;
    }

    private function fichier(string $contenu): string
    {
        $chemin = tempnam(sys_get_temp_dir(), 'grille-') . '.csv';
        file_put_contents($chemin, $contenu);
        $this->temporaires[] = $chemin;

        return $chemin;
    }
}
