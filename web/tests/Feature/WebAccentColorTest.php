<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\GeneralSettings;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use App\Services\Brand\AccentColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Lot 3 de la charte web (2026-09-18) — l'ocre réglable par transporteur.
 *
 * L'arbitrage §9.3 de l'audit a retenu l'option (b) : `primary_color` et
 * `accent_color` restent réglables, la charte n'étant que le DÉFAUT d'usine. Le
 * lot 1 avait livré la couleur primaire ; l'ocre restait figé dans `tokens.css`.
 *
 * Ce que ces tests tiennent, et que rien d'autre ne tient :
 *
 *  1. **Les trois jetons dérivés suivent la couleur choisie.** `--bl-on-accent`
 *     et `--bl-accent-text` sont des contrastes MESURÉS contre l'ocre. Les
 *     laisser fixes pendant que l'accent bouge, c'est livrer du texte illisible.
 *     Le test le prouve par le contre-exemple : sur un bleu marine, l'encre de
 *     la charte tombe sous AA, celle que le service calcule le tient.
 *  2. **Aucune couleur ne peut rendre un libellé illisible.** Balayage de
 *     4 096 couleurs, contrastes RECALCULÉS. Un commentaire peut mentir.
 *  3. **La charte garde le dernier mot sur elle-même.** Tant que le
 *     transporteur est sur l'ocre, rien n'est injecté : `tokens.css` sert ses
 *     valeurs mesurées à la main, et la page ne paie pas un octet.
 *  4. **Rien d'autre que des hexadécimaux n'atteint le bloc `<style>`.** La
 *     valeur passe par `normalise()` à l'écriture comme à la lecture.
 */
class WebAccentColorTest extends TestCase
{
    use RefreshDatabase;

    /** Un bleu marine plausible pour un transporteur — et hostile à l'encre brune. */
    private const MARINE = '#1B2A5B';

    private function jetons(): string
    {
        return file_get_contents(public_path('beninlink/css/tokens.css'));
    }

    /** La valeur d'un jeton `--bl-*` telle que `tokens.css` la porte. */
    private function jeton(string $nom): string
    {
        $this->assertSame(
            1,
            preg_match('/' . preg_quote($nom, '/') . '\s*:\s*(#[0-9A-Fa-f]{6})\s*;/', $this->jetons(), $m),
            "{$nom} doit être déclaré une fois dans tokens.css"
        );

        return strtoupper($m[1]);
    }

    // — la charte est la même des deux côtés --------------------------------

    /**
     * LE test de ce lot, côté charte. `AccentColor` porte quatre hexadécimaux
     * pour savoir reconnaître « le transporteur est resté sur la charte ». Ce
     * sont les seuls de la classe, et ils doivent être ceux de `tokens.css` :
     * une retouche de nuance d'un côté sans l'autre ferait injecter des jetons
     * là où il ne faut pas — ou pire, n'en injecterait plus du tout.
     */
    public function test_the_service_and_tokens_css_agree_on_the_charter_ochre(): void
    {
        $this->assertSame($this->jeton('--bl-accent'), AccentColor::CHARTE);
        $this->assertSame($this->jeton('--bl-accent-dark'), AccentColor::CHARTE_DARK);
        $this->assertSame($this->jeton('--bl-on-accent'), AccentColor::CHARTE_ENCRE);
        $this->assertSame($this->jeton('--bl-accent-text'), AccentColor::CHARTE_TEXTE);
    }

    // — ce que le lot corrige, prouvé par le contre-exemple ------------------

    /**
     * Sans ce lot, `--bl-on-accent` resterait le brun `#3A2A06` quelle que soit
     * la couleur du transporteur. Sur un bleu marine, ce brun donne un libellé
     * de bouton qu'on ne lit pas — voilà le défaut, mesuré, pas supposé.
     */
    public function test_the_charter_ink_would_be_unreadable_on_another_accent(): void
    {
        $avant = AccentColor::contraste(AccentColor::CHARTE_ENCRE, self::MARINE);
        $this->assertLessThan(4.5, $avant, 'sinon ce lot n\'a rien à corriger sur cette couleur');

        $apres = AccentColor::contraste(AccentColor::encre(self::MARINE), self::MARINE);
        $this->assertGreaterThanOrEqual(4.5, $apres);

        // Et la variante « en texte sur blanc » suit la même règle.
        $this->assertGreaterThanOrEqual(
            4.5,
            AccentColor::contraste(AccentColor::texte(self::MARINE), '#FFFFFF')
        );
    }

