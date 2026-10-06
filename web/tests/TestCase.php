<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * ⚠️ Le client de test de Laravel (Symfony `Request::create()`) envoie
     * `Accept-Language: en-us,en;q=0.5` par défaut. Depuis **S90** l'API négocie sa
     * langue par cet en-tête : sans cette ligne, chaque test d'API tournerait en
     * anglais alors que les apps, qui n'envoient rien, reçoivent le français.
     * Un test qui veut une langue la demande explicitement par l'en-tête.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);
    }
}
