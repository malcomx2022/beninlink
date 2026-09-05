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
/*
|--------------------------------------------------------------------------
| Module « payout / paiement en ligne »
|--------------------------------------------------------------------------
|
| D10 (2026-09-05) — le module qui règle un marchand (ou l'encaisse) par
| Stripe, PayPal, bKash, Skrill ou Razorpay est **coupé**. Ce n'est pas la
| passerelle qu'on juge, c'est ce module-ci : les cinq chemins partagent les
| mêmes défauts, constatés en écrivant le rapprochement solde / relevé (D9).
|
|   - ils déplacent `merchants.current_balance` **sans écrire au relevé**,
|     ce qui casse l'invariant de D9 ;
|   - ils facturent en **BDT** codé en dur — un transporteur béninois
|     débiterait des takas ;
|   - ils lisent `Merchant::find($request->merchantId)` et
|     `Account::find($request->account_id)` **sans scope société** : un
|     marchand crédite le compte bancaire d'une autre société ;
|   - **PayPal et Razorpay ne vérifient rien** auprès du fournisseur. Une
|     requête avec un identifiant de transaction inventé éteint la dette du
|     marchand et crédite le transporteur d'un argent jamais reçu. C'est
|     l'exact opposé de la règle du projet : « webhook signé = seule source
|     de vérité ».
|
| Corriger cinq chemins qu'aucun transporteur béninois n'utilisera n'a pas de
| sens ; couper le module ferme l'écart comptable, la fuite inter-locataires
| et la fraude d'un seul geste. Le reversement au marchand passe par la
| demande de retrait (`MerchantManage\Payment`), couverte et scopée.
|
| ⚠️ Portée volontairement **étroite** : seules les routes et les écrans de ce
| module sont coupés. `stripe_status` sert aussi l'abonnement SaaS de la
| plateforme, qui ne touche aucun solde marchand — le désactiver par identité
| de passerelle l'aurait emporté avec.
|
| Pour rouvrir le module : passer `online_payout` à `true` ET corriger les
| quatre points ci-dessus. Le code est resté en place.
|
*/
return [
    'disabled_gateways' => [
        PayoutSetup::AAMARPAY,
        PayoutSetup::SSL_COMMERZ,
    ],

    'online_payout' => false,
];
