<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\FindsHardcodedText;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S117** — le back-office du transporteur parle français, et ses comptes sont béninois.
 *
 * S116 tenait le panneau marchand. Le relevé du reste des vues a trouvé 404 textes écrits en
 * dur dans 133 fichiers : les écrans de colis (« Send SMS for pickup man », « Select delivery
 * man », « Tk » après le prix de l'emballage), les impressions, les rapports, les comptes et
 * les virements. Le plus grave n'était pas l'anglais : un compte du transporteur se créait en
 * choisissant **bKash, Rocket ou Nagad** (portefeuilles du Bangladesh) et sa banque parmi
 * **BB, DBBL, IB** (Bangladesh Bank, Dutch-Bangla Bank, Islami Bank), et une cinquantaine
 * d'écrans les réécrivaient en dur. La page d'activation du socle, enfin, affichait le logo et
 * le WhatsApp de l'éditeur.
 *
 * Règles tenues ici :
 * - **toute** vue, hors pages exemptées avec motif, n'a aucun texte littéral ;
 * - le libellé d'un compte vient de `lang/*\/account_gateway.php`, sa banque de
 *   `account_bank.php` — une seule source, **D16** (défaut réversible) ;
 * - aucun taka, aucune banque ni portefeuille du Bangladesh, aucune marque de l'éditeur.
 */
class BackOfficeSpeaksFrenchTest extends TestCase
{
    use FindsHardcodedText;
    use RefreshDatabase;
    use SeedsTenant;

    /** Unités, formats et sigles affichés tels quels. */
    private const TELS_QUELS = ['kg', 'FCFA', 'CSV'];

    /** Vues qu'aucun utilisateur n'atteint, et pourquoi. Un dossier se termine par « / ». */
    private const EXEMPTEES = [
        'installer/' => 'installateur du socle, fermé sur toute base installée (S88)',
        'welcome.blade.php' => 'page d\'accueil par défaut de Laravel, aucune route ne la sert',
        'backend/payout/aamarpay.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'backend/payout/bkash.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'backend/payout/paypal.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'backend/payout/razorpay.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'backend/payout/skrill.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'backend/payout/sslcommerz.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'backend/payout/stripe.blade.php' => 'route sous onlinePayoutEnabled() — module coupé (D10)',
        'backend/merchant_panel/sslecommerz/' => 'pages de démonstration SSLCommerz, aucune route ne les sert (S116)',
        'backend/merchant_panel/onlinepayment/bkash.blade.php' => 'module coupé (D10, S116)',
        'backend/merchant_panel/onlinepayment/paypal.blade.php' => 'module coupé (D10, S116)',
        'backend/merchant_panel/onlinepayment/stripe.blade.php' => 'module coupé (D10, S116)',
        'backend/merchant_panel/onlinepayment/skrill.blade.php' => 'module coupé (D10, S116)',
        'backend/merchant_panel/onlinepayment/sslcommerz.blade.php' => 'passerelle désactivée (S21, S116)',
        'backend/merchant_panel/onlinepayment/aamarpay.blade.php' => 'passerelle désactivée (S21, S116)',
    ];

    /** @return iterable<\Symfony\Component\Finder\SplFileInfo> */
    private function vuesServies(): iterable
    {
        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php')->sortByName() as $vue) {
            $chemin = str_replace('\\', '/', $vue->getRelativePathname());
            foreach (array_keys(self::EXEMPTEES) as $exemptee) {
                if ($chemin === $exemptee || (str_ends_with($exemptee, '/') && str_starts_with($chemin, $exemptee))) {
                    continue 2;
                }
            }
            yield $chemin => $vue;
        }
    }

    public function test_no_served_view_holds_hardcoded_text(): void
    {
        $litteraux = [];
        $lues = 0;
        foreach ($this->vuesServies() as $chemin => $vue) {
            $lues++;
            foreach ($this->textesEnDur($vue->getContents(), self::TELS_QUELS) as $texte) {
                $litteraux[] = "{$chemin} : « {$texte} »";
            }
        }

        $this->assertGreaterThan(300, $lues, 'le parcours a bien lu les vues');
        $this->assertSame([], $litteraux, "texte écrit en dur :\n" . implode("\n", $litteraux));
    }

    public function test_every_exempted_view_still_exists(): void
    {
        foreach (array_keys(self::EXEMPTEES) as $exemptee) {
            $chemin = resource_path('views/' . rtrim($exemptee, '/'));
            $this->assertTrue(file_exists($chemin), "{$exemptee} : exemption sans objet, la retirer");
        }
    }

    /** Ni taka, ni banque ni portefeuille du Bangladesh, ni l'éditeur du socle, dans ce qu'une vue affiche. */
    public function test_no_bangladeshi_currency_bank_wallet_or_vendor_is_displayed(): void
    {
        $trouves = [];
        foreach ($this->vuesServies() as $chemin => $vue) {
            foreach ($this->textesEnDur($vue->getContents()) as $texte) {
                if (preg_match('/\b(Tk|TK|BDT|Taka|bKash|Bkash|Rocket|Nagad|BB|DBBL|IB|WemaxDevs|CodeCanyon)\b|৳/u', $texte, $m)) {
                    $trouves[] = "{$chemin} : « {$m[0]} »";
                }
            }
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', $vue->getContents());
            // Une phrase traduite peut porter la marque : on lit aussi les arguments de __().
            // Seule exception : l'écran de réglage du module de paiement en ligne coupé (D10) nomme ses passerelles.
            if ($chemin !== 'backend/setting/payout_setup/index.blade.php' && preg_match_all('/__\(\s*\'([^\']*)\'/', $source, $phrases)) {
                foreach ($phrases[1] as $phrase) {
                    if (preg_match('/\b(Tk|bKash|Bkash|Rocket|Nagad|DBBL|WemaxDevs|CodeCanyon)\b|\(TK\)/u', $phrase, $m)) {
                        $trouves[] = "{$chemin} : __('{$phrase}')";
                    }
                }
            }
        }

        $this->assertSame([], $trouves, implode("\n", $trouves));
        $this->assertFileDoesNotExist(public_path('wemaxdevs.png'), 'le logo de l\'éditeur n\'est plus servi');
    }

    /** D16 : une seule source pour le libellé d'un compte et de sa banque, et elle est béninoise. */
    public function test_account_labels_come_from_one_beninese_source(): void
    {
        app()->setLocale('fr');
        $this->assertSame(
            [1 => 'Espèces', 2 => 'Banque', 3 => 'MTN MoMo', 4 => 'Moov Money', 5 => 'Autre Mobile Money'],
            trans('account_gateway')
        );
        $this->assertSame('Ecobank Bénin', __('account_bank.1'));
        $this->assertContains('Autre banque', trans('account_bank'));

        foreach (['backend/account/create.blade.php', 'backend/account/edit.blade.php'] as $formulaire) {
            $source = file_get_contents(resource_path('views/' . $formulaire));
            $this->assertStringContainsString("trans('account_bank')", $source, "{$formulaire} : banques lues dans account_bank");
            $this->assertMatchesRegularExpression("/account_gateway/", $source, "{$formulaire} : portefeuilles lus dans account_gateway");
        }
    }

    /** La page d'un domaine inactif ne renvoie plus vers l'éditeur du socle. */
    public function test_the_inactive_domain_page_names_no_vendor(): void
    {
        $this->seedTenant();
        app()->setLocale('fr');
        $page = html_entity_decode(view('purchase_verify')->render(), ENT_QUOTES);

        $this->assertStringContainsString('Ce domaine est inactif.', $page);
        foreach (['WemaxDevs', 'wemaxdevs', 'wa.me', 'CodeCanyon', 'purchase code'] as $editeur) {
            $this->assertStringNotContainsString($editeur, $page);
        }
    }
}
