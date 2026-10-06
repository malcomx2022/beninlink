<?php

namespace Tests\Feature;

use App\Http\Kernel;
use App\Http\Middleware\ApiLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S90 (T6)** — l'API négocie sa langue par `Accept-Language`.
 *
 * Le socle ignorait l'en-tête : les `message` de l'enveloppe sortaient dans la
 * locale du serveur quoi que l'app demande. La règle : la première langue de
 * l'en-tête que `config/locales.php` sert (`fr`, `en`), sinon le français. Une
 * app qui n'envoie rien ne voit aucune différence — les deux apps sont françaises.
 */
class ApiLocaleTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const CLE = 'cle-de-test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        config(['rxcourier.api_key' => self::CLE, 'app.app_installed' => 'yes']);
    }

    /**
     * Une connexion sans identifiants : 422 et un `message` traduit — le témoin le plus simple.
     *
     * ⚠️ Le client de test de Laravel (Symfony `Request::create()`) envoie
     * `Accept-Language: en-us,en;q=0.5` **par défaut** : « sans en-tête » se dit
     * ici par un en-tête vide, ce qu'une app qui n'envoie rien produit en vrai.
     */
    private function message(array $entetes = []): string
    {
        return $this->postJson('/api/v10/signin', ['merchant_id' => 'x', 'password' => 'zzzzzz'], array_merge(['apiKey' => self::CLE, 'Accept-Language' => ''], $entetes))
            ->assertStatus(401)
            ->json('message');
    }

    public function test_without_a_header_the_api_answers_in_french(): void
    {
        $this->assertSame(__('auth.credentials_msg', [], 'fr'), $this->message());
        $this->assertNotSame(__('auth.credentials_msg', [], 'fr'), __('auth.credentials_msg', [], 'en'), 'fixture : les deux langues diffèrent');
    }

    public function test_accept_language_en_is_honoured(): void
    {
        $this->assertSame(__('auth.credentials_msg', [], 'en'), $this->message(['Accept-Language' => 'en']));
        $this->assertSame(__('auth.credentials_msg', [], 'en'), $this->message(['Accept-Language' => 'en-GB,en;q=0.9']), 'le sous-code principal suffit');
    }

    public function test_an_unserved_language_falls_back_to_french(): void
    {
        foreach (['zh', 'bn-BD', '*', 'xx;q=1'] as $entete) {
            $this->assertSame(__('auth.credentials_msg', [], 'fr'), $this->message(['Accept-Language' => $entete]), "« {$entete} »");
        }
    }

    public function test_the_preference_order_is_the_header_s(): void
    {
        $this->assertSame(__('auth.credentials_msg', [], 'fr'), $this->message(['Accept-Language' => 'en;q=0.5,fr;q=0.9']), 'q décide');
        $this->assertSame(__('auth.credentials_msg', [], 'en'), $this->message(['Accept-Language' => 'zh,en,fr']), 'la première servie, dans l\'ordre');
    }

    public function test_negotiation_is_pure(): void
    {
        $servies = ['fr', 'en'];
        $this->assertSame('en', ApiLocale::negocier('en-GB,en;q=0.8,fr;q=0.5', $servies));
        $this->assertSame('fr', ApiLocale::negocier('de, fr;q=0.3, en;q=0.2', $servies));
        $this->assertNull(ApiLocale::negocier('', $servies));
        $this->assertNull(ApiLocale::negocier('en;q=0', $servies), 'q=0 est un refus');
        $this->assertSame('en', ApiLocale::negocier('EN_us', $servies), 'casse et séparateur indifférents');
    }

    /** La locale est remise après la réponse : une requête `en` ne colore pas la suivante. */
    public function test_the_locale_is_restored_after_the_response(): void
    {
        $avant = app()->getLocale();
        $this->message(['Accept-Language' => 'en']);
        $this->assertSame($avant, app()->getLocale());
    }

    /** Le garde vit sur le groupe `api` et seulement là : le web garde sa session (`LanguageManager`). */
    public function test_the_guard_sits_on_the_api_group_only(): void
    {
        $groupes = app(Kernel::class)->getMiddlewareGroups();

        $this->assertContains(ApiLocale::class, $groupes['api']);
        $this->assertNotContains(ApiLocale::class, $groupes['web']);
    }
}