    /**
     * La promesse du service : AUCUNE couleur ne peut produire un texte
     * illisible. On la vérifie en balayant l'espace des couleurs et en
     * recalculant chaque contraste — 4 096 couleurs suffisent à couvrir toutes
     * les luminances, y compris les gris moyens, qui sont le cas hostile (ni le
     * noir ni le blanc n'y tiennent AA sans passer aux valeurs pures).
     */
    public function test_no_colour_a_carrier_may_pick_can_produce_unreadable_text(): void
    {
        $pireEncre = 21.0;
        $pireTexte = 21.0;

        for ($r = 0; $r <= 255; $r += 17) {
            for ($v = 0; $v <= 255; $v += 17) {
                for ($b = 0; $b <= 255; $b += 17) {
                    $accent = sprintf('#%02X%02X%02X', $r, $v, $b);

                    $encre = AccentColor::contraste(AccentColor::encre($accent), $accent);
                    $pireEncre = min($pireEncre, $encre);
                    $this->assertGreaterThanOrEqual(4.5, $encre, "encre sur {$accent}");

                    $texte = AccentColor::texte($accent);
                    $surBlanc = AccentColor::contraste($texte, '#FFFFFF');
                    $pireTexte = min($pireTexte, $surBlanc);
                    $this->assertGreaterThanOrEqual(4.5, $surBlanc, "texte de {$accent} sur blanc");

                    // La variante « en texte » ASSOMBRIT : elle garde la teinte
                    // choisie par le transporteur, elle ne la remplace pas.
                    [$tr, $tv, $tb] = sscanf($texte, '#%02x%02x%02x');
                    $this->assertLessThanOrEqual($r, $tr, $accent);
                    $this->assertLessThanOrEqual($v, $tv, $accent);
                    $this->assertLessThanOrEqual($b, $tb, $accent);
                }
            }
        }

        // Les deux pires cas rencontrés touchent la cible sans la manquer : la
        // marge est nulle par construction, et c'est voulu — on assombrit juste
        // ce qu'il faut, pas jusqu'au noir.
        $this->assertGreaterThanOrEqual(4.5, $pireEncre);
        $this->assertGreaterThanOrEqual(4.5, $pireTexte);
    }

    // — la charte garde le dernier mot sur elle-même ------------------------

    public function test_nothing_is_injected_while_the_carrier_keeps_the_charter(): void
    {
        $this->assertSame([], AccentColor::jetons(AccentColor::CHARTE), 'la charte');
        $this->assertSame([], AccentColor::jetons('#e0a63c'), 'la charte, saisie en minuscules');
        $this->assertSame([], AccentColor::jetons(null), 'colonne absente ou nulle');
        $this->assertSame([], AccentColor::jetons('red'), 'valeur illisible');

        // Le calcul NE reproduit PAS les valeurs mesurées à la main du lot 1 —
        // l'encre de la charte est un brun chaud, pas un noir. C'est justement
        // pourquoi la charte ne passe jamais par le calcul.
        $this->assertNotSame(AccentColor::CHARTE_ENCRE, AccentColor::encre(AccentColor::CHARTE));
    }

    /**
     * Les jetons de PASTILLE sont des sémantiques de statut partagées avec
     * `mobile/`, pas la marque du transporteur : un colis en transit doit rester
     * de la même couleur pour tout le monde. L'accent ne les touche pas.
     */
    public function test_the_carrier_accent_never_reaches_the_status_pills(): void
    {
        $jetons = AccentColor::jetons(self::MARINE);

        $this->assertSame(
            ['--bl-accent', '--bl-accent-dark', '--bl-on-accent', '--bl-accent-text'],
            array_keys($jetons),
            'quatre jetons, et exactement ceux-là'
        );

        // Les sept familles de pastilles vivent dans tokens.css et n'en bougent
        // pas : le fragment injecté ne peut pas en redéfinir une.
        $this->assertStringContainsString('--bl-pill-transit-bg', $this->jetons());
        foreach (array_keys($jetons) as $nom) {
            $this->assertStringNotContainsString('pill', $nom);
        }
    }

