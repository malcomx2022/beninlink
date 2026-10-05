<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Api\V10\DeliverymanController;
use App\Services\OpenApi\SpecGenerator;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * S85 — le contrat de l'app LIVREUR.
 *
 * Tous les filets de contrat entre `web/` et les apps regardaient l'app marchand :
 * `OpenApiSpecTest` compare l'inventaire de `mobile/` à la spec, `ParcelStageTest`
 * lit sa copie des statuts, `MerchantAppCustomsContractTest` ses tailles de page.
 * L'app livreur n'avait rien : un endpoint renommé côté `web/` l'aurait cassée en
 * silence, là où l'app marchand aurait eu un test rouge. Elle est hors ligne 11,
 * mais elle part en recette avec l'autre.
 *
 * Cinq propriétés, chacune mesurée avant d'être écrite :
 *
 *  1. chaque endpoint de son inventaire existe dans la spec, et n'est jamais
 *     réservé au type MARCHAND (`x-user-type`, S5) ;
 *  2. l'app appelle ce qu'elle inventorie — les entrées sans appelant sont
 *     nommées, pas tolérées en silence ;
 *  3. les issues qu'un livreur peut déclarer sont EXACTEMENT celles du serveur :
 *     la table de l'app, le catalogue `ApiParcelStatus` et le `switch` du
 *     contrôleur disent les trois mêmes constantes ;
 *  4. aucune route livreur ne pagine — l'app n'a donc pas de `page` à lire, et
 *     ce test le dira le jour où ça change ;
 *  5. la répétition de recette appelle l'inventaire, et ce qu'elle n'appelle pas
 *     est listé ici.
 */
class DeliverymanAppContractTest extends TestCase
{
    private const INVENTAIRE = '../mobile-livreur/src/api/endpoints.ts';

    /**
     * Entrées de l'inventaire qu'aucun écran ni module de l'app n'appelle
     * aujourd'hui (mesuré par `grep endpoints\.<clé>` hors du fichier lui-même).
     *
     * Elles restent dans l'inventaire parce qu'il documente le tag « Livreur » de
     * la spec, pas seulement ce que l'app consomme. Mais une entrée qui perd son
     * appelant, ou un appel qui perd son entrée, doit se voir : d'où la liste.
     *
     *  - `refresh` : l'app livreur ne rafraîchit pas son jeton (elle se
     *    reconnecte) — à relire si une session longue le demande ;
     *  - `parcelIndex` : la liste des onglets vient du `dashboard` ;
     *  - `parcelPartialDelivered` : la livraison partielle passe par
     *    `parcel-status-update` avec le montant encaissé ;
     *  - `parcelStatuses` : les trois issues sont codées dans l'app (propriété 3) ;
     *  - `paymentLogs` : l'écran des gains lit `income-expense` et
     *    `parcel-payment-logs`.
     */
    private const SANS_APPELANT = ['refresh', 'parcelIndex', 'parcelPartialDelivered', 'parcelStatuses', 'paymentLogs'];

    /**
     * Endpoints de l'inventaire que `RecettePiloteRepetitionTest` n'appelle pas.
     * Les trois premiers sont les entrées sans appelant ci-dessus ; les quatre
     * autres sont des routes communes aux deux apps, exercées par leurs propres
     * tests (`ApiUserTypeTest`, `PushNotificationTest`, `TenantIsolationTest`).
     */
    private const HORS_REPETITION = [
        'deliveryman/parcel/index', 'deliveryman/parcel-status', 'deliveryman/payment-logs',
        'refresh', 'sign-out', 'push/register', 'push/forget',
    ];

    /** Les trois issues qu'un livreur déclare, telles que `reportOutcome()` les code. */
    private const ISSUES = ['DELIVERED', 'PARTIAL_DELIVERED', 'RETURN_TO_COURIER'];

    /* ─────────────────────────────── lecture ────────────────────────────── */

    private function livreur(string $chemin): string
    {
        $fichier = base_path('../mobile-livreur/' . $chemin);
        $this->assertFileExists($fichier, "mobile-livreur/{$chemin}");

        return file_get_contents($fichier);
    }

