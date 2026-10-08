<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\Concerns\FindsHardcodedText;
use Tests\TestCase;

/**
 * **S116** — le panneau marchand parle français et compte en FCFA.
 *
 * Après l'inscription (S115), la PME vit dans `merchant_panel/`. Un relevé des
 * textes écrits en dur y a trouvé 140 nœuds, dont le plus grave n'était pas de
 * l'anglais : **« Tk »** (taka) après le prix de l'emballage, sur la création, la
 * modification et la duplication d'un colis — « 1 000 FCFA Tk », `formatAmount()`
 * portant déjà la devise. L'export PDF des colis titrait « Cash Collection (TK) »
 * en anglais, sur des montants bruts ; les paiements reçus nommaient les
 * portefeuilles du Bangladesh (« Bkash », « Rocket », « Nagad ») ; les priorités
 * d'assistance, « Paid At », « Download File », « Toggle Dropdown » restaient en
 * anglais.
 *
 * Règle : aucune vue du panneau marchand n'a de texte littéral, hors unités et
 * pages exemptées **avec leur motif** (inatteignables).
 */
class MerchantPanelSpeaksFrenchTest extends TestCase
{
    use FindsHardcodedText;

    /** Unités et formats affichés tels quels. */
    private const TELS_QUELS = ['kg', 'FCFA', 'CSV'];

    /** Pages qu'aucun marchand n'atteint, et pourquoi. */
    private const EXEMPTEES = [
        'sslecommerz/exampleHosted.blade.php' => 'page de démonstration SSLCommerz, aucune route ne la sert',
        'sslecommerz/exampleEasycheckout.blade.php' => 'page de démonstration SSLCommerz, aucune route ne la sert',
        'onlinepayment/bkash.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'onlinepayment/paypal.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'onlinepayment/stripe.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'onlinepayment/skrill.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'onlinepayment/sslcommerz.blade.php' => 'route sous gatewayEnabled(SSL_COMMERZ) — passerelle désactivée (S21)',
        'onlinepayment/aamarpay.blade.php' => 'route sous gatewayEnabled(AAMARPAY) — passerelle désactivée (S21)',
    ];

    private function vues(): Finder
    {
        return Finder::create()->files()->in(resource_path('views/backend/merchant_panel'))->name('*.blade.php');
    }

    public function test_no_view_of_the_merchant_panel_holds_hardcoded_text(): void
    {
        $litteraux = [];
        $lues = 0;
        foreach ($this->vues() as $vue) {
            if (array_key_exists($vue->getRelativePathname(), self::EXEMPTEES)) {
                continue;
            }
            $lues++;
            foreach ($this->textesEnDur($vue->getContents(), self::TELS_QUELS) as $texte) {
                $litteraux[] = "{$vue->getRelativePathname()} : « {$texte} »";
            }
        }

        $this->assertGreaterThan(40, $lues, 'le parcours a bien lu le panneau');
        $this->assertSame([], $litteraux, "texte écrit en dur dans le panneau marchand :\n" . implode("\n", $litteraux));
    }

    public function test_every_exempted_page_still_exists(): void
    {
        foreach (array_keys(self::EXEMPTEES) as $vue) {
            $this->assertFileExists(resource_path('views/backend/merchant_panel/' . $vue), "{$vue} : exemption sans objet, la retirer");
        }
    }

    /** Le taka ne s'affiche nulle part dans le panneau : la devise est le FCFA, et `formatAmount()` la porte. */
    public function test_no_taka_is_displayed_to_a_merchant(): void
    {
        foreach ($this->vues() as $vue) {
            if (array_key_exists($vue->getRelativePathname(), self::EXEMPTEES)) {
                continue;
            }
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', $vue->getContents());
            $this->assertDoesNotMatchRegularExpression('/\bTk\b|\(TK\)|\bBDT\b|৳/u', $source, $vue->getRelativePathname());
        }
    }

    /** Les portefeuilles mobiles se disent « Mobile Money » au Bénin, pas bKash, Rocket ou Nagad. */
    public function test_payments_received_do_not_name_bangladeshi_wallets(): void
    {
        foreach (['online_payment_received/index.blade.php', 'onlinepayment/payment_list.blade.php'] as $vue) {
            $source = file_get_contents(resource_path('views/backend/merchant_panel/' . $vue));
            foreach (['Bkash', 'Rocket', 'Nagad'] as $portefeuille) {
                $this->assertStringNotContainsString($portefeuille, $source, $vue);
            }
            $this->assertStringContainsString("__('Mobile Money')", $source, $vue);
        }
    }
}
