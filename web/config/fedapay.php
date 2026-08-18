<?php

/*
|--------------------------------------------------------------------------
| FedaPay — Mobile Money Bénin (MTN MoMo + Moov Money)
|--------------------------------------------------------------------------
| Chantier 3. FedaPay agrège les deux opérateurs derrière une seule API :
| le client choisit son opérateur sur la page hébergée par FedaPay, l'app
| n'ouvre que l'URL renvoyée et ne voit jamais les clés.
|
| ⚠️ Aucune clé n'est écrite ici : tout vient du .env, jamais versionné.
| Basculer sandbox → live change UNIQUEMENT FEDAPAY_ENVIRONMENT et les clés ;
| aucun code n'est à modifier.
|
| Intégration en HTTP direct via guzzlehttp/guzzle (déjà présent), sans le SDK
| fedapay/fedapay-php : cela évite une dépendance de plus au socle et garde la
| maîtrise des délais et des erreurs. Les routes et le format suivent la
| documentation officielle (docs.fedapay.com).
*/

return [

    /*
    | 'sandbox' pour les tests (aucun argent réel), 'live' en production.
    | Toute autre valeur est traitée comme 'sandbox' — un environnement mal
    | orthographié ne doit jamais faire basculer en production par accident.
    */
    'environment' => env('FEDAPAY_ENVIRONMENT', 'sandbox'),

    /*
    | Bases d'API officielles. Sélectionnées par `environment` ; ne pas les
    | mettre dans le .env, ce sont des constantes du fournisseur.
    */
    'base_urls' => [
        'sandbox' => 'https://sandbox-api.fedapay.com/v1',
        'live' => 'https://api.fedapay.com/v1',
    ],

    /*
    | Clés « plateforme » : abonnements SaaS des locataires.
    | Un locataire peut brancher son propre compte pour l'encaissement marchand
    | (table `settings`, par company_id) — voir FedaPayGateway::credentialsFor().
    */
    'public_key' => env('FEDAPAY_PUBLIC_KEY'),
    'secret_key' => env('FEDAPAY_SECRET_KEY'),

    /*
    | Secret de signature des webhooks (Workbench → Webhooks). Distinct de la
    | clé secrète : il sert à vérifier l'en-tête X-FEDAPAY-SIGNATURE.
    | ⚠️ Sans lui, aucun webhook n'est accepté — c'est volontaire : un webhook
    | non vérifiable ne doit jamais créditer un wallet.
    */
    'webhook_secret' => env('FEDAPAY_WEBHOOK_SECRET'),

    /*
    | Tolérance d'horodatage des webhooks, en secondes. Au-delà, l'événement est
    | rejeté : c'est ce qui empêche le rejeu d'un webhook intercepté.
    */
    'webhook_tolerance' => (int) env('FEDAPAY_WEBHOOK_TOLERANCE', 300),

    'currency' => 'XOF',   // FCFA — montants ENTIERS, jamais de décimales
    'country' => 'bj',

    /*
    | Délais réseau (secondes). FedaPay répond vite, mais une liaison béninoise
    | peut être lente : mieux vaut un délai généreux qu'une transaction perdue
    | dont on ne saura pas si elle a été créée.
    */
    'timeout' => (int) env('FEDAPAY_TIMEOUT', 30),
];
