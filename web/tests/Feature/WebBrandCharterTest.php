<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Lot 1 de la charte web (2026-09-18) — le filet.
 *
 * L'audit `docs/guides/charte-web/` a relevé que `web/` était resté sur la
 * charte We Courier : **132 occurrences du violet `#7e0095`**, **zéro** de
 * l'ocre, ni Sora ni DM Sans, et les titres du site public en `fangsong` — une
 * famille de serif chinois absente de toute machine latine.
 *
 * Ce lot corrige cela **par ajout** : une couche de jetons chargée en dernier,
 * pour ne pas peindre la charte dans les 7 263 lignes du CSS du socle (voir
 * `docs/guides/socle/` : 238 fichiers du socle déjà modifiés, dont 4 seulement
 * dans `public/backend` — un conflit de fusion de plus s'y paierait à chaque
 * montée de We Courier).
 *
 * Ces tests fixent ce qui, sinon, se déferait **en silence** :
 *  - une re-fusion du socle qui remet `lang="en"`, le zoom bloqué, ou qui
 *    retire les deux `<link>` de la charte ;
 *  - une recopie d'hexadécimal dans une vue — c'est ce qui avait produit trois
 *    îlots divergents ;
 *  - une dérive entre la charte du web et celle des apps.
 *
 * Aucun ne touche la base : ils lisent les fichiers, comme
 * `BrowserPushRetiredTest` le fait pour D12.
 */
class WebBrandCharterTest extends TestCase
{
    /** Vert profond, ocre : la charte, telle que le CLAUDE.md racine l'énonce. */
    private const VERT = '#12503A';
    private const OCRE = '#E0A63C';

    /** Violet du socle We Courier — ne doit plus décider de rien. */
    private const VIOLET = '#7e0095';

    private function jetons(): string
    {
        return file_get_contents(public_path('beninlink/css/tokens.css'));
    }

    // — la charte du web est celle des apps, prouvée ------------------------

    /**
     * LE test de ce lot. `mobile/src/theme/colors.ts` est la source de vérité
     * (arbitrage du 2026-09-18, contre les cinq jetons divergents de la
     * maquette). Ici on le lit vraiment et on compare : une retouche d'un côté
     * sans l'autre fait échouer la suite, au lieu de livrer deux chartes.
     */
    public function test_tokens_css_carries_the_exact_colours_of_the_mobile_theme(): void
    {
        $theme = base_path('../mobile/src/theme/colors.ts');
        $this->assertFileExists($theme, 'la source de vérité de la charte');

        $source = file_get_contents($theme);
        $jetons = $this->jetons();

        // Nom du jeton côté app  =>  nom du jeton côté web.
        $paires = [
            'primary' => '--bl-primary',
            'primaryDark' => '--bl-primary-dark',
            'primaryLight' => '--bl-primary-light',
            'accent' => '--bl-accent',
            'accentDark' => '--bl-accent-dark',
            'danger' => '--bl-danger',
            'warning' => '--bl-warning',
            'success' => '--bl-success',
            'info' => '--bl-info',
            'text' => '--bl-text',
            'textMuted' => '--bl-text-muted',
            'background' => '--bl-background',
            'surface' => '--bl-surface',
            'border' => '--bl-border',
            'disabled' => '--bl-disabled',
        ];

        foreach ($paires as $app => $web) {
            $this->assertSame(
                1,
                preg_match('/^\s*' . $app . ":\s*'(#[0-9A-Fa-f]{6})'/m", $source, $m),
                "colors.ts ne déclare plus `{$app}` : la correspondance est à revoir"
            );
            $attendue = strtoupper($m[1]);

            $this->assertSame(
                1,
                preg_match('/' . preg_quote($web, '/') . ':\s*(#[0-9A-Fa-f]{6})\s*;/', $jetons, $t),
                "tokens.css ne déclare pas {$web}"
            );

            $this->assertSame(
                $attendue,
                strtoupper($t[1]),
                "{$web} a dérivé de colors.ts.{$app} — une charte, deux valeurs"
            );
        }
    }

