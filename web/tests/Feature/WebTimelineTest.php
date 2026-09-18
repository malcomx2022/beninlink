<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Services\Parcel\ParcelStage;
use Tests\TestCase;

/**
 * Lot 7 de la charte web (2026-09-18) — la forme fine de la maquette.
 *
 * ┌─ CE QUE CE LOT A TROUVÉ ───────────────────────────────────────────────────┐
 * │ Le lot 2 avait réduit à UNE table les TROIS copies qui coloraient les      │
 * │ statuts. Il en restait une QUATRIÈME, et c'était la pire : un `@switch`    │
 * │ de onze branches dans `frontend/pages/tracking.blade.php` — la page du     │
 * │ **client final**.                                                          │
 * │                                                                            │
 * │ Elle ne couvrait que **10 codes sur 33**, et sa table était fausse : tout  │
 * │ ce qui n'était pas une « reprogrammation » recevait `#2ec551`, un vert vif │
 * │ qui n'est pas celui de la charte. Donc une livraison PARTIELLE — un        │
 * │ incident — s'affichait exactement comme une livraison réussie, et le repli │
 * │ des 23 codes non listés aussi.                                             │
 * └────────────────────────────────────────────────────────────────────────────┘
 */
class WebTimelineTest extends TestCase
{
    private function feuille(string $nom): string
    {
        return file_get_contents(public_path('beninlink/css/' . $nom));
    }

    private function suivi(): string
    {
        return file_get_contents(resource_path('views/frontend/pages/tracking.blade.php'));
    }

    /** La valeur d'un jeton `--bl-*`, telle que `tokens.css` la porte. */
    private function jeton(string $nom): string
    {
        $jetons = file_get_contents(public_path('beninlink/css/tokens.css'));

        $this->assertSame(
            1,
            preg_match('/' . preg_quote($nom, '/') . '\s*:\s*(#[0-9A-Fa-f]{6})\s*;/', $jetons, $m),
            "{$nom} doit être déclaré dans tokens.css"
        );

        return strtoupper($m[1]);
    }

    // — la quatrième copie n'existe plus -------------------------------------

    /**
     * LE test de ce lot. La vue ne décide plus d'aucune couleur : elle demande
     * sa famille à `ParcelStage`, la même table que le back-office et que
     * `mobile/`. Un client qui suit son colis sur le site et le marchand qui le
     * regarde dans l'application voient désormais le même stade.
     */
    public function test_the_public_timeline_no_longer_carries_its_own_status_table(): void
    {
        $vue = $this->suivi();

        $this->assertStringNotContainsString('@case', $vue, 'les onze branches doivent avoir disparu');
        $this->assertStringNotContainsString('@switch', $vue);
        $this->assertStringContainsString('ParcelStage::of($log->parcel_status)', $vue);

        // Et les deux classes du socle qui portaient la couleur ne sont plus posées.
        foreach (['cd-timeline__img--picture', 'cd-timeline__yellow'] as $classe) {
            $this->assertStringNotContainsString($classe, $vue, $classe);
        }
    }

    /**
     * Le défaut, prouvé par le contre-exemple : le socle peignait la livraison
     * partielle avec la MÊME classe que la livraison réussie. Ce n'est donc pas
     * une nuance de goût — c'est un incident montré comme une réussite, sur la
     * page que lit le destinataire du colis.
     */
    public function test_a_partial_delivery_is_no_longer_shown_as_a_success(): void
    {
        $this->assertSame(ParcelStage::PARTIAL, ParcelStage::of(ParcelStatus::PARTIAL_DELIVERED));
        $this->assertSame(ParcelStage::DONE, ParcelStage::of(ParcelStatus::DELIVERED));
        $this->assertTrue(ParcelStage::isIncident(ParcelStatus::PARTIAL_DELIVERED));

        // Deux familles distinctes ⇒ deux règles distinctes dans la feuille.
        $css = $this->feuille('theme-frontend.css');
        $this->assertStringContainsString('.bl-node--partial', $css);
        $this->assertStringContainsString('.bl-node--done', $css);

        $partiel = $this->couleursDuNoeud('partial');
        $livre = $this->couleursDuNoeud('done');
        $this->assertNotSame($partiel['fond'], $livre['fond'], 'partielle et livrée ne peuvent pas se confondre');
    }