    /** `clé => chemin` de l'inventaire, un `${…}` devenant `{param}`. */
    private function inventaire(): array
    {
        $source = $this->livreur('src/api/endpoints.ts');
        $bloc = substr($source, strpos($source, 'export const endpoints'));
        $bloc = substr($bloc, 0, strpos($bloc, '} as const;'));

        $entrees = [];
        preg_match_all("/^\s*(\w+):\s*'([^']+)'/m", $bloc, $simples, PREG_SET_ORDER);
        preg_match_all('/^\s*(\w+):\s*\([^)]*\)\s*=>\s*`([^`]+)`/m', $bloc, $parametres, PREG_SET_ORDER);

        foreach ($simples as [, $cle, $chemin]) {
            $entrees[$cle] = $chemin;
        }
        foreach ($parametres as [, $cle, $chemin]) {
            $entrees[$cle] = preg_replace('/\$\{[^}]+\}/', '{param}', $chemin);
        }

        $this->assertGreaterThanOrEqual(15, count($entrees), 'l\'inventaire a changé de forme : ce test ne le lit plus');

        return $entrees;
    }

    /** Tous les fichiers TypeScript de l'app, hors l'inventaire lui-même et les tests. */
    private function sourcesDeLapp(): string
    {
        $tout = '';
        foreach (['src', 'app'] as $racine) {
            $iterateur = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('../mobile-livreur/' . $racine)));
            foreach ($iterateur as $f) {
                if (!in_array($f->getExtension(), ['ts', 'tsx'], true)) {
                    continue;
                }
                if ($f->getBasename() === 'endpoints.ts' || str_contains($f->getBasename(), '.test.')) {
                    continue;
                }
                $tout .= file_get_contents($f->getPathname());
            }
        }

        return $tout;
    }

    /** Les routes `/api/v10` montées derrière `userType:deliveryman`. */
    private function routesLivreur(): array
    {
        $routes = [];
        foreach (Router::getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            if (Str::startsWith($route->uri(), 'api/') && in_array('userType:deliveryman', $route->gatherMiddleware(), true)) {
                $routes[] = $route;
            }
        }
        $this->assertGreaterThanOrEqual(10, count($routes), 'les routes livreur ne sont plus derrière `userType:deliveryman`');

        return $routes;
    }

    private function source(\ReflectionMethod $m): string
    {
        $lignes = file($m->getFileName());

        return implode('', array_slice($lignes, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1));
    }

    /* ───────── 1. l'inventaire existe dans la spec, et pas au type marchand ── */

    public function test_the_courier_inventory_matches_the_spec_and_is_never_merchant_only(): void
    {
        $spec = (new SpecGenerator())->generate();
        $connus = [];
        foreach ($spec['paths'] as $chemin => $operations) {
            $connus[preg_replace('/\{[^}]+\}/', '{param}', $chemin)] = $operations;
        }

        foreach ($this->inventaire() as $cle => $chemin) {
            $this->assertArrayHasKey('/' . $chemin, $connus, "l'app livreur appelle `{$cle}` → {$chemin}, absent de l'API");

            foreach ($connus['/' . $chemin] as $verbe => $operation) {
                $type = $operation['x-user-type'] ?? null;
                if ($type === null) {
                    continue; // route commune aux deux apps
                }
                $this->assertContains('deliveryman', $type,
                    "`{$cle}` ({$verbe} {$chemin}) est réservé au type " . implode(',', $type) . " : l'app livreur recevrait 403 (S5)");
            }
        }
    }

    /* ───────── 2. l'app appelle ce qu'elle inventorie ─────────────────────── */

    public function test_the_app_calls_what_it_inventories_and_names_what_it_does_not(): void
    {
        $inventaire = $this->inventaire();
        preg_match_all('/endpoints\.(\w+)/', $this->sourcesDeLapp(), $m);
        $appelees = array_unique($m[1]);

        $inconnues = array_diff($appelees, array_keys($inventaire));
        $this->assertSame([], array_values($inconnues), 'l\'app appelle des clés absentes de l\'inventaire : ' . implode(', ', $inconnues));

        $sansAppelant = array_values(array_diff(array_keys($inventaire), $appelees));
        sort($sansAppelant);
        $attendues = self::SANS_APPELANT;
        sort($attendues);
        $this->assertSame($attendues, $sansAppelant,
            'les entrées de l\'inventaire sans appelant ont changé : mettre SANS_APPELANT à jour avec le motif');
    }

    /* ───────── 3. les issues du livreur : app, catalogue, contrôleur ───────── */

    public function test_the_outcomes_a_courier_can_declare_are_exactly_the_servers(): void
    {
        // L'app : la table de `reportOutcome()`.
        $module = $this->livreur('src/api/deliveryman.ts');
        preg_match_all('/BackendParcelStatus\.([A-Z_]+)/', substr($module, strpos($module, 'export async function reportOutcome')), $m);
        $app = array_unique($m[1]);
        sort($app);

        // Le catalogue que sert `deliveryman/parcel-status`.
        $catalogue = array_keys(trans('ApiParcelStatus'));
        sort($catalogue);

        // Le `switch` du contrôleur.
        preg_match_all('/case\s+ParcelStatus::([A-Z_]+):/', $this->source(new \ReflectionMethod(DeliverymanController::class, 'parcelStatusUpdate')), $c);
        $controleur = array_unique($c[1]);
        sort($controleur);

        $attendues = self::ISSUES;
        sort($attendues);
        $codes = array_map(fn ($nom) => constant(ParcelStatus::class . '::' . $nom), $attendues);
        sort($codes);

        $this->assertSame($attendues, $app, 'l\'app livreur code d\'autres issues que les trois du contrat');
        $this->assertSame($attendues, $controleur, 'le contrôleur accepte d\'autres issues que celles que l\'app connaît');
        $this->assertSame($codes, $catalogue, '`deliveryman/parcel-status` annonce d\'autres issues que celles que le contrôleur accepte');
    }

    /* ───────── 4. aucune route livreur ne pagine ───────────────────────────── */

    public function test_no_courier_route_paginates_so_the_app_has_no_page_to_read(): void
    {
        $paginent = [];

        foreach ($this->routesLivreur() as $route) {
            [$classe, $methode] = explode('@', ltrim($route->getActionName(), '\\'));
            $source = $this->source(new \ReflectionMethod($classe, $methode));
            $textes = [$classe . '::' . $methode => $source];

            // Un niveau de dépôt, résolu sur la propriété réelle (forme de S38).
            $controleur = app($classe);
            $reflet = new \ReflectionObject($controleur);
            preg_match_all('/\$this->(\w+)->(\w+)\s*\(/', $source, $appels, PREG_SET_ORDER);
            foreach ($appels as [, $propriete, $appelee]) {
                if (!$reflet->hasProperty($propriete)) {
                    continue;
                }
                $depot = $reflet->getProperty($propriete)->getValue($controleur);
                if (is_object($depot) && method_exists($depot, $appelee)) {
                    $textes[get_class($depot) . '::' . $appelee] = $this->source(new \ReflectionMethod($depot, $appelee));
                }
            }

            foreach ($textes as $nom => $texte) {
                if (str_contains($texte, 'paginate(')) {
                    $paginent[] = $route->uri() . ' → ' . $nom;
                }
            }
        }

        $this->assertSame([], $paginent, "Une route livreur pagine : l'app livreur ne lit pas `page` (S78), elle perdrait des lignes en silence. "
            . "L'inscrire dans ApiPaginationContractTest::PAGINEES, répondre par responseWithPage(), et faire lire `page` à l'app :\n - " . implode("\n - ", $paginent));
    }

    /* ───────── 5. la répétition de recette et l'inventaire ─────────────────── */

    public function test_the_recette_rehearsal_exercises_the_inventory_except_what_is_named_here(): void
    {
        $repetition = file_get_contents(base_path('tests/Feature/RecettePiloteRepetitionTest.php'));

        $nonAppelees = [];
        foreach ($this->inventaire() as $chemin) {
            // ⚠️ Un préfixe ne suffit pas : `deliveryman/parcel-status` est le début
            // de `deliveryman/parcel-status-update`, et le premier jet de ce test
            // croyait la liste des statuts jouée parce que la mise à jour l'était.
            $racine = Str::before($chemin, '{param}');
            if (!preg_match('#/api/v10/' . preg_quote($racine, '#') . '(?![A-Za-z0-9_-])#', $repetition)) {
                $nonAppelees[] = $chemin;
            }
        }
        sort($nonAppelees);
        $attendues = self::HORS_REPETITION;
        sort($attendues);

        $this->assertSame($attendues, $nonAppelees,
            'la couverture de l\'inventaire livreur par la répétition de recette a changé : mettre HORS_REPETITION à jour (un endpoint nouveau se joue dans la répétition, ou se motive ici)');
    }
}
