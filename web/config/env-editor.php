<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Files Config
    |--------------------------------------------------------------------------
    */
    'paths' => [
        // .env file directory
        'env' => base_path(),
        //backup files directory
        'backupDirectory' => storage_path('env-editor'),
    ],
    // .env file name
    'envFileName' => '.env',

    /*
    |--------------------------------------------------------------------------
    | Routes group config
    |--------------------------------------------------------------------------
    |
    */
    'route' => [
        // Prefix url for route Group
        'prefix' => 'env-editor',
        // Routes base name
        'name' => 'env-editor',
        // Middleware(s) applied on route Group
        //
        // S27 — `['web']` seul laissait `GET /env-editor` répondre 200 SANS
        // AUTHENTIFICATION : lecture et écriture du fichier d'environnement
        // (identifiants de base, APP_KEY, clés FedaPay) par n'importe quel
        // visiteur. Le fournisseur du paquet est auto-découvert et charge ses
        // routes sans garde d'environnement, et nginx ne les bloque pas.
        //
        // Le garde répond 404 : l'interface ne confirme pas son existence. La
        // BIBLIOTHÈQUE reste utilisée — `InstallerController` écrit `.env` par sa
        // façade pendant l'installation ; c'est l'interface web qui est coupée.
        'middleware' => ['web', \App\Http\Middleware\BlockEnvEditorRoutes::class],
    ],

    /* ------------------------------------------------------------------------------------------------
    |  Time Format for Views and parsed backups
    | ------------------------------------------------------------------------------------------------
    */
    'timeFormat' => 'd/m/Y H:i:s',

    /* ------------------------------------------------------------------------------------------------
     | Set Views options
     | ------------------------------------------------------------------------------------------------
     | Here you can set The "extends" blade of index.blade.php
    */
    'layout' => 'env-editor::layout',

];
