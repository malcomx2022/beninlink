<?php

/**
 * Les langues que cette installation sert réellement.
 *
 * ┌─ POURQUOI CE FICHIER ──────────────────────────────────────────────────────┐
 * │ Le socle proposait SEPT langues — anglais, bengali, hindi, arabe, français,  │
 * │ espagnol, chinois — sur un produit dont la langue actée est le français.     │
 * │ Trois étaient incomplètes (`bn`, `in`, `zh` : 72 à 77 fichiers de langue     │
 * │ contre 87 pour `fr`), et le sélecteur était recopié SEPT fois dans quatre    │
 * │ vues, soit 49 liens à maintenir.                                            │
 * │                                                                             │
 * │ Pire : `LocalizationController::setLocalization()` acceptait **n'importe      │
 * │ quelle** chaîne et la mettait en session. `/localization/xx` suffisait à      │
 * │ basculer l'interface sur une locale inexistante — donc à afficher les CLÉS    │
 * │ brutes (`levels.name`) partout — et l'utilisateur restait coincé là, la       │
 * │ session étant persistante, sans savoir qu'il devait appeler `/localization/fr`│
 * │ pour s'en sortir.                                                            │
 * └─────────────────────────────────────────────────────────────────────────────┘
 *
 * Les dossiers `lang/es`, `lang/zh`, `lang/ar`, `lang/bn`, `lang/in` ne sont PAS
 * supprimés — la règle du projet est « 0 fichier supprimé du socle ». Ils ne sont
 * simplement plus servis. Y rebrancher une langue demande de compléter ses
 * fichiers d'abord, puis de l'ajouter ci-dessous.
 */
return [

    /**
     * Code ISO → libellé et drapeau. L'ordre fixe celui du sélecteur.
     *
     * Le drapeau du français est celui du **Bénin**, pas de la France : c'est ce
     * que montre la maquette validée (« 🇧🇯 FR »), et c'est juste — la langue
     * servie est celle du pays du produit, pas d'un autre.
     */
    /**
     * La langue de l'INSTALLATION — celle des messages adressés à un tiers qui
     * n'a pas de session : le SMS envoyé à un client, à un livreur, au marchand.
     *
     * ⚠️ Pourquoi pas `config('app.locale')` : `Illuminate\Foundation\Application::setLocale()`
     * **écrit** dans `app.locale` en même temps qu'il change la locale du
     * traducteur. Or `LanguageManager` l'appelle à chaque requête dont la
     * session porte une langue. `config('app.locale')` vaut donc, en cours de
     * requête, la langue de **l'agent connecté** — exactement la valeur dont il
     * faut se défaire. Cette clé-ci, personne ne la réécrit.
     *
     * C'est ici qu'une langue enregistrée par destinataire viendrait se
     * brancher, en repli : voir `SmsTemplate::locale()`.
     */
    'default' => env('APP_LOCALE', 'fr'),

    'supported' => [
        'fr' => ['label' => 'levels.franch',  'flag' => 'flag-icon-bj'],
        'en' => ['label' => 'levels.english', 'flag' => 'flag-icon-us'],
    ],

];
