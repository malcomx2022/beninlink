<?php

namespace Tests\Feature;

use App\Enums\PayoutSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * D10 — le module « payout / paiement en ligne » est coupé.
 *
 * Cinq chemins (Stripe, PayPal, bKash, Skrill, Razorpay) permettaient de régler
 * un marchand — ou de l'encaisser — hors du flux de retrait. Ils partagent
 * quatre défauts, constatés en écrivant le rapprochement solde / relevé (D9) :
 *
 *   - ils déplacent `merchants.current_balance` **sans écrire au relevé** ;
 *   - ils facturent en **BDT** codé en dur ;
 *   - ils lisent marchand et compte bancaire **sans scope société** ;
 *   - **PayPal et Razorpay ne vérifient rien** auprès du fournisseur : une
 *     requête avec un identifiant inventé éteint la dette du marchand et
 *     crédite le transporteur d'un argent jamais reçu.
 *
 * On coupe le **module**, pas les passerelles : `stripe_status` sert aussi
 * l'abonnement SaaS de la plateforme, qui ne touche aucun solde marchand.
 * C'est ce que fixe le dernier test.
 */
class OnlinePayoutModuleDisabledTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    /** Le module est coupé par défaut. */
    public function test_the_module_is_off(): void
    {
        $this->assertFalse(onlinePayoutEnabled());
    }

    /**
     * Les routes du module ne sont plus enregistrées. Le test lit la
     * déclaration : les routes locataires ne sont montées qu'avec un domaine
     * de locataire, hors de portée d'un test.
     */
    public function test_every_route_of_the_module_is_gated(): void
    {
        $source = file_get_contents(base_path('routes/web.php'));

        $chemins = [
            "[PayoutController::class, 'stripePost']",
            "[PayoutController::class, 'razorpayPost']",
            "[PayoutController::class, 'paypalpayment']",
            "[AdminSkrillController::class, 'makePayment']",
            "[AdminBkashController::class, 'bkashExecute']",
            "[OnlinePaymentController::class, 'stripePost']",
            "[OnlinePaymentController::class, 'paypalpayment']",
            "[SkrillController::class, 'makePayment']",
            "[BkashController::class, 'bkashExecute']",
        ];

        foreach ($chemins as $chemin) {
            $position = strpos($source, $chemin);
            $this->assertNotFalse($position, "route introuvable : {$chemin}");

            // La garde ouvrante la plus proche au-dessus doit être la nôtre.
            $avant = substr($source, 0, $position);
            $this->assertStringContainsString(
                'onlinePayoutEnabled()',
                substr($avant, strrpos($avant, 'Route::prefix') ?: 0),
                "route non gardée : {$chemin}",
            );
        }
    }

    /** Et les deux écrans qui y menaient ne proposent plus rien. */
    public function test_the_two_screens_no_longer_offer_the_module(): void
    {
        $vues = [
            'backend/payout/index.blade.php',
            'backend/merchant_panel/onlinepayment/index.blade.php',
        ];

        foreach ($vues as $vue) {
            $source = file_get_contents(resource_path('views/' . $vue));
            $this->assertStringContainsString('onlinePayoutEnabled()', $source, $vue);

            // Aucun bouton du module hors d'une garde : chaque `route(...)` du
            // module vit dans un `@if` qui la porte.
            foreach (['stripe', 'paypal', 'razorpay', 'skrill', 'bkash'] as $passerelle) {
                foreach (explode("\n", $source) as $numero => $ligne) {
                    if (!str_contains($ligne, "route('") || !str_contains(strtolower($ligne), $passerelle)) {
                        continue;
                    }
                    $garde = $this->gardeOuvranteAvant($source, $numero);
                    $this->assertStringContainsString(
                        'onlinePayoutEnabled()',
                        $garde,
                        "{$vue} ligne " . ($numero + 1) . " : lien {$passerelle} hors garde",
                    );
                }
            }

            $compiled = Blade::compileString($source);
            $this->assertSame(
                substr_count($compiled, '<?php if('),
                substr_count($compiled, '<?php endif; ?>'),
                "@if/@endif déséquilibrés dans {$vue}",
            );
        }
    }

    /**
     * Le garde-fou de la décision : couper le module ne coupe **pas** les
     * passerelles. `stripe_status` sert aussi l'abonnement SaaS de la
     * plateforme, qui ne déplace aucun solde marchand — le désactiver par
     * identité de passerelle l'aurait emporté avec.
     */
    public function test_cutting_the_module_does_not_disable_the_gateways_themselves(): void
    {
        foreach ([PayoutSetup::STRIPE, PayoutSetup::PAYPAL, PayoutSetup::BKASH, PayoutSetup::SKRILL, PayoutSetup::RAZORPAY] as $passerelle) {
            $this->assertTrue(gatewayEnabled($passerelle), "passerelle {$passerelle}");
        }

        // Celles que S21 a coupées le restent, elles.
        $this->assertFalse(gatewayEnabled(PayoutSetup::AAMARPAY));
        $this->assertFalse(gatewayEnabled(PayoutSetup::SSL_COMMERZ));
    }

    /** La dernière ligne `@if` ouverte avant la ligne donnée. */
    private function gardeOuvranteAvant(string $source, int $numero): string
    {
        $lignes = explode("\n", $source);
        for ($i = $numero; $i >= 0; $i--) {
            if (str_contains($lignes[$i], '@if(')) {
                return $lignes[$i];
            }
        }

        return '';
    }
}
