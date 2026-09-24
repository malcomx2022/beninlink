<?php

namespace Tests\Feature;

use App\Models\Backend\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S56 — le filet de la surface HORS REQUÊTE.
 *
 * Les quatre filets existants (`WebIsolationCoverageTest`,
 * `BodyIdentifierCoverageTest`, `IsolationCoverageTest`,
 * `WebAdminPermissionCoverageTest`) énumèrent tous des **routes** : ils appellent
 * `Route::getRoutes()`. Ce qui tourne **sans requête HTTP** leur est donc
 * entièrement invisible — commandes Artisan, tâches planifiées, jobs en file,
 * observateurs de modèles, notifications.
 *
 * Or c'est précisément là que vit une famille de défauts que ce dépôt a déjà
 * nommée : **F4**.
 *
 * ## F4, mesuré et non supposé
 *
 * `scopeCompanywise()` est `where('company_id', settings()->id)`. Et `settings()`
 * résout la société par le **sous-domaine** ou l'**utilisateur connecté** ;
 * faute des deux, elle retombe sur la société **1** :
 *
 * ```
 * [societes actives : 1, 2]
 * [settings()->id hors requete : 1]
 * ```
 *
 * Une commande qui écrit `Model::companywise()` ne traite donc que la première
 * société, **sans erreur ni alerte**. La société 2 est silencieusement invisible.
 * `test_the_ambient_company_falls_back_to_the_first_one` ci-dessous inscrit ce
 * mécanisme : c'est la prémisse de tout le filet, et il mord si elle change.
 *
 * ## F4 a déjà mordu deux fois
 *
 * - `invoice:generate` bouclait sur `merchantIdlist()`, filtré par
 *   `settings()->id` : la génération quotidienne ne servait **que** la société 1,
 *   et les autres transporteurs n'avaient **jamais** de relevé ;
 * - `SendSms` aurait envoyé au nom du mauvais transporteur si le job ne portait
 *   pas sa société.
 *
 * Les deux sont corrigés, et leurs docblocs expliquent la règle. Mais **rien
 * n'empêchait la troisième fois** : aucun filet ne regardait cette surface.
 *
 * ## Ce que ce filet exige
 *
 * Sur la surface hors requête, aucune résolution **ambiante** du périmètre :
 * ni `settings()`, ni `companywise()`, ni `Auth::`/`auth()` — qui n'a pas
 * davantage d'utilisateur connecté à lire. La société se prend **explicitement** :
 * lue dans une liste, passée en option, ou dérivée de la **ligne** traitée.
 *
 * C'est la règle que `Invoice` énonce déjà pour elle-même ; ce filet l'étend à
 * toute la surface, et la rend impossible à oublier.
 *
 * ⚠️ **Les commentaires sont retirés avant l'examen.** `Invoice` et `SendSms`
 * *citent* `settings()` dans leur docbloc pour expliquer le piège : un filet qui
 * lirait le fichier brut signalerait exactement les deux fichiers qui
 * documentent la règle. La source est donc passée au **tokenizer** PHP et
 * dépouillée de ses commentaires.
 */
class OffRequestScopeCoverageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    /** Les répertoires qui tournent sans requête HTTP. */
    private const SURFACE = [
        'app/Console/Commands',
        'app/Jobs',
        'app/Observers',
        'app/Notifications',
    ];

    /**
     * Les marques d'une résolution AMBIANTE du périmètre.
     *
     * `auth()` et `Auth::` y figurent pour la même raison que `settings()` :
     * hors requête il n'y a pas d'utilisateur connecté, donc `auth()->user()`
     * est `null` et tout ce qui en dépend part de travers — silencieusement si
     * le résultat est utilisé comme identifiant.
     */
    private const MARQUES_AMBIANTES = ['settings()', 'companywise(', 'Auth::', 'auth()'];

    /**
     * Les fichiers autorisés à en porter une, avec leur motif.
     *
     * Vide à l'ouverture : la surface est saine au moment d'écrire ce filet.
     * C'est le bon moment pour le poser — pas quand elle ne l'est plus.
     */
    private const EXEMPTEES = [
        // 'app/Console/Commands/Exemple.php' => 'motif',
    ];

    /* ─────────────────────── la prémisse, mesurée ──────────────────────────── */

    /**
     * ⚠️ LE test de ce filet, parce qu'il porte la raison des autres.
     *
     * Si `settings()` cessait de retomber sur la société 1 hors requête — parce
     * qu'elle lèverait, ou prendrait une société explicite — alors la règle que
     * ce filet applique n'aurait plus le même fondement, et il faudrait la
     * relire plutôt que la reconduire.
     */
    public function test_the_ambient_company_falls_back_to_the_first_one(): void
    {
        $this->seedTenant();

        $actives = GeneralSettings::orderBy('id')->pluck('id')->all();

        $this->assertGreaterThan(1, count($actives),
            'ce test ne prouve rien avec une seule société : il lui en faut au moins deux '
            . 'pour que « retomber sur la première » soit distinguable de « prendre la bonne »');

        // Aucune requête, aucun utilisateur connecté, aucun sous-domaine : le cas
        // exact d'une commande Artisan et d'un job en file.
        $this->assertSame(
            (int) $actives[0],
            (int) settings()->id,
            'hors requête, `settings()` ne retombe plus sur la première société : la prémisse '
            . 'de ce filet a changé, le relire avant de le reconduire',
        );

        // Et la conséquence, dite explicitement : les autres sociétés sont invisibles.
        $this->assertNotContains((int) settings()->id, array_slice($actives, 1),
            'la société ambiante hors requête est une SEULE société, pas toutes');
    }

    /* ──────────────────── le filet : aucune marque ambiante ────────────────── */

    public function test_no_off_request_file_resolves_the_company_ambiently(): void
    {
        $fautifs = [];

        foreach ($this->fichiersDeLaSurface() as $chemin) {
            if (array_key_exists($chemin, self::EXEMPTEES)) {
                continue;
            }

            $code = $this->sourceSansCommentaires(base_path($chemin));

            foreach (self::MARQUES_AMBIANTES as $marque) {
                if (str_contains($code, $marque)) {
                    $fautifs[] = $chemin . ' → ' . $marque;
                }
            }
        }

        sort($fautifs);

        $this->assertSame([], $fautifs,
            "Résolution AMBIANTE du périmètre sur la surface hors requête (F4).\n\n"
            . "Hors requête, `settings()` retombe sur la société 1 : ce code ne traite donc "
            . "qu'une société, sans erreur ni alerte. Prendre la société EXPLICITEMENT — lue "
            . "dans une liste, passée en option, ou dérivée de la ligne traitée — comme "
            . "`invoice:generate` le fait. Si l'usage est légitime, l'inscrire dans EXEMPTEES "
            . "avec son motif :\n - " . implode("\n - ", $fautifs));
    }

    /**
     * Et l'inverse : une exemption qui ne correspond plus à rien s'en va.
     *
     * ⚠️ L'assertion est écrite pour tenir **même quand `EXEMPTEES` est vide** —
     * et elle l'est aujourd'hui. Une boucle `foreach` sur une liste vide
     * n'exécute aucune assertion : PHPUnit l'aurait marqué *risky*, et le test
     * n'aurait rien prouvé. C'est la même famille de piège que les assertions
     * d'absence des lots S51 à S55 : on compare donc des ENSEMBLES, ce qui
     * assertit une fois quoi qu'il arrive.
     */
    public function test_no_exemption_points_to_a_file_that_no_longer_exists(): void
    {
        $fantomes = array_values(array_diff(
            array_keys(self::EXEMPTEES),
            $this->fichiersDeLaSurface(),
        ));

        $this->assertSame([], $fantomes,
            "EXEMPTEES déclare des fichiers qui n'existent plus :\n - " . implode("\n - ", $fantomes));
    }

    /**
     * Le témoin de l'énumération elle-même : si la surface devenait vide — un
     * répertoire renommé, un glob cassé — le filet passerait au vert sans rien
     * examiner. C'est le piège des assertions d'absence, rencontré cinq fois
     * dans les lots S51 à S55.
     */
    public function test_the_surface_is_not_empty(): void
    {
        $fichiers = $this->fichiersDeLaSurface();

        $this->assertGreaterThanOrEqual(20, count($fichiers),
            'la surface hors requête énumérée est suspecte : un répertoire a-t-il été '
            . 'renommé ? Sans fichiers, le filet ne mesure rien.');

        // Les deux fichiers que F4 a déjà mordus doivent en faire partie.
        $this->assertContains('app/Console/Commands/Invoice.php', $fichiers);
        $this->assertContains('app/Jobs/SendSms.php', $fichiers);
    }

    /* ────────────────────────────── outillage ──────────────────────────────── */

    /** @return string[] chemins relatifs à la racine du projet, triés */
    private function fichiersDeLaSurface(): array
    {
        $fichiers = [];

        foreach (self::SURFACE as $repertoire) {
            $absolu = base_path($repertoire);

            if (! is_dir($absolu)) {
                continue;
            }

            $iterateur = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolu, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterateur as $fichier) {
                if ($fichier->getExtension() === 'php') {
                    $fichiers[] = $repertoire . '/' . ltrim(
                        str_replace($absolu, '', $fichier->getPathname()), '/',
                    );
                }
            }
        }

        sort($fichiers);

        return $fichiers;
    }

    /**
     * La source privée de ses commentaires.
     *
     * ⚠️ Indispensable, et pas une précaution théorique : `Invoice` et `SendSms`
     * citent `settings()` dans leur docbloc pour expliquer F4. Une lecture brute
     * signalerait les deux fichiers qui documentent la règle — le filet
     * accuserait la documentation au lieu du code.
     */
    private function sourceSansCommentaires(string $chemin): string
    {
        $code = '';

        foreach (token_get_all(file_get_contents($chemin)) as $jeton) {
            if (is_array($jeton)) {
                if (in_array($jeton[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $code .= $jeton[1];
                continue;
            }
            $code .= $jeton;
        }

        return $code;
    }
}
