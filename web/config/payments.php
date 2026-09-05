<?php

use App\Enums\PayoutSetup;

/*
|--------------------------------------------------------------------------
| Passerelles de paiement héritées du socle We Courier
|--------------------------------------------------------------------------
|
| S21 — Aamarpay et SSLCommerz (passerelles bangladaises) appelaient leurs
| API sans vérifier le certificat TLS (`CURLOPT_SSL_VERIFYPEER=false`) et
| n'ont aucun usage au Bénin. Décision du 2026-09-05 : les désactiver plutôt
| que les corriger. Leur code reste en place (référence, licence), mais :
|   - leurs routes ne sont plus enregistrées ;
|   - les réglages refusent de les activer ;
|   - les écrans ne les proposent plus.
| Le paiement Mobile Money passe par FedaPay (chantier 3).
|
*/
return [
    'disabled_gateways' => [
        PayoutSetup::AAMARPAY,
        PayoutSetup::SSL_COMMERZ,
    ],
];
