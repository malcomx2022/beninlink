<?php

use App\Enums\AccountHeads;
use App\Enums\AccountType;
use App\Enums\ApprovalStatus;

return [

    'cod_charges' => [
        'inside_city'    => 1,
        'sub_city'       => 2,
        'outside_city'   => 3,
        // D4 — zone d'export. 3 %, tranché le 2026-09-06. L'écran de création
        // d'un marchand lit ces clés : ajouter la ligne ici suffit à l'y faire
        // apparaître.
        'cedeao'         => 3,
    ],


    'account_type' =>[
        'admin'       => AccountType::ADMIN,
        'user'        => AccountType::USER
    ],
    'delivery_types'=>[
        'same_day'    =>  'same_day',
        'next_day'    =>  'next_day',
        'sub_city'    =>  'sub_city',
        'outside_City'=>  'outside_City'
    ],

    'account_heads_type' =>[
        'income'      => AccountHeads::INCOME,
        'expense'     => AccountHeads::EXPENSE
    ],

    'approval_status' =>[
        'Reject'      => ApprovalStatus::REJECT   ,
        'Approved'    => ApprovalStatus::APPROVED ,
        'Pending'     => ApprovalStatus::PENDING  ,
        'Processed'   => ApprovalStatus::PROCESSED,
    ],

    'delivery_weights' => [1,2,3,4,5,6,7,8,9,10], // in kg
    'delivery_charges' => [
        'inside_city_same_day'    => [
            '1' => 40, // kg
            '2' => 100,
            '3' => 150,
            '4' => 200,
            '5' => 250,
            '6' => 300,
            '7' => 350,
            '8' => 400,
            '9' => 450,
            '10' => 500,
        ],
        'inside_city_next_day'    => [
            '1' => 30,
            '2' => 60,
            '3' => 90,
            '4' => 120,
            '5' => 150,
            '6' => 180,
            '7' => 210,
            '8' => 240,
            '9' => 270,
            '10' => 300,
        ],
        'sub_city' => [
            '1' => 40,
            '2' => 80,
            '3' => 120,
            '4' => 160,
            '5' => 200,
            '6' => 240,
            '7' => 280,
            '8' => 320,
            '9' => 360,
            '10' => 400,
        ],
        'outside_city' => [
            '1' => 60,
            '2' => 120,
            '3' => 180,
            '4' => 240,
            '5' => 250,
            '6' => 300,
            '7' => 350,
            '8' => 400,
            '9' => 450,
            '10' => 500,
        ],
    ],
    /*
    | S3 — Clé d'API des applications mobiles.
    |
    | Le socle We Courier livrait ici une valeur EN DUR, identique pour toutes
    | les installations du produit et embarquée en clair dans les APK. Elle est
    | désormais lue dans le .env : chaque installation a la sienne, et une clé
    | compromise se remplace sans toucher au code.
    |
    | ⚠️ Cette clé reste une simple porte d'entrée, pas une authentification :
    | elle voyage dans chaque requête et se retrouve dans le bundle mobile. Les
    | données du marchand restent protégées par `auth:sanctum`. Ne jamais s'en
    | servir pour autoriser une écriture (cf. S4).
    |
    | S77 (T7) — plus de valeur de repli. Le socle en avait une pour qu'une
    | installation sans variable continue de répondre ; elle répondait alors à la
    | clé publique de l'éditeur, connue de tous. Sans API_KEY, la clé vaut null et
    | `CheckApiKeyMiddleware` refuse TOUTE requête d'API (refus fermé) : le
    | déploiement s'arrête avant, `verifier-env.sh` exigeant la variable.
    | Générer : php -r 'echo "blk_".bin2hex(random_bytes(16)), PHP_EOL;'
    */
    'api_key' => env('API_KEY'),

];