    /**
     * La maquette du back-office divergeait de `colors.ts` sur cinq jetons —
     * un marchand passant de l'app au web aurait vu deux rouges et deux gris.
     * Elle a été corrigée ; ce test empêche la divergence de revenir.
     */
    public function test_the_back_office_mockup_agrees_with_the_mobile_theme(): void
    {
        $maquette = file_get_contents(base_path('../maquettes/3_back_office_web.html'));
        $theme = file_get_contents(base_path('../mobile/src/theme/colors.ts'));

        $lire = function (string $source, string $motif) {
            $this->assertSame(1, preg_match($motif, $source, $m), "introuvable : {$motif}");

            return strtoupper($m[1]);
        };

        $arbitres = [
            '--vert' => 'primary',
            '--rouge' => 'danger',
            '--vert-vif' => 'success',
            '--encre' => 'text',
            '--brume' => 'background',
            '--ligne' => 'border',
            '--ocre' => 'accent',
        ];

        foreach ($arbitres as $maq => $app) {
            $this->assertSame(
                $lire($theme, '/^\s*' . $app . ":\s*'(#[0-9A-Fa-f]{6})'/m"),
                $lire($maquette, '/' . preg_quote($maq, '/') . ':(#[0-9A-Fa-f]{6})/'),
                "la maquette et colors.ts divergent sur {$maq} / {$app}"
            );
        }
    }

    // — les deux <link> de la charte, et leur POSITION ----------------------

    /**
     * La position n'est pas un détail. Au back-office, la charte est chargée
     * APRÈS `@stack('styles')` parce que les pages y poussent `logs.css` —
     * la timeline de suivi — qui porte encore le violet du socle.
     */
    public function test_the_back_office_loads_the_charter_after_the_pushed_styles(): void
    {
        $source = file_get_contents(resource_path('views/backend/partials/header.blade.php'));

        foreach (['tokens.css', 'theme-backoffice.css'] as $feuille) {
            $this->assertStringContainsString("beninlink/css/{$feuille}", $source, $feuille);
        }

        $this->assertLessThan(
            strpos($source, 'beninlink/css/tokens.css'),
            strpos($source, "@stack('styles')"),
            "la charte doit venir après @stack('styles'), sinon logs.css la recouvre"
        );

        // Et après les feuilles du socle, sans quoi elle ne requalifie rien.
        $this->assertLessThan(
            strpos($source, 'beninlink/css/theme-backoffice.css'),
            strpos($source, 'libs/css/style.css'),
            'la charte doit venir après le CSS du socle'
        );
    }

    /**
     * Au site public, l'ordre est inverse sur un point : la charte passe AVANT
     * le bloc en ligne qui injecte `settings()->primary_color`, pour qu'un
     * transporteur ayant choisi sa couleur la garde. La charte est le défaut,
     * pas une prison — c'est ce que le multi-tenant du socle promet.
     */
    public function test_the_public_site_lets_the_tenant_colour_win(): void
    {
        $source = file_get_contents(resource_path('views/frontend/layouts/master.blade.php'));

        foreach (['tokens.css', 'theme-frontend.css'] as $feuille) {
            $this->assertStringContainsString("beninlink/css/{$feuille}", $source, $feuille);
        }

        $this->assertLessThan(
            strpos($source, 'settings()->primary_color'),
            strpos($source, 'beninlink/css/theme-frontend.css'),
            'le réglage du transporteur doit garder le dernier mot'
        );

        // Bitter était téléchargée et utilisée nulle part ; Roboto est remplacée.
        $this->assertStringNotContainsString('family=Bitter', $source);
        $this->assertStringNotContainsString('fonts.googleapis.com', $source);
    }

    // — les polices sont vraiment là ----------------------------------------

    public function test_the_charter_fonts_are_self_hosted_and_valid(): void
    {
        $jetons = $this->jetons();

        foreach (['sora-latin', 'sora-latin-ext', 'dm-sans-latin', 'dm-sans-latin-ext'] as $police) {
            $chemin = public_path("beninlink/fonts/{$police}.woff2");
            $this->assertFileExists($chemin, $police);

            // Signature woff2 : un fichier HTML d'erreur renommé .woff2 ne
            // produirait aucune erreur visible, juste une police manquante.
            $this->assertSame('wOF2', file_get_contents($chemin, false, null, 0, 4), $police);

            $this->assertStringContainsString("../fonts/{$police}.woff2", $jetons, $police);
        }

        // Licence OFL : la redistribution l'exige à côté des fichiers.
        $this->assertFileExists(public_path('beninlink/fonts/OFL-Sora.txt'));
        $this->assertFileExists(public_path('beninlink/fonts/OFL-DMSans.txt'));

        // Chemins RELATIFS : static_asset() sert deux formes d'URL selon le
        // locataire, une URL absolue casserait sur l'une des deux.
        $this->assertStringNotContainsString('/public/beninlink/fonts', $jetons);
        $this->assertStringNotContainsString('fonts.gstatic.com', $jetons);
    }

    // — ce que la charte remplace -------------------------------------------