    // — rien d'autre qu'un hexadécimal n'atteint la page --------------------

    /**
     * La couleur finit dans un bloc `<style>`. Blade échappe `<` et `>`, donc on
     * ne sort pas de l'élément — mais `;`, `{` et `}` passent, et suffisent à
     * injecter du CSS. Le filtre est donc la seule garde.
     */
    public function test_only_a_hexadecimal_survives_the_filter(): void
    {
        foreach (['#e0a63c' => '#E0A63C', 'E0A63C' => '#E0A63C', '#abc' => '#AABBCC', '  #E0A63C ' => '#E0A63C'] as $saisie => $attendu) {
            $this->assertSame($attendu, AccentColor::normalise((string) $saisie), (string) $saisie);
        }

        foreach (['red', '#12', '#GGGGGG', '#fff;}body{display:none', 'var(--bl-primary)', '', null] as $saisie) {
            $this->assertNull(AccentColor::normalise($saisie), var_export($saisie, true));
        }
    }

    /**
     * Et la garde tient au point d'ÉCRITURE, pas seulement à l'affichage : une
     * valeur qui n'est pas une couleur ne s'enregistre pas du tout.
     */
    public function test_the_settings_screen_cannot_store_anything_but_a_colour(): void
    {
        $this->societe();
        $repo = app(GeneralSettingsInterface::class);

        $repo->update($this->formulaire(['accent_color' => '#1b2a5b']));
        $this->assertSame(self::MARINE, DB::table('general_settings')->where('id', 1)->value('accent_color'));

        $repo->update($this->formulaire(['accent_color' => '#fff;}body{display:none']));
        $this->assertSame(
            self::MARINE,
            DB::table('general_settings')->where('id', 1)->value('accent_color'),
            'une saisie douteuse ne doit rien écraser'
        );
    }

    // — la base ------------------------------------------------------------

    /**
     * `CompanyRepository::company_create()` ne renseigne aucune couleur : chaque
     * nouveau transporteur hérite du DÉFAUT DE COLONNE. C'est le constat du
     * lot 1, et il vaut aussi pour l'ocre.
     */
    public function test_a_new_carrier_inherits_the_charter_ochre(): void
    {
        $this->assertTrue(Schema::hasColumn('general_settings', 'accent_color'));

        DB::table('general_settings')->insert(['id' => 7, 'name' => 'Transporteur neuf']);

        $this->assertSame(
            AccentColor::CHARTE,
            DB::table('general_settings')->where('id', 7)->value('accent_color')
        );

        $seeder = file_get_contents(database_path('seeders/GeneralSettingsSeeder.php'));
        $this->assertSame(2, substr_count($seeder, AccentColor::CHARTE), 'les deux sociétés du seeder');
    }

    // — l'injection dans les pages ------------------------------------------

    /**
     * Les deux mises en page — site public et back-office — incluent le même
     * fragment, APRÈS les feuilles de la charte : sans quoi `tokens.css`
     * recouvrirait le réglage du transporteur, qui doit garder le dernier mot.
     *
     * Ce sont les deux SEULES vues concernées : les cinq autres qui chargent
     * `tokens.css` (les trois impressions, `api/docs`, `fedapay_callback`)
     * n'emploient aucun jeton d'accent — ce test le vérifie aussi, pour que
     * l'ajout d'un accent dans l'une d'elles ne passe pas inaperçu.
     */
    public function test_both_layouts_inject_the_accent_after_the_charter_sheets(): void
    {
        $vues = [
            'frontend/layouts/master.blade.php' => 'beninlink/css/theme-frontend.css',
            'backend/partials/header.blade.php' => 'beninlink/css/theme-backoffice.css',
        ];

        foreach ($vues as $vue => $feuille) {
            $source = file_get_contents(resource_path('views/' . $vue));

            $this->assertStringContainsString("@include('beninlink.brand-accent')", $source, $vue);
            $this->assertLessThan(
                strpos($source, "@include('beninlink.brand-accent')"),
                strpos($source, $feuille),
                "{$vue} : le réglage du transporteur doit garder le dernier mot"
            );
        }

        foreach ([
            'backend/parcel/bulk_print.blade.php',
            'backend/reports/parcel_reports_print.blade.php',
            'backend/merchant_panel/reports/parcel_reports_print.blade.php',
            'backend/payment/fedapay_callback.blade.php',
            'api/docs.blade.php',
        ] as $vue) {
            $this->assertStringNotContainsString(
                '--bl-accent',
                file_get_contents(resource_path('views/' . $vue)),
                "{$vue} emploie un jeton d'accent : il lui faut le fragment d'injection"
            );
        }
    }

