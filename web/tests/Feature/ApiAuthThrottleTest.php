<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * **S96** — les points d'entrée d'authentification de l'API sont limités contre la force brute.
 *
 * Le login web l'était (`ThrottlesLogins`, 5 essais), `password/email` aussi (`throttle:5,1`,
 * S-antérieur) ; `signin`, `deliveryman/login`, `otp-verification`, `resend-otp` et
 * `password/reset` ne connaissaient que la borne globale du groupe `api` (60/min par adresse) :
 * soixante mots de passe par minute et par compte, sans fin. Le limiteur `connexion` pose deux
 * bornes : **5/min par identifiant + adresse** (un compte visé) et **30/min par adresse** (une
 * adresse qui énumère des comptes), et répond dans l'enveloppe de l'API, en français.
 */
class ApiAuthThrottleTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const CLE = 'cle-de-test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        config(['rxcourier.api_key' => self::CLE, 'app.app_installed' => 'yes']);
        Cache::flush(); // le compteur du limiteur vit dans le cache : chaque test repart de zéro
    }

    private function tentative(string $identifiant, array $entetes = [])
    {
        return $this->postJson('/api/v10/signin', ['merchant_id' => $identifiant, 'password' => 'faux-mot-de-passe'],
            array_merge(['apiKey' => self::CLE, 'Accept-Language' => ''], $entetes));
    }

    public function test_the_sixth_attempt_on_the_same_account_is_refused_in_the_api_envelope(): void
    {
        foreach (range(1, 5) as $i) {
            $this->tentative('M-VISE')->assertStatus(401);
        }

        $refus = $this->tentative('M-VISE')->assertStatus(429);
        $refus->assertHeader('Retry-After');
        $refus->assertJsonPath('success', false)->assertJsonPath('data', []);
        $this->assertStringContainsString('Trop de tentatives', $refus->json('message'));
        $this->assertStringContainsString((string) $refus->headers->get('Retry-After'), $refus->json('message'), 'le délai annoncé est celui de l\'en-tête');
    }

    /** La casse et les espaces ne font pas un autre compte. */
    public function test_the_identifier_is_normalised(): void
    {
        foreach (['M-VISE', 'm-vise', ' M-Vise ', 'M-VISE', 'm-VISE'] as $forme) {
            $this->tentative($forme)->assertStatus(401);
        }
        $this->tentative('M-vise')->assertStatus(429);
    }

    /** Un autre compte depuis la même adresse n'est pas bloqué par le premier : une agence derrière un NAT reste connectable. */
    public function test_another_account_from_the_same_address_is_not_locked_out_by_the_first(): void
    {
        foreach (range(1, 6) as $i) {
            $this->tentative('M-VISE');
        }
        $this->tentative('M-AUTRE')->assertStatus(401);
    }

    /** … mais une adresse qui énumère des comptes bute sur la borne par adresse : 30 par minute. */
    public function test_an_address_that_enumerates_accounts_is_capped(): void
    {
        foreach (range(1, 30) as $i) {
            $this->tentative('M-' . $i)->assertStatus(401, "tentative {$i}");
        }
        $this->tentative('M-31')->assertStatus(429);
    }

    /** La réponse suit la langue négociée (S90). */
    public function test_the_refusal_speaks_the_negotiated_language(): void
    {
        foreach (range(1, 5) as $i) {
            $this->tentative('M-VISE');
        }
        $message = $this->tentative('M-VISE', ['Accept-Language' => 'en'])->assertStatus(429)->json('message');
        $this->assertStringContainsString('Too many', $message);
        $this->assertNotSame(__('auth.throttle', ['seconds' => 1], 'fr'), __('auth.throttle', ['seconds' => 1], 'en'), 'fixture : les deux langues diffèrent');
    }

    public function test_every_authentication_entry_point_carries_the_limiter(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('POST', $r->methods(), true))
            ->keyBy(fn ($r) => $r->uri());

        foreach (['signin', 'deliveryman/login', 'otp-verification', 'resend-otp', 'password/email', 'password/reset'] as $chemin) {
            $route = $routes->get('api/v10/' . $chemin);
            $this->assertNotNull($route, $chemin);
            $this->assertContains('throttle:connexion', $route->gatherMiddleware(), $chemin . ' : limité par le limiteur « connexion »');
        }
    }

    public function test_the_courier_login_is_limited_too(): void
    {
        foreach (range(1, 5) as $i) {
            $this->postJson('/api/v10/deliveryman/login', ['driver_id' => 'L-VISE', 'password' => 'faux-mot-de-passe'], ['apiKey' => self::CLE, 'Accept-Language' => ''])
                ->assertStatus(401);
        }
        $this->postJson('/api/v10/deliveryman/login', ['driver_id' => 'L-VISE', 'password' => 'faux-mot-de-passe'], ['apiKey' => self::CLE, 'Accept-Language' => ''])
            ->assertStatus(429);
    }
}