    public function test_no_layout_blocks_the_zoom_any_more(): void
    {
        $vues = [
            'backend/partials/header.blade.php',
            'frontend/layouts/master.blade.php',
            'installer/index.blade.php',
            'backend/deliveryman/parcel/parcel-map.blade.php',
        ];

        foreach ($vues as $vue) {
            $source = file_get_contents(resource_path('views/' . $vue));

            // Le back-office était rendu à 80 % ET pincer-pour-zoomer coupé,
            // pour des agents qui saisissent des colis au téléphone.
            $this->assertStringNotContainsString('user-scalable = no', $source, $vue);
            $this->assertStringNotContainsString('user-scalable=no', $source, $vue);
            $this->assertStringNotContainsString('maximum-scale', $source, $vue);

            $compile = Blade::compileString($source);
            $this->assertSame(
                substr_count($compile, '<?php if('),
                substr_count($compile, '<?php endif; ?>'),
                $vue
            );
        }
    }

    public function test_the_back_office_declares_the_locale_it_actually_serves(): void
    {
        $source = file_get_contents(resource_path('views/backend/partials/header.blade.php'));

        // La locale par défaut est `fr` (config/app.php) ; la page annonçait `en`.
        $this->assertStringNotContainsString('<html lang="en"', $source);
        $this->assertStringContainsString("app()->getLocale()", $source);
    }

    public function test_the_headings_of_the_public_site_are_no_longer_chinese_serif(): void
    {
        $theme = file_get_contents(public_path('beninlink/css/theme-frontend.css'));

        // Le socle laissait --h-font-family:'fangsong'. On ne l'édite pas : on
        // redéfinit la variable depuis une feuille chargée après.
        $this->assertMatchesRegularExpression(
            '/--h-font-family:\s*var\(--bl-font-heading\)/',
            $theme
        );
        $this->assertStringContainsString("'Sora'", $this->jetons());
        $this->assertStringContainsString("'DM Sans'", $this->jetons());
    }

    // — les règles de la charte ---------------------------------------------

