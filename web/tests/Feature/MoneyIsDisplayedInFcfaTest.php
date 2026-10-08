<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S121** — un montant affiché passe par `formatAmount()` : FCFA entiers, séparateur français.
 *
 * Les colonnes d'argent du socle sont des `decimal` (D15 : on ne les migre pas, on les **affiche**
 * entières). Un relevé des vues a trouvé 66 montants écrits bruts dans un nœud de texte — « 15000.00 »
 * au lieu de « 15 000 FCFA » : soldes des comptes dans les listes de virement, de paiement marchand et
 * d'agence, montant des salaires, prix des plans, COD et encaissement sur la fiche colis, et le
 * récapitulatif d'un colis modifié ou dupliqué au panneau marchand (`?? '0.00'`).
 *
 * Deux scripts relisaient un solde affiché par `parseInt(… .text())` pour refuser une dépense plus
 * forte que le compte : sur « 15 000 FCFA », `parseInt` rend 15. Ils lisent par `montantLu()` (pied de
 * page), qui comprend le texte formaté comme l'ancien.
 */
class MoneyIsDisplayedInFcfaTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    /** Champs d'argent du socle, tels que les vues les nomment. */
    private const CHAMPS = 'amount|balance|cash_collection|delivery_charge|cod_amount|cod_charges?|vat_amount|total_delivery_amount|current_payable|price|liquid_fragile_amount|packaging_amount|return_charges|payable|salary|income|expense|charge';

    /** Pages qu'aucun utilisateur n'atteint (mêmes motifs que S116 et S117). */
    private const EXEMPTEES = ['installer/', 'backend/payout/', 'backend/merchant_panel/onlinepayment/', 'backend/merchant_panel/sslecommerz/'];

    /** Expressions qui ne sont pas un montant, ou déjà formatées. */
    private const AUTRES = '/formatAmount|number_format|__\(|trans\(|count\(|->links|firstItem|lastItem|total\(\)|_id\b|->title|->name|date|Carbon|->format\(|_type|percent|weight/';

    /** @return list<string> les montants affichés bruts d'une vue */
    private function montantsBruts(string $source): array
    {
        $expressions = [];
        $source = preg_replace_callback('/\{\{(?!--).*?\}\}/s', function ($m) use (&$expressions) {
            $expressions[] = $m[0];

            return "\x00" . (count($expressions) - 1) . "\x00";
        }, $source);
        $masque = preg_replace_callback('/\{\{--.*?--\}\}|<script.*?<\/script>|<style.*?<\/style>/s', fn ($m) => str_repeat(' ', strlen($m[0])), $source);
        $masque = preg_replace_callback('/@\w+\s*\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)/', fn ($m) => str_repeat(' ', strlen($m[0])), $masque);

        $bruts = [];
        preg_match_all('/>([^<>]*)</', $masque, $noeuds);
        foreach ($noeuds[1] as $noeud) {
            preg_match_all("/\x00(\\d+)\x00/", $noeud, $refs);
            foreach ($refs[1] as $i) {
                $expression = trim(preg_replace('/^\{\{\s*|\s*\}\}$/s', '', $expressions[(int) $i]));
                if (preg_match(self::AUTRES, $expression)) {
                    continue;
                }
                if (preg_match('/(->|\[[\'"])\w*(' . self::CHAMPS . ')\w*[\'"\]]?\s*(\?\?.*)?$/i', $expression)) {
                    $bruts[] = $expression;
                }
            }
        }

        return $bruts;
    }

    public function test_no_amount_is_displayed_raw(): void
    {
        $bruts = [];
        $lues = 0;
        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $vue) {
            $chemin = str_replace('\\', '/', $vue->getRelativePathname());
            foreach (self::EXEMPTEES as $exemptee) {
                if (str_starts_with($chemin, $exemptee)) {
                    continue 2;
                }
            }
            $lues++;
            foreach ($this->montantsBruts($vue->getContents()) as $expression) {
                $bruts[] = "{$chemin} : {{ {$expression} }}";
            }
        }

        $this->assertGreaterThan(300, $lues);
        $this->assertSame([], $bruts, "montant affiché sans formatAmount() :\n" . implode("\n", $bruts));
    }

    /** Le détecteur reconnaît bien la forme qu'il refuse — sinon le test ci-dessus ne prouverait rien. */
    public function test_the_detector_sees_a_raw_amount_and_spares_inputs(): void
    {
        $this->assertSame(['$account->balance'], $this->montantsBruts('<td>{{ $account->balance }}</td>'));
        $this->assertSame([], $this->montantsBruts('<input value="{{ $account->balance }}">'));
        $this->assertSame([], $this->montantsBruts('<td>{{ formatAmount($account->balance) }}</td>'));
        $this->assertSame(["\$parcel->vat_amount ?? '0.00'"], $this->montantsBruts("<span id=\"VatAmount\">{{ \$parcel->vat_amount ?? '0.00' }}</span>"));
    }

    public function test_amounts_read_in_whole_fcfa(): void
    {
        $this->seedTenant();
        $this->assertSame("15\u{00A0}000\u{00A0}" . currencySymbol(), formatAmount('15000.00'));
        $this->assertSame("1\u{00A0}501", formatAmount(1500.5, false));
    }

    /** Un script ne relit plus un solde affiché par parseInt : « 15 000 FCFA » y vaut 15. */
    public function test_scripts_read_a_displayed_amount_with_montant_lu(): void
    {
        foreach (Finder::create()->files()->in(public_path('backend/js'))->name('*.js')->notName('*.min.js') as $script) {
            $this->assertDoesNotMatchRegularExpression(
                '/parse(?:Int|Float)\(\s*\$\([\'"][^\'"]+[\'"]\)\.(?:text|html)\(\)/',
                $script->getContents(),
                $script->getRelativePathname()
            );
        }
        $this->assertStringContainsString('function montantLu(texte)', file_get_contents(resource_path('views/backend/partials/footer.blade.php')));
        $this->assertStringContainsString("montantLu($('#account_balance_').text())", file_get_contents(public_path('backend/js/expense/salary.js')));
    }
}
