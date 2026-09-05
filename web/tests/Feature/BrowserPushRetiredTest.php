<?php

namespace Tests\Feature;

use App\Http\Services\PushNotificationService;
use App\Models\Backend\Merchant;
use App\Models\Backend\PushNotification;
use App\Models\User;
use App\Repositories\PushNotification\PushNotificationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S22 / D12 — le push navigateur du back-office est retiré.
 *
 * D11 a rebranché le push des deux apps mobiles. Restait le canal du
 * back-office, et il était pire que mort :
 *
 *   - il inscrivait le navigateur de chaque agent au projet Firebase **de
 *     l'éditeur** (`we-courier-81101`), clés en clair dans la page ;
 *   - il chargeait le SDK Firebase depuis un tiers **sur chaque page** et
 *     réclamait la permission de notifier à chaque chargement ;
 *   - il envoyait ensuite par l'API FCM « legacy », arrêtée en juin 2024 :
 *     la permission était demandée pour un canal qui ne livrait rien.
 *
 * Ces tests fixent le retrait, y compris ce qu'on ne voit pas : un service
 * worker déjà installé survit au déploiement.
 */
class BrowserPushRetiredTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
    }

    public function test_no_back_office_page_loads_firebase_any_more(): void
    {
        foreach (['backend/partials/footer.blade.php', 'installer/index.blade.php'] as $vue) {
            $source = file_get_contents(resource_path('views/' . $vue));

            $this->assertStringNotContainsString('gstatic.com/firebasejs', $source, $vue);
            $this->assertStringNotContainsString('firebase.initializeApp', $source, $vue);
            $this->assertStringNotContainsString('apiKey', $source, $vue);
            $this->assertStringNotContainsString('requestPermission', $source, $vue);

            // La vue compile toujours (les @if/@endif restants s'équilibrent).
            $compile = Blade::compileString($source);
            $this->assertSame(substr_count($compile, '<?php if('), substr_count($compile, '<?php endif; ?>'), $vue);
        }
    }

    public function test_the_service_worker_unregisters_itself_instead_of_holding_the_keys(): void
    {
        // Le fichier garde son chemin : c'est celui que les navigateurs déjà
        // abonnés interrogent. Sans cela, ils resteraient inscrits au projet
        // d'un tiers, le code de la page ayant beau disparaître.
        $sw = file_get_contents(public_path('firebase-messaging-sw.js'));

        $this->assertStringContainsString('registration', $sw);
        $this->assertStringContainsString('unregister', $sw);
        $this->assertStringNotContainsString('apiKey', $sw);
        $this->assertStringNotContainsString('messagingSenderId', $sw);
        $this->assertStringNotContainsString('importScripts', $sw);
    }

    public function test_the_token_route_answers_but_stores_nothing(): void
    {
        $admin = User::where('user_type', 1)->firstOrFail();

        // La route reste servie : un navigateur portant encore l'ancien
        // service worker la rappellera au prochain chargement, et une 404
        // dans la console de chaque agent n'apprendrait rien à personne.
        // (Elle est appelée directement : les routes `web` du socle ne sont
        // montées que sur un domaine locataire, hors de portée des tests.)
        $this->assertNotNull(\Illuminate\Support\Facades\Route::getRoutes()->getByName('notification-store.token'));

        $reponse = app(\App\Http\Controllers\Backend\WebNotificationController::class)
            ->store(new Request(['token' => 'jeton-navigateur']));

        $this->assertSame(410, $reponse->getStatusCode());
        $this->assertNull($admin->fresh()->web_token);
    }

    public function test_the_outgoing_web_transport_is_gone(): void
    {
        $source = file_get_contents(base_path('app/Http/Services/PushNotificationService.php'));

        // Plus aucun envoi vers l'API arrêtée : la seule occurrence restante
        // est l'explication en commentaire.
        $this->assertSame(0, substr_count($source, 'registration_ids'));
        $this->assertStringNotContainsString("'https://fcm.googleapis.com/fcm/send'", $source);

        // La méthode garde sa signature (trois appels dans le socle) et ne
        // sort plus : appelée avec des jetons, elle ne lève rien.
        $this->assertTrue(app(PushNotificationService::class)
            ->sendWebNotification(new PushNotification(), false, 'all', ['jeton-a', 'jeton-b']));
    }

    public function test_the_migration_purges_the_tokens_and_keeps_the_column(): void
    {
        DB::table('users')->whereKey(User::where('user_type', 1)->value('id'))
            ->update(['web_token' => 'jeton-firebase-editeur']);

        (require database_path('migrations/2026_09_05_230000_retire_browser_push_tokens.php'))->up();

        $this->assertSame(0, DB::table('users')->whereNotNull('web_token')->count());
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('users', 'web_token'));
    }

    // — ce que le retrait ne doit pas casser ---------------------------------

    public function test_an_admin_message_is_still_recorded_without_the_dead_transport(): void
    {
        $stocke = app(PushNotificationRepository::class)->store(new Request([
            'title' => 'Fermeture exceptionnelle',
            'description' => 'Les agences ferment vendredi à 13 h.',
            'role_id' => (string) \App\Enums\UserType::MERCHANT,
            'user_id' => '',
            'merchant_id' => null,
        ]));

        $this->assertTrue($stocke, "l'écriture ne dépend pas de l'acheminement");
        $this->assertSame(1, PushNotification::count());
    }

    public function test_the_message_still_reaches_the_merchants_of_its_company(): void
    {
        $marchand = Merchant::firstOrFail();

        $message = new PushNotification();
        $message->forceFill([
            'company_id' => $marchand->company_id,
            'title' => 'Fermeture exceptionnelle',
            'description' => 'Les agences ferment vendredi à 13 h.',
            'type' => 'all',
        ])->save();

        // C'est le canal navigateur qui disparaît, pas le message : le fil du
        // marchand (D11) le porte toujours, et le pousse sur son téléphone.
        $entree = $marchand->user->notifications()->firstOrFail()->data;
        $this->assertSame('Fermeture exceptionnelle', $entree['title']);
    }

    public function test_a_failure_no_longer_dumps_the_stack_into_the_admin_s_browser(): void
    {
        $source = file_get_contents(base_path('app/Repositories/PushNotification/PushNotificationRepository.php'));

        // `dd()` vidait l'exception dans la réponse et interrompait tout ;
        // la seule occurrence restante est celle qui l'explique en commentaire.
        $this->assertSame(0, preg_match('/^\s*dd\(/m', $source), 'dd() en production');
    }

    public function test_an_admin_does_not_modify_another_company_s_message(): void
    {
        $ailleurs = $this->marchandDUneAutreSociete('M');

        $message = new PushNotification();
        $message->forceFill([
            'company_id' => $ailleurs->company_id,
            'title' => "Message d'un autre transporteur",
            'description' => 'Intact.',
            'type' => 'all',
        ])->save();

        $modifie = app(PushNotificationRepository::class)->update($message->id, new Request([
            'title' => 'Détourné',
            'description' => 'Détourné.',
            'type' => 'all',
            'user_id' => null,
            'merchant_id' => null,
        ]));

        $this->assertFalse($modifie);
        $this->assertSame("Message d'un autre transporteur", $message->fresh()->title);
    }
}