    /**
     * Un code que le socle n'avait pas listé tombait dans le `@default`, peint
     * du même vert que « livré ». Il retombe désormais sur le NEUTRE : mieux
     * vaut une pastille grise qu'une réussite inventée.
     */
    public function test_an_unlisted_status_falls_back_to_neutral_not_to_green(): void
    {
        // Un code hors des dix que le socle listait.
        $this->assertSame(ParcelStage::WAIT, ParcelStage::of(ParcelStatus::PENDING));
        $this->assertSame(ParcelStage::WAIT, ParcelStage::of(99999));

        $neutre = $this->couleursDuNoeud('wait');
        $livre = $this->couleursDuNoeud('done');
        $this->assertNotSame($neutre['fond'], $livre['fond']);
    }

    // — les contrastes, recalculés -------------------------------------------

    /**
     * Chaque nœud est un aplat portant une icône. Les paires sont mesurées, pas
     * choisies à l'œil — c'est la règle que `tokens.css` pose depuis le lot 1.
     *
     * WCAG 2.1 demande 3:1 pour un élément graphique ; on vise **4,5:1**, comme
     * partout ailleurs dans ce dépôt, pour n'avoir qu'un seuil à retenir.
     */
    public function test_every_timeline_node_is_readable(): void
    {
        foreach (['wait', 'transit', 'hub', 'assign', 'done', 'partial', 'return', 'cancel'] as $famille) {
            $c = $this->couleursDuNoeud($famille);

            $this->assertGreaterThanOrEqual(
                4.5,
                $this->contraste($c['fond'], $c['encre']),
                "nœud « {$famille} » : {$c['encre']} sur {$c['fond']}"
            );
        }
    }

    /** Les huit familles de `ParcelStage`, plus l'annulation, ont toutes leur règle. */
    public function test_every_stage_of_the_service_has_a_node_rule(): void
    {
        $css = $this->feuille('theme-frontend.css');

        foreach (ParcelStage::families() as $famille) {
            $this->assertStringContainsString(
                ".bl-node--{$famille}",
                $css,
                "la famille « {$famille} » de ParcelStage n'a pas de couleur de nœud"
            );
        }

        // L'annulation n'est pas une famille : c'est un état qui se superpose.
        $this->assertStringContainsString('.bl-node--cancel', $css);
    }

    // — l'anglais en dur de la page publique ---------------------------------

    /**
     * Le socle collait le mot anglais « cancel » au libellé du statut, en dur,
     * dans un bloc `@php` — donc invisible pour l'audit du lot 4, qui lisait les
     * `placeholder` et les clés JSON. Sur la page du client final.
     */
    public function test_the_cancelled_suffix_is_translated(): void
    {
        $vue = $this->suivi();

        $this->assertStringNotContainsString("' cancel'", $vue);
        $this->assertStringContainsString("__('Cancelled')", $vue);

        $fr = json_decode(file_get_contents(lang_path('fr.json')), true);
        $this->assertArrayHasKey('Cancelled', $fr);
        $this->assertSame('Annulé', $fr['Cancelled']);
    }

    // — les cartes de synthèse ------------------------------------------------

    /**
     * La maquette veut une pastille d'icône de 34 px et un chiffre en Sora 800.
     * Le §6.6 prescrivait des classes `bl-kpi` **à poser dans les vues** ; les
     * noms du socle suffisent, et les requalifier coûte **zéro fichier** à la
     * carte de fusion. Ce test fige ce choix : si quelqu'un repose des classes
     * dans la vue, il verra d'abord passer ici.
     */
    public function test_the_summary_cards_are_restyled_without_touching_a_view(): void
    {
        $css = $this->feuille('theme-backoffice.css');

        $this->assertStringContainsString('.card.total-card-color', $css);
        $this->assertMatchesRegularExpression('/\.icon\s*\{[^}]*width:\s*34px/s', $css);
        $this->assertMatchesRegularExpression('/\.metric-value h1[^{]*\{[^}]*font-weight:\s*800/s', $css);

        // La vue du tableau de bord n'a PAS été retouchée pour cela.
        $vue = file_get_contents(resource_path('views/backend/dashboard.blade.php'));
        $this->assertStringNotContainsString('bl-kpi', $vue, 'le rendu vient de la feuille, pas de la vue');
        $this->assertStringContainsString('total-card-color', $vue, 'les noms du socle restent le point d\'accroche');
    }

