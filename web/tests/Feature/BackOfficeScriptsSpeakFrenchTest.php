<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * **S119** — les scripts du back-office parlent français.
 *
 * S117 et S118 tenaient les vues et le code PHP. Restait ce que les scripts affichent eux-mêmes :
 * les cinq interrupteurs de statut et de priorité demandaient « Are you confirm ? » avec des
 * boutons « Yes » / « Cancel » (sur `denyButtonText`, option sans effet sans `showDenyButton` :
 * le bouton d'annulation n'apparaissait pas) puis notifiaient « Status updated successfully » ;
 * la suppression confirmait « Are you sure want to delete this record? » ; les recherches de colis
 * des recettes, dépenses et remises d'espèces répondaient « Parcel not found! », et les soldes
 * « Current Balance: 15000.5 » (montant brut, décimales) ; le sélecteur de
 * période proposait « Today », « Last 7 Days », « Clear » ; les cartes disaient « Your current
 * location. » et « Geolocation is not supported by this browser. ».
 *
 * Règle : un script lit ses textes dans `trad` (`lang/*\/js.php`, rendu par le pied de page) ou
 * dans les globales déjà traduites (`yes`, `cancel`, `confirmUpdate`), jamais un littéral.
 * Les bibliothèques tierces (minifiées, SweetAlert, graphiques, lecteur vidéo) ne sont pas lues.
 */
class BackOfficeScriptsSpeakFrenchTest extends TestCase
{
    /** Bibliothèques tierces, servies telles quelles. */
    private const TIERCES = ['sweetalert2.js', 'charts/apexcharts.js', 'parcel/map/typed.js'];

    /** Formes par lesquelles un script affiche un texte. */
    private const FORMES = [
        "/\\b(?:text|title|confirmButtonText|cancelButtonText|denyButtonText|cancelLabel|applyLabel)\\s*:\\s*['\"][A-Za-z]/",
        "/\\b(?:alert|confirm)\\(\\s*['\"]/",
        "/\\.text\\(\\s*['\"][A-Za-z]/",
        "/['\"](?:Today|Yesterday|Last 7 Days|Last 30 Days|This Month|Last Month)['\"]\\s*:/",
        "/=\\s*\"[A-Z][a-z]+ (?:is|are|not)\\b[^\"]*\"/",
        // un nœud de texte dans un fragment HTML : '<small class="text-danger">Parcel not found!</small>'
        "/>\\s*[A-Za-z][^<'\"+\\n]{2,}</",
    ];

    /** @return array<string, string> */
    private function scripts(): array
    {
        $scripts = [];
        foreach (Finder::create()->files()->in(public_path('backend/js'))->name('*.js')->notName('*.min.js') as $fichier) {
            $chemin = str_replace('\\', '/', $fichier->getRelativePathname());
            if (! in_array($chemin, self::TIERCES, true)) {
                $scripts[$chemin] = $fichier->getContents();
            }
        }

        return $scripts;
    }

    public function test_no_back_office_script_displays_a_literal(): void
    {
        $litteraux = [];
        foreach ($this->scripts() as $chemin => $source) {
            foreach (self::FORMES as $forme) {
                if (preg_match_all($forme, $source, $trouves)) {
                    foreach ($trouves[0] as $texte) {
                        $litteraux[] = "{$chemin} : {$texte}";
                    }
                }
            }
        }

        $this->assertGreaterThan(40, count($this->scripts()), 'le parcours a bien lu les scripts');
        $this->assertSame([], $litteraux, "texte écrit en dur dans un script :\n" . implode("\n", $litteraux));
    }

    public function test_every_key_a_script_reads_exists_in_both_languages(): void
    {
        $fr = require lang_path('fr/js.php');
        $en = require lang_path('en/js.php');
        $this->assertSame(array_keys($fr), array_keys($en), 'lang/fr/js.php et lang/en/js.php ont les mêmes clés');

        $lues = [];
        foreach ($this->scripts() as $chemin => $source) {
            preg_match_all('/\btrad\.([a-z0-9_]+)/', $source, $cles);
            foreach ($cles[1] as $cle) {
                $lues[$cle] = true;
                $this->assertArrayHasKey($cle, $fr, "{$chemin} lit trad.{$cle}, absente de lang/fr/js.php");
            }
        }
        $this->assertSame([], array_values(array_diff(array_keys($fr), array_keys($lues))), 'clé de js.php que plus aucun script ne lit');
    }

    public function test_the_footer_hands_the_catalogue_to_the_scripts(): void
    {
        $pied = file_get_contents(resource_path('views/backend/partials/footer.blade.php'));
        $this->assertStringContainsString("var trad = @json(__('js'));", $pied);
        // les soldes affichés par les scripts : FCFA entiers, séparateur français
        $this->assertStringContainsString("function montantFcfa(n) { return Math.round(Number(n) || 0).toLocaleString('fr-FR')", $pied);

        app()->setLocale('fr');
        $this->assertSame('Statut mis à jour.', __('js.status_updated'));
        $this->assertSame('Ce mois-ci', __('js.this_month'));
    }

    /** Le format et le séparateur de la période sont lus côté serveur : ils ne se traduisent pas. */
    public function test_the_date_range_keeps_the_format_the_filters_parse(): void
    {
        foreach (['date-range-picker/date-range-picker-custom.js', 'date-range-picker/dashboard-date-range-picker-custom.js'] as $script) {
            $source = file_get_contents(public_path('backend/js/' . $script));
            $this->assertStringContainsString("format: 'MM/DD/YYYY'", $source, $script);
            $this->assertStringContainsString('separator: " To "', $source, $script);
        }
    }
}
