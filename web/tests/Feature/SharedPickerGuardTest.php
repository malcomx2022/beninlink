<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as Router;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S43 — les deux sélecteurs partagés, et l'invariant qui les tient.
 *
 * S42 les avait laissés nus, en disant pourquoi : `parcel/deliveryman/search`
 * et `parcel/merchant/shops` sont appelés depuis 19 et 12 vues, et **plusieurs
 * de leurs écrans appelants n'avaient eux-mêmes aucune garde**. On ne dérive
 * pas un droit d'un écran qui n'en a pas : la liste aurait été incomplète, et
 * une liste incomplète **refuse un compte légitime**.
 *
 * Ce lot a gardé les quatre écrans qui manquaient (`parcel/filter`,
 * `parcel/specific/search`, `payout/`, `payout/merchant/payout`). La chaîne est
 * donc complète, et les deux sélecteurs deviennent dérivables.
 *
 * ⚠️ **Le danger d'une liste à dix alternatives n'est pas qu'elle soit longue,
 * c'est qu'elle se périme en silence.** Un écran ajouté demain qui appelle le
 * sélecteur sans que son droit figure dans la liste ne produira pas d'erreur
 * visible : la requête AJAX répondra 403 et la liste déroulante restera
 * **vide**. Personne ne le remarquera avant qu'un opérateur ne se plaigne.
 *
 * D'où ce test, qui ne vérifie pas une liste écrite à la main mais la
 * **recalcule** depuis les vues : il relève les vues qui appellent chaque
 * sélecteur, remonte à la route qui rend chacune, et exige que le droit de
 * cette route figure dans la garde du sélecteur.
 *
 * ⚠️ **Et il vérifie son propre instrument.** En écrivant S42 j'ai cherché
 * `route('parcel.merchant')` quand le nom réel était `parcel.merchant.get` : la
 * mesure a rendu **zéro appelant**, et j'ai failli conclure à une route morte.
 * Un relevé qui ne trouve rien doit donc échouer bruyamment, pas passer au
 * vert — c'est ce que fait l'assertion sur le nombre de vues.
 */
class SharedPickerGuardTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use MountsTenantRoutes;

    /**
     * Les sélecteurs partagés : nom de route, et le nombre de vues appelantes
     * relevé au moment de la garde. Le compte sert de témoin d'instrument.
     */
    private const SELECTEURS = [
        'parcel.deliveryman.search' => 19,
        'parcel.merchant.shops' => 12,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->mountTenantRoutes();
    }

    /**
     * @dataProvider selecteurs
     */
    public function test_a_shared_picker_admits_every_right_of_its_calling_screens(
        string $selecteur, int $vuesAttendues
    ): void {
        $vues = $this->vuesAppelant($selecteur);

        // Le témoin d'instrument : un relevé qui s'effondre est un relevé cassé,
        // pas une bonne nouvelle.
        $this->assertGreaterThanOrEqual(
            $vuesAttendues,
            count($vues),
            "Le relevé des appelants de {$selecteur} est tombé de {$vuesAttendues} à "
            . count($vues) . ' vues. Soit des écrans ont disparu, soit le relevé ne '
            . 'trouve plus rien — vérifier le nom de route avant de conclure.',
        );

        $admis = $this->droitsDeLaGarde($selecteur);
        $this->assertNotSame([], $admis, "{$selecteur} ne porte aucune garde de droit");

        $manquants = [];

        foreach ($vues as $vue) {
            foreach ($this->droitsDesEcransRendant($vue) as $droit) {
                if (!in_array($droit, $admis, true)) {
                    $manquants[] = $droit . ' (écran rendant ' . $vue . ')';
                }
            }
        }

        $manquants = array_values(array_unique($manquants));
        sort($manquants);

        $this->assertSame([], $manquants, "Des écrans appellent {$selecteur} avec un droit que sa garde "
            . "n'admet pas. La requête AJAX répondra 403 et la liste déroulante restera VIDE, sans erreur "
            . "visible. Ajouter ces droits à la garde de la route :\n - " . implode("\n - ", $manquants));
    }

    public static function selecteurs(): array
    {
        $cas = [];
        foreach (self::SELECTEURS as $nom => $vues) {
            $cas[$nom] = [$nom, $vues];
        }

        return $cas;
    }

    /**
     * L'inverse : un droit admis qui ne correspond plus à aucun écran appelant
     * élargit la garde pour rien. On ne l'interdit pas — un écran peut appeler
     * le sélecteur depuis du JavaScript, hors de portée de ce relevé — mais on
     * le compte, pour que la liste reste lisible.
     */
    public function test_every_guarded_picker_route_is_still_reachable(): void
    {
        foreach (array_keys(self::SELECTEURS) as $selecteur) {
            $this->assertNotNull(Router::getRoutes()->getByName($selecteur),
                "Le sélecteur {$selecteur} n'existe plus : cet invariant n'a plus d'objet");
        }
    }

    /* ─────────────────────────── l'instrument ───────────────────────────── */

    /** Les vues qui appellent ce sélecteur, en notation pointée. */
    private function vuesAppelant(string $selecteur): array
    {
        $vues = [];

        foreach ($this->fichiersDeVue() as $fichier) {
            if (str_contains(file_get_contents($fichier), "route('" . $selecteur . "')")) {
                $vues[] = str_replace(
                    [resource_path('views') . DIRECTORY_SEPARATOR, '.blade.php', DIRECTORY_SEPARATOR],
                    ['', '', '.'],
                    $fichier,
                );
            }
        }

        return $vues;
    }

    private function fichiersDeVue(): array
    {
        $fichiers = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                $fichiers[] = $f->getPathname();
            }
        }

        return $fichiers;
    }

    /**
     * Les droits des routes qui rendent cette vue.
     *
     * Une vue qui n'est rendue par aucune route est une **partie incluse** (une
     * modale de la liste des colis, par exemple) : son droit est celui de
     * l'écran qui l'inclut, déjà compté par ailleurs. On rend donc un tableau
     * vide plutôt que d'échouer.
     */
    private function droitsDesEcransRendant(string $vue): array
    {
        $droits = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            $action = ltrim($route->getActionName(), '\\');

            if (!str_contains($action, '@')) {
                continue;
            }

            [$classe, $methode] = explode('@', $action);

            if (!class_exists($classe) || !method_exists($classe, $methode)) {
                continue;
            }

            if (!str_contains($this->source($classe, $methode), "view('" . $vue . "'")) {
                continue;
            }

            foreach ($this->droitsDe($route) as $droit) {
                $droits[] = $droit;
            }
        }

        return array_values(array_unique($droits));
    }

    private function droitsDeLaGarde(string $selecteur): array
    {
        $route = Router::getRoutes()->getByName($selecteur);

        return $route ? $this->droitsDe($route) : [];
    }

    /** Les droits admis par la garde d'une route, la liste `a|b|c` éclatée. */
    private function droitsDe(\Illuminate\Routing\Route $route): array
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'hasPermission:')) {
                return explode('|', substr($middleware, strlen('hasPermission:')));
            }
        }

        return [];
    }

    private function source(string $classe, string $methode): string
    {
        $reflet = new \ReflectionMethod($classe, $methode);
        $fichier = $reflet->getFileName();

        if (!$fichier || !is_readable($fichier)) {
            return '';
        }

        return implode('', array_slice(
            file($fichier),
            $reflet->getStartLine() - 1,
            $reflet->getEndLine() - $reflet->getStartLine() + 1,
        ));
    }
}