    // — la police propriétaire ------------------------------------------------

    /**
     * `Circular Std` est sous licence propriétaire. La charte l'a remplacée dès
     * le lot 1, mais trois mises en page la téléchargeaient encore : 348 Ko de
     * fontes que rien ne peint, et une redistribution qui n'avait pas lieu
     * d'être.
     *
     * Les FICHIERS restent — la règle du projet est « 0 fichier supprimé du
     * socle ». C'est le `<link>` qui part.
     */
    public function test_the_proprietary_font_is_no_longer_served(): void
    {
        foreach ([
            'backend/partials/header.blade.php',
            'installer/index.blade.php',
            'errors/layout.blade.php',
        ] as $vue) {
            $source = file_get_contents(resource_path('views/' . $vue));

            $this->assertStringNotContainsString(
                'circular-std/style.css',
                $source,
                "{$vue} télécharge encore la police propriétaire"
            );
            $this->assertStringContainsString('Circular Std', $source, 'la vue doit dire pourquoi elle ne la charge plus');
        }

        // Les fichiers sont toujours là : on ne supprime rien du socle.
        $this->assertFileExists(public_path('backend/vendor/fonts/circular-std/style.css'));

        // Et `.table th` — le seul sélecteur VIVANT que le socle peignait avec
        // elle — a désormais sa police de charte. Sans cette règle, les 39 `<th>`
        // hors `<thead>` retomberaient sur la police par défaut du navigateur.
        $this->assertMatchesRegularExpression(
            '/^\.table th, table th \{ font-family: var\(--bl-font-body\); \}/m',
            $this->feuille('theme-backoffice.css')
        );
    }

    // — outils ---------------------------------------------------------------

    /**
     * Le fond et l'encre d'une famille de nœud, résolus jusqu'à l'hexadécimal :
     * la feuille ne porte que des `var(--bl-*)`, et c'est `tokens.css` qui donne
     * la valeur. Résoudre plutôt que recopier, c'est ce qui rend le contraste
     * mesurable ici.
     *
     * @return array{fond:string,encre:string}
     */
    private function couleursDuNoeud(string $famille): array
    {
        $css = $this->feuille('theme-frontend.css');

        $this->assertSame(
            1,
            preg_match(
                '/\.cd-timeline__img\.bl-node--' . preg_quote($famille, '/')
                    . '\s*\{\s*background:\s*var\((--bl-[a-z-]+)\);\s*color:\s*var\((--bl-[a-z-]+)\);/',
                $css,
                $m
            ),
            "la règle du nœud « {$famille} » doit poser un fond et une encre, tous deux en jetons"
        );

        return ['fond' => $this->jeton($m[1]), 'encre' => $this->jeton($m[2])];
    }

    /** Contraste WCAG 2.1 entre deux couleurs `#rrggbb`. */
    private function contraste(string $a, string $b): float
    {
        $luminance = static function (string $hex): float {
            $hex = ltrim($hex, '#');
            $canal = static function (float $c): float {
                $c /= 255;

                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            };

            return 0.2126 * $canal(hexdec(substr($hex, 0, 2)))
                + 0.7152 * $canal(hexdec(substr($hex, 2, 2)))
                + 0.0722 * $canal(hexdec(substr($hex, 4, 2)));
        };

        $x = $luminance($a);
        $y = $luminance($b);

        return $x > $y ? ($x + 0.05) / ($y + 0.05) : ($y + 0.05) / ($x + 0.05);
    }
}
