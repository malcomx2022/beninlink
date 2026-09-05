<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Transport des notifications poussées
    |--------------------------------------------------------------------------
    |
    | `expo` : service de push d'Expo (https://exp.host). C'est le transport des
    | apps `mobile/` et `mobile-livreur/`, qui sont des apps Expo : Expo relaie
    | vers FCM (Android) et APNs (iOS) avec SES propres identifiants, si bien que
    | le backend n'a AUCUN secret à porter — décisif en multi-tenant, où une clé
    | par société aurait été à saisir, à stocker et à faire tourner.
    |
    | `null` : rien ne part (valeur des tests et d'une installation qui n'a pas
    | encore d'app mobile). Le fil de notifications en base continue d'être écrit.
    |
    | Le jour où un client aurait besoin de FCM HTTP v1 en direct, c'est un
    | pilote de plus ici — le reste du code ne connaît que `PushGateway`.
    |
    */

    'driver' => env('PUSH_DRIVER', 'expo'),

    'expo' => [
        'endpoint' => env('PUSH_EXPO_ENDPOINT', 'https://exp.host/--/api/v2/push/send'),

        // Expo accepte 100 messages par requête (limite documentée).
        'chunk' => 100,

        // Le push est un confort : on ne fait pas attendre une requête métier.
        'timeout' => (float) env('PUSH_EXPO_TIMEOUT', 5),

        // Jeton d'accès Expo, facultatif. Ne devient nécessaire que si le
        // compte Expo active « enhanced security for push notifications ».
        'access_token' => env('PUSH_EXPO_ACCESS_TOKEN'),
    ],

];