    /**
     * Blanc sur ocre = 2,17:1. La règle n'est pas une préférence : c'est la
     * différence entre un bouton lisible et un bouton illisible.
     */
    public function test_the_ochre_never_carries_white_text(): void
    {
        $jetons = $this->jetons();

        $this->assertSame(
            1,
            preg_match('/--bl-on-accent:\s*(#[0-9A-Fa-f]{6})/', $jetons, $m),
            'tokens.css doit nommer la couleur de texte posée sur l\'ocre'
        );
        $this->assertNotSame('#FFFFFF', strtoupper($m[1]), 'blanc sur ocre : 2,17:1');

        // Contraste recalculé ici même : le commentaire pourrait mentir, pas ceci.
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($m[1], self::OCRE));
    }

    /** Les six pastilles de statut tiennent AA — la maquette échouait sur les six. */
    public function test_every_status_pill_meets_aa_for_small_text(): void
    {
        $jetons = $this->jetons();

        foreach (['wait', 'transit', 'hub', 'assign', 'done', 'return'] as $famille) {
            foreach (['bg', 'fg'] as $part) {
                $this->assertSame(
                    1,
                    preg_match("/--bl-pill-{$famille}-{$part}:\s*(#[0-9A-Fa-f]{6})/", $jetons, $m),
                    "--bl-pill-{$famille}-{$part}"
                );
                $$part = $m[1];
            }

            $this->assertGreaterThanOrEqual(
                4.5,
                $this->contraste($fg, $bg),
                "la pastille « {$famille} » n'est pas lisible ({$fg} sur {$bg})"
            );
        }
    }

    /**
     * Les couleurs d'état portent du texte, et Bootstrap y pose du blanc. Deux
     * paires du socle échouaient : blanc sur l'orange (2,91:1) et blanc sur le
     * vert de succès (4,14:1 — une pastille « Livré » est du petit texte). La
     * charte les traite, et ce test vérifie le RÉSULTAT, pas l'intention.
     */
    public function test_every_semantic_surface_is_readable(): void
    {
        $jetons = $this->jetons();
        $theme = file_get_contents(public_path('beninlink/css/theme-backoffice.css'));

        $valeur = function (string $nom) use ($jetons): string {
            $this->assertSame(
                1,
                preg_match('/' . preg_quote($nom, '/') . ':\s*(#[0-9A-Fa-f]{6})/', $jetons, $m),
                "tokens.css ne déclare pas {$nom}"
            );

            return $m[1];
        };

        // Texte sur fond coloré.
        foreach ([
            ['#FFFFFF', '--bl-danger', 'blanc sur danger'],
            ['#FFFFFF', '--bl-info', 'blanc sur info'],
            ['#FFFFFF', '--bl-success-strong', 'blanc sur le vert assombri'],
        ] as [$texte, $fond, $quoi]) {
            $this->assertGreaterThanOrEqual(4.5, $this->contraste($texte, $valeur($fond)), $quoi);
        }

        // L'orange ne porte pas de blanc : il porte l'encre de l'ocre.
        $this->assertGreaterThanOrEqual(
            4.5,
            $this->contraste($valeur('--bl-on-accent'), $valeur('--bl-warning')),
            'encre sur orange'
        );
        $this->assertMatchesRegularExpression(
            '/\.badge-warning[^}]*color:\s*var\(--bl-on-accent\)/s',
            $theme,
            'le blanc sur orange (2,91:1) doit être rattrapé'
        );

        // Couleurs de texte sur blanc.
        foreach (['--bl-accent-text', '--bl-success-text', '--bl-text-muted', '--bl-slate'] as $nom) {
            $this->assertGreaterThanOrEqual(
                4.5,
                $this->contraste($valeur($nom), '#FFFFFF'),
                "{$nom} sur blanc"
            );
        }

        // Et le piège inverse : le vert NOMINAL ne doit pas servir de texte, ni
        // de fond à du blanc — c'est précisément ce qui échouait.
        $this->assertLessThan(
            4.5,
            $this->contraste($valeur('--bl-success'), '#FFFFFF'),
            'si --bl-success tient AA, ces deux variantes sont devenues inutiles : simplifier'
        );
    }

    // — plus de recopie, et plus de violet qui décide -----------------------

    /**
     * Trois vues ajoutées par BeninLink recopiaient la charte à la main : à la
     * première retouche de nuance, elles auraient divergé. Deux référencent
     * désormais les jetons. La troisième est un PDF : dompdf ne résout pas
     * `var()`, donc elle garde ses littéraux — mais elle doit le DIRE.
     */
    public function test_the_charter_is_not_copied_into_views_any_more(): void
    {
        foreach (['api/docs.blade.php', 'backend/payment/fedapay_callback.blade.php'] as $vue) {
            $source = file_get_contents(resource_path('views/' . $vue));

            $this->assertStringNotContainsStringIgnoringCase(self::VERT, $source, $vue);
            $this->assertStringContainsString('beninlink/css/tokens.css', $source, $vue);
            $this->assertStringContainsString('var(--bl-', $source, $vue);
        }

        $pdf = file_get_contents(resource_path('views/backend/invoice/statement_pdf.blade.php'));
        $this->assertStringContainsStringIgnoringCase(self::VERT, $pdf, 'le PDF garde ses littéraux');
        $this->assertStringContainsString('tokens.css', $pdf, 'il doit dire de qui il les copie');
        $this->assertStringContainsString('dompdf', $pdf, 'et pourquoi il ne peut pas faire autrement');
    }

    /**
     * `CompanyRepository::company_create()` ne renseigne jamais `primary_color` :
     * chaque nouveau transporteur hérite du DÉFAUT DE COLONNE. Il était violet.
     */
    public function test_a_new_carrier_inherits_the_charter_colour(): void
    {
        $creation = file_get_contents(
            database_path('migrations/2014_05_31_094551_create_general_settings_table.php')
        );
        $this->assertStringContainsString("default('" . self::VERT . "')", $creation);
        $this->assertStringNotContainsString(self::VIOLET, $creation);

        $seeder = file_get_contents(database_path('seeders/GeneralSettingsSeeder.php'));
        $this->assertStringNotContainsString(self::VIOLET, $seeder);
        $this->assertSame(2, substr_count($seeder, self::VERT), 'les deux sociétés du seeder');
    }

    /**
     * La migration de données ne doit convertir QUE la valeur d'usine : un
     * transporteur qui a choisi sa couleur la garde. C'est ce qui la rend
     * rejouable sans dégât.
     */
    public function test_the_colour_migration_spares_a_carrier_who_chose_its_own(): void
    {
        $source = file_get_contents(
            database_path('migrations/2026_09_18_100000_set_beninlink_brand_color.php')
        );

        $this->assertStringContainsString('whereRaw', $source, 'la conversion doit être filtrée');
        $this->assertStringContainsString(self::VIOLET, $source, 'et ne viser que la valeur d\'usine');

        // Le DDL n'accepte aucun paramètre lié : la valeur est interpolée, donc
        // elle doit être validée. Sans quoi la garde ne tient qu'au bon vouloir.
        $this->assertStringContainsString('preg_match', $source);
        $this->assertStringContainsString('getDriverName', $source, 'SQLite n\'a pas ALTER ... SET DEFAULT');
    }

    // — outil ---------------------------------------------------------------

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
