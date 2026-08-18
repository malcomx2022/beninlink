<?php

namespace Tests\Feature;

use App\Services\Payments\FedaPayGateway;
use Tests\TestCase;

/**
 * Vérification de la signature des webhooks FedaPay.
 *
 * `.claude/rules/payments.md` exige un test couvrant l'idempotence. Ce fichier
 * couvre la **porte d'entrée** : sans signature valide, aucun webhook n'atteint
 * la logique de crédit. L'idempotence elle-même (verrou de ligne + statut déjà
 * `approved`) demande une base migrée — voir la note en fin de fichier.
 *
 * Aucun appel réseau : on ne teste pas FedaPay, on teste NOTRE vérification.
 */
class FedaPayWebhookTest extends TestCase
{
    private function gateway(): FedaPayGateway
    {
        config([
            'fedapay.webhook_secret' => 'wh_test_secret',
            'fedapay.webhook_tolerance' => 300,
        ]);

        return new FedaPayGateway();
    }

    private function sign(string $payload, ?int $timestamp = null, string $secret = 'wh_test_secret'): string
    {
        $timestamp ??= time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        return "t={$timestamp},s={$signature}";
    }

    public function test_une_signature_valide_est_acceptee(): void
    {
        $payload = '{"name":"transaction.approved","entity":{"id":123}}';

        $this->assertTrue(
            $this->gateway()->verifySignature($payload, $this->sign($payload))
        );
    }

    public function test_une_charge_utile_modifiee_est_rejetee(): void
    {
        $payload = '{"name":"transaction.approved","entity":{"id":123}}';
        $header = $this->sign($payload);

        // Le montant est falsifié après signature : c'est l'attaque que la
        // signature doit rendre inopérante.
        $falsifie = '{"name":"transaction.approved","entity":{"id":123,"amount":999999}}';

        $this->assertFalse($this->gateway()->verifySignature($falsifie, $header));
    }

    public function test_un_secret_different_est_rejete(): void
    {
        $payload = '{"name":"transaction.approved"}';

        $this->assertFalse(
            $this->gateway()->verifySignature($payload, $this->sign($payload, null, 'mauvais_secret'))
        );
    }

    public function test_un_horodatage_trop_ancien_est_rejete(): void
    {
        $payload = '{"name":"transaction.approved"}';
        // Au-delà de la tolérance : c'est la protection contre le rejeu.
        $vieux = time() - 3600;

        $this->assertFalse(
            $this->gateway()->verifySignature($payload, $this->sign($payload, $vieux))
        );
    }

    public function test_un_entete_absent_ou_malforme_est_rejete(): void
    {
        $gateway = $this->gateway();
        $payload = '{"name":"transaction.approved"}';

        $this->assertFalse($gateway->verifySignature($payload, null));
        $this->assertFalse($gateway->verifySignature($payload, ''));
        $this->assertFalse($gateway->verifySignature($payload, 'n_importe_quoi'));
        $this->assertFalse($gateway->verifySignature($payload, 't=123'));
    }

    public function test_sans_secret_configure_aucun_webhook_n_est_accepte(): void
    {
        $payload = '{"name":"transaction.approved"}';
        $header = $this->sign($payload);

        config(['fedapay.webhook_secret' => null]);

        // Refus volontaire : une installation non configurée ne doit jamais
        // créditer un wallet sur la foi d'un appel non vérifiable.
        $this->assertFalse((new FedaPayGateway())->verifySignature($payload, $header));
    }

    public function test_les_numeros_beninois_sont_normalises(): void
    {
        $gateway = $this->gateway();

        $this->assertSame('22961000000', $gateway->normalizePhone('+229 61 00 00 00'));
        $this->assertSame('22961000000', $gateway->normalizePhone('22961000000'));
        $this->assertSame('22961000000', $gateway->normalizePhone('061000000'));
    }

    public function test_l_environnement_inconnu_retombe_sur_sandbox(): void
    {
        // Un environnement mal orthographié ne doit jamais basculer en production.
        config(['fedapay.environment' => 'produciton']);
        $this->assertSame('sandbox', (new FedaPayGateway())->environment());

        config(['fedapay.environment' => 'live']);
        $this->assertSame('live', (new FedaPayGateway())->environment());
        $this->assertSame('https://api.fedapay.com/v1', (new FedaPayGateway())->baseUrl());

        config(['fedapay.environment' => 'sandbox']);
        $this->assertSame('https://sandbox-api.fedapay.com/v1', (new FedaPayGateway())->baseUrl());
    }
}

/*
 * À COMPLÉTER quand la base de test sera migrée (phpunit.xml pointe sur une
 * SQLite en mémoire, vide : il faut y ajouter RefreshDatabase) :
 *   - deux webhooks `transaction.approved` identiques ne créditent qu'une fois ;
 *   - un `transaction.declined` reçu après approbation ne débite pas ;
 *   - le webhook retrouve son locataire sans passer par settings().
 */