    /** Sur la charte, le fragment n'écrit RIEN — pas un bloc `<style>` vide. */
    public function test_the_fragment_stays_silent_on_the_charter(): void
    {
        $this->societe(AccentColor::CHARTE);

        $this->assertSame('', trim(view('beninlink.brand-accent')->render()));
    }

    /** Sur une autre couleur, il écrit les quatre jetons, et rien d'autre. */
    public function test_the_fragment_writes_the_four_tokens_for_a_custom_accent(): void
    {
        $this->societe(self::MARINE);

        $rendu = view('beninlink.brand-accent')->render();

        $this->assertStringContainsString('--bl-accent: ' . self::MARINE . ';', $rendu);
        $this->assertStringContainsString('--bl-on-accent: ' . AccentColor::encre(self::MARINE) . ';', $rendu);
        $this->assertStringContainsString('--bl-accent-text: ' . AccentColor::texte(self::MARINE) . ';', $rendu);
        $this->assertStringContainsString('--bl-accent-dark: ' . AccentColor::sombre(self::MARINE) . ';', $rendu);

        // Un seul bloc, et il ne porte que des hexadécimaux.
        $this->assertSame(1, substr_count($rendu, '<style>'));
        $this->assertSame(4, preg_match_all('/--bl-[a-z-]+: #[0-9A-F]{6};/', $rendu));
    }

    /** L'écran des paramètres propose le réglage, et son libellé est traduit. */
    public function test_the_settings_screen_offers_the_setting(): void
    {
        $vue = file_get_contents(resource_path('views/backend/general_settings/index.blade.php'));

        $this->assertStringContainsString('name="accent_color"', $vue);
        $this->assertStringContainsString("__('levels.accent_color')", $vue);
        $this->assertStringContainsString('type="color"', $vue);

        foreach (['fr', 'en', 'es', 'bn', 'zh', 'ar', 'in'] as $langue) {
            $libelles = require lang_path($langue . '/levels.php');
            $this->assertArrayHasKey('accent_color', $libelles, $langue);
            $this->assertNotSame('', trim($libelles['accent_color']), $langue);
        }
    }

    // — outils --------------------------------------------------------------

    /** La société 1, celle que `settings()` sert hors requête authentifiée. */
    private function societe(string $accent = AccentColor::CHARTE): GeneralSettings
    {
        $row = new GeneralSettings();
        $row->id = 1;
        $row->name = 'BeninLink';
        $row->currency = 'FCFA';
        $row->primary_color = '#12503A';
        $row->text_color = '#ffffff';
        $row->accent_color = $accent;
        $row->status = Status::ACTIVE;
        $row->save();

        return $row;
    }

    /** L'écran des paramètres renvoie tous ses champs à chaque enregistrement. */
    private function formulaire(array $champs): Request
    {
        return new Request(array_merge([
            'name' => 'BeninLink',
            'phone' => '97000000',
            'email' => 'contact@beninlink.bj',
            'address' => 'Cotonou',
            'currency' => 'FCFA',
            'copyright' => '© BeninLink',
            'par_track_prefix' => 'bl',
            'invoice_prefix' => 'bl',
            'primary_color' => '#12503A',
            'text_color' => '#ffffff',
        ], $champs));
    }
}
