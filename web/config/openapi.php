<?php

/*
|--------------------------------------------------------------------------
| OpenAPI — contrat de l'API /api/v10 (chantier 7)
|--------------------------------------------------------------------------
| La spécification est GÉNÉRÉE depuis le routeur (App\Services\OpenApi\
| SpecGenerator) et enrichie par resources/openapi/overlay.php : chaque route
| réelle y figure, aucune route documentée ne peut être absente du code.
| `php artisan openapi:generate` écrit le fichier servi aux apps ; le test
| OpenApiSpecTest refuse toute dérive entre routeur, overlay et apps mobiles.
*/

return [
    'title' => 'BeninLink — API marchand & livreur',
    'version' => 'v10',
    'description' => "Contrat consommé par les apps React Native `mobile/` (marchand) et `mobile-livreur/`.\n\n"
        . "Deux couches d'authentification : l'en-tête `apiKey` sur presque toutes les routes, "
        . "puis un jeton Sanctum (`Authorization: Bearer …`) sur les routes protégées. "
        . "Les montants sont en francs CFA (XOF) **entiers** ; certains attributs de modèle sérialisés "
        . "tels quels peuvent encore arriver en chaîne décimale (`\"1234.00\"`) — les clients doivent tolérer les deux.\n\n"
        . "Toutes les réponses passent par l'enveloppe `{success, message, data}` sauf mention contraire.",

    /* Préfixe des routes documentées, tel qu'il figure dans le routeur. */
    'route_prefix' => 'api/v10',

    /* Fichier généré par `openapi:generate`, servi statiquement et versionné. */
    'output' => public_path('openapi/v10.json'),

    'servers' => [
        ['url' => '/api/v10', 'description' => 'Cette installation'],
        ['url' => 'https://beninlink.app/api/v10', 'description' => 'Production'],
    ],
];
