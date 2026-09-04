<?php

/*
|--------------------------------------------------------------------------
| Reporting SaaS — MRR, ARR, churn, LTV, CAC (chantier 6)
|--------------------------------------------------------------------------
| Lecture des tables existantes (subscriptions, plans, general_settings,
| expenses, fedapay_transactions) ; aucun schéma modifié. Les conventions
| de calcul vivent ici, les formules dans App\Services\Reporting\SaasMetrics.
*/

return [

    /*
    | Société « plateforme » : l'éditeur BeninLink lui-même, exclu des clients
    | (elle ne s'abonne pas à elle-même). C'est aussi elle qui porte les
    | dépenses d'acquisition servant au CAC.
    */
    'platform_company_id' => 1,

    /*
    | Un plan se vend pour `days_count` jours. Pour le ramener à un revenu
    | mensuel récurrent, on normalise sur ce nombre de jours par mois.
    */
    'month_days' => 30,

    /*
    | Chapitres comptables (`account_heads.name`, comparaison insensible à la
    | casse, correspondance partielle) dont les dépenses de la société
    | plateforme comptent comme coût d'acquisition. Sans dépense enregistrée
    | sous l'un de ces chapitres, le CAC est « non disponible », jamais zéro.
    */
    'cac_account_heads' => ['marketing', 'acquisition', 'publicit', 'communication', 'commercial'],

    /* Profondeur de l'historique affiché sur la page de reporting, en mois. */
    'trailing_months' => 12,
];
