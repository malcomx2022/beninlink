<?php

namespace Tests\Feature;

use App\Mail\CompanySignup;
use App\Mail\MerchantSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MountsTenantRoutes;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S115** — le parcours d'une PME pilote parle français, du formulaire au courriel.
 *
 * S112 à S114 tenaient les catalogues. Restait le texte **écrit en dur** dans les
 * vues, qu'aucun catalogue ne voit : le formulaire d'inscription (« Registrations
 * Form », « Register My Account »), la confirmation du code (« Check Your Phone… »),
 * le courriel de bienvenue (« Thank you for your interest in becoming an merchant »,
 * sujet « Welcome to new merchant », couleur violette de l'éditeur) et le
 * portefeuille (« You are low on balance »). Les mêmes, côté société.
 *
 * Règle tenue ici : dans ces vues, **tout texte visible passe par `__()`** — un
 * texte littéral hors `{{ }}` est refusé, quelle que soit sa langue.
 */
class MerchantJourneySpeaksFrenchTest extends TestCase
{
    use MountsTenantRoutes;
    use RefreshDatabase;
    use SeedsTenant;

    /** Les écrans et courriels qu'une PME traverse avant son premier colis. */
    private const PARCOURS = [
        'backend/merchant/sign_up.blade.php',
        'backend/merchant/verification.blade.php',
        'backend/merchant/mail/signup.blade.php',
        'backend/super-admin/company/company_signup.blade.php',
        'backend/super-admin/company/verification.blade.php',
        'backend/super-admin/company/mail/signup.blade.php',
        'auth/register.blade.php',
        'backend/merchant_panel/mywallet/index.blade.php',
    ];

    /** Noms propres affichés tels quels. */
    private const TELS_QUELS = ['Facebook', 'Twitter'];

    public function test_every_visible_text_of_the_journey_goes_through_the_translator(): void
    {
        $litteraux = [];
        foreach (self::PARCOURS as $vue) {
            $source = file_get_contents(resource_path('views/' . $vue));
            // D'abord les expressions et directives Blade (`->` y contient un `>`), puis les nœuds de texte.
            $source = preg_replace('/\{\{--.*?--\}\}|<script.*?<\/script>|<style.*?<\/style>|<!--.*?-->|\{\{.*?\}\}|\{!!.*?!!\}/s', ' ', $source);
            $source = preg_replace('/@\w+\s*\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)|@\w+/s', ' ', $source);
            preg_match_all('/>([^<>]*)</', $source, $noeuds);
            foreach ($noeuds[1] as $noeud) {
                $texte = trim(preg_replace('/[\s:,.;!?()\-]+/u', ' ', $noeud));
                if (preg_match('/\p{L}{2,}/u', $texte) && ! in_array($texte, self::TELS_QUELS, true)) {
                    $litteraux[] = "{$vue} : « {$texte} »";
                }
            }
        }

        $this->assertSame([], $litteraux, "texte écrit en dur dans le parcours PME :\n" . implode("\n", $litteraux));
    }

    public function test_the_two_welcome_mails_read_in_french_in_the_platform_colours(): void
    {
        $this->seedTenant();
        app()->setLocale('fr');

        foreach ([MerchantSignup::class, CompanySignup::class] as $classe) {
            $mail = new $classe([]);
            $mail->build();
            $rendu = html_entity_decode($mail->render(), ENT_QUOTES);

            $this->assertSame('Bienvenue sur ' . settings()->name, $mail->subject, $classe);
            $this->assertStringContainsString('<html lang="fr">', $rendu, $classe);
            $this->assertStringContainsString('Bonjour', $rendu, $classe);
            $this->assertStringContainsString('Une question ? Écrivez-nous à', $rendu, $classe);
            $this->assertStringContainsString('#12503A', $rendu, $classe);
            foreach (['#7e0095', 'Thank you for your interest', 'Welcome to new', 'Get download', 'anytime'] as $socle) {
                $this->assertStringNotContainsString($socle, $rendu . $mail->subject, "{$classe} : « {$socle} »");
            }
        }
    }

    public function test_the_merchant_sign_up_page_reads_in_french(): void
    {
        $this->seedTenant();
        $this->mountTenantRoutes();

        $page = html_entity_decode($this->get(self::HOTE . '/merchant/sign-up')->assertOk()->getContent(), ENT_QUOTES);

        foreach (['Formulaire d\'inscription', 'Renseignez vos informations.', 'Choisir une agence', 'Créer mon compte', 'Déjà inscrit ?'] as $attendu) {
            $this->assertStringContainsString($attendu, $page);
        }
        foreach (['Registrations Form', 'Register My Account', 'Already member', 'Select Hub'] as $socle) {
            $this->assertStringNotContainsString($socle, $page);
        }
    }
}
