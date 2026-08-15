<?php
/*
| Configuration FedaPay — BeninLink (socle We Courier SAAS)
| FedaPay agrège MTN MoMo Bénin et Moov Money Bénin derrière une seule API.
| Clés "plateforme" (abonnements SaaS des entreprises locataires) + possibilité,
| pour chaque locataire, de brancher son propre compte FedaPay (encaissement marchand).
*/
return [
    'environment'    => env('FEDAPAY_ENVIRONMENT', 'sandbox'), // 'sandbox' | 'live'
    'public_key'     => env('FEDAPAY_PUBLIC_KEY'),
    'secret_key'     => env('FEDAPAY_SECRET_KEY'),
    'webhook_secret' => env('FEDAPAY_WEBHOOK_SECRET'),
    'currency'       => 'XOF',   // FCFA — entiers, PAS de décimales
    'country'        => 'bj',
    'gateway_slug'   => 'fedapay', // à aligner sur la table gateways de We Courier
];
