<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Tant que `APP_INSTALLED` ne vaut pas `yes`, `IsInstalledMiddleware`
     * renvoie toute requête vers l'installeur web. C'est le comportement
     * attendu du socle We Courier sur une installation neuve.
     */
    public function test_the_application_redirects_to_the_installer(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('install');
    }
}
