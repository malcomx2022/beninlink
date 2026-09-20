<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Migration Bootstrap 4 → 5 — l'inventaire, et les deux premières étapes.
 *
 * Le chantier est resté **hors lot** de la charte web du début à la fin, et
 * c'était juste : il change l'apparence de chaque écran du back-office, alors
 * qu'aucun des sept lots n'a fait de vérification visuelle.
 *
 * Ce que ces tests tiennent n'est donc pas la migration, mais sa PRÉPARATION :
 *
 *  1. **L'inventaire ne pourrit pas.** L'audit annonçait « 217 `data-toggle` ».
 *     C'est vrai, et c'est la plus petite des trois mesures qui comptent. Les
 *     bornes ci-dessous sont larges à dessein — elles ne figent pas un chiffre,
 *     elles empêchent le plan de décrire un dépôt qui n'existe plus.
 *  2. **Les deux étapes sans risque visuel sont faites**, et le restent.
 *
 * Voir `docs/guides/bootstrap/migration-4-vers-5.md`.
 */
class BootstrapMigrationTest extends TestCase
{
    /** @return list<string> */
    private function vues(): array
    {
        $racine = resource_path('views');
        $out = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                $out[] = $f->getPathname();
            }
        }

        return $out;
    }

    private function occurrences(string $motif): int
    {
        $n = 0;

        foreach ($this->vues() as $chemin) {
            $n += preg_match_all($motif, file_get_contents($chemin));
        }

        return $n;
    }

    private function backoffice(): string
    {
        return file_get_contents(public_path('beninlink/css/theme-backoffice.css'));
    }

    // — étape A : plus de CDN tiers pour Bootstrap 4 -------------------------

    /**
     * Le socle prenait la feuille Bootstrap 4 sur `maxcdn.bootstrapcdn.com` en
     * **4.1.1**, pendant que le pied de page servait le script de la copie
     * LOCALE, en **4.1.0**. La feuille et le script n'étaient donc pas de la
     * même version — un décalage que personne n'avait relevé parce qu'il ne
     * produit rien de visible.
     *
     * La copie locale les réaligne, et retire un CDN de chaque page.
     */
    public function test_bootstrap_four_is_served_by_the_repository(): void
    {
        $entete = file_get_contents(resource_path('views/backend/partials/header.blade.php'));
        $pied = file_get_contents(resource_path('views/backend/partials/footer.blade.php'));

        // On interdit le CHARGEMENT, pas la mention : le commentaire de la vue
        // nomme le CDN pour dire de quoi on s'est défait, et pourquoi.
        $this->assertSame(
            0,
            preg_match_all('/(?:href|src)\s*=\s*"[^"]*maxcdn\.bootstrapcdn\.com/', $entete),
            'Bootstrap 4 ne doit plus venir d\'un CDN tiers'
        );
        $this->assertStringContainsString('vendor/bootstrap/css/bootstrap.min.css', $entete);

        // Les deux fichiers existent vraiment, et annoncent la même version.
        $css = public_path('backend/vendor/bootstrap/css/bootstrap.min.css');
        $js = public_path('backend/vendor/bootstrap/js/bootstrap.bundle.js');
        $this->assertFileExists($css);
        $this->assertFileExists($js);

        $version = static function (string $f): string {
            preg_match('/Bootstrap\s+v(\d+\.\d+\.\d+)/', file_get_contents($f, false, null, 0, 400), $m);

            return $m[1] ?? '?';
        };

        $this->assertSame(
            $version($css),
            $version($js),
            'la feuille et le script de Bootstrap 4 doivent être de la même version'
        );

        $this->assertStringContainsString('vendor/bootstrap/js/bootstrap.bundle.js', $pied);
    }

    // — étape B : le chevron du sidebar survivra au renommage ----------------

    /**
     * LE piège de cette migration, et il est silencieux.
     *
     * Le socle dessine la flèche des entrées de menu dépliables avec **neuf**
     * règles qui sélectionnent sur `[data-toggle="collapse"]`. Renommer les
     * attributs des vues en `data-bs-toggle` — ce que Bootstrap 5 exige — les
     * rend toutes inopérantes d'un coup : chaque sous-menu perd sa flèche et sa
     * rotation. Sans erreur, sans avertissement.
     *
     * Les neuf sont doublées dans la feuille de charte. Tant que les vues
     * portent l'ancien attribut, elles ne correspondent à rien : l'étape est
     * donc **réversible et invisible**.
     */
    public function test_the_sidebar_chevron_will_survive_the_rename(): void
    {
        $socle = file_get_contents(public_path('backend/libs/css/style.css'));

        $this->assertSame(
            9,
            preg_match_all('/\[data-toggle="collapse"\]/', $socle),
            'le socle dessine la flèche avec neuf sélecteurs — si ce nombre change, le doublage aussi'
        );

        $this->assertSame(
            9,
            preg_match_all('/\[data-bs-toggle="collapse"\]/', $this->backoffice()),
            'les neuf doivent être doublées dans la charte, une pour une'
        );

        // Et le doublage vise les mêmes familles, pas n'importe quoi.
        foreach ([
            '.nav-left-sidebar .nav-link[data-bs-toggle="collapse"]',
            '.navigation-horizontal .nav-link[data-bs-toggle="collapse"]',
            'html[dir="rtl"] .nav-left-sidebar .nav-link[data-bs-toggle="collapse"]::after',
        ] as $selecteur) {
            $this->assertStringContainsString($selecteur, $this->backoffice(), $selecteur);
        }
    }

    // — l'inventaire, pour que le plan ne décrive pas un dépôt disparu -------

    /**
     * Les trois mesures du guide. Les bornes sont **larges** : elles ne figent
     * pas un chiffre — elles disent « le plan parle encore de ce dépôt ».
     *
     * Un franchissement par le bas est une bonne nouvelle qui demande de
     * relire le guide ; par le haut, c'est que quelqu'un écrit du Bootstrap 4
     * neuf, et le guide dit pourquoi il ne faut pas.
     */
    public function test_the_inventory_the_guide_relies_on_still_holds(): void
    {
        // 1. Les attributs de comportement. L'audit n'avait compté que `toggle`.
        $attributs = [
            'data-toggle' => [180, 260],
            'data-target' => [40, 90],
            'data-dismiss' => [30, 70],
        ];

        foreach ($attributs as $attribut => [$bas, $haut]) {
            $n = $this->occurrences('/\b' . preg_quote($attribut, '/') . '=/');
            $this->assertGreaterThanOrEqual($bas, $n, "{$attribut} : le guide en annonce davantage");
            $this->assertLessThanOrEqual($haut, $n, "{$attribut} : quelqu'un écrit du Bootstrap 4 neuf");
        }

        // 2. L'API jQuery de Bootstrap, que la version 5 supprime. Elle est
        //    RARE — six appels — et c'est la bonne nouvelle du relevé.
        $jquery = 0;
        foreach (['public/backend/js', 'public/backend/libs/js', 'public/frontend/js'] as $d) {
            foreach (glob(public_path(str_replace('public/', '', $d)) . '/{,*/}*.js', GLOB_BRACE) ?: [] as $f) {
                $jquery += preg_match_all(
                    '/\$\([^)]*\)\.(modal|tooltip|popover|collapse|tab|dropdown|carousel|toast)\(/',
                    file_get_contents($f)
                );
            }
        }
        $this->assertLessThanOrEqual(
            15,
            $jquery,
            'Bootstrap 5 supprime l\'API jQuery : chaque appel de plus est une reprise de plus'
        );
    }

    /**
     * Les deux Bootstrap cohabitent encore, et c'est **voulu** tant que la
     * migration n'est pas jouée. Ce test le dit à voix haute : le jour où l'un
     * des deux disparaît, il échoue, et celui qui l'a retiré lira le guide.
     */
    public function test_both_bootstraps_are_still_loaded_on_purpose(): void
    {
        $entete = file_get_contents(resource_path('views/backend/partials/header.blade.php'));
        $pied = file_get_contents(resource_path('views/backend/partials/footer.blade.php'));

        $this->assertStringContainsString('vendor/bootstrap-five/bootstrap.min.css', $entete);
        $this->assertStringContainsString('vendor/bootstrap/css/bootstrap.min.css', $entete);
        $this->assertStringContainsString('vendor/bootstrap-five/bootstrap.min.js', $pied);
        $this->assertStringContainsString('vendor/bootstrap/js/bootstrap.bundle.js', $pied);

        $guide = base_path('../docs/guides/bootstrap/migration-4-vers-5.md');
        $this->assertFileExists($guide, 'retirer un Bootstrap sans le guide, c\'est le faire à l\'aveugle');
    }
}
