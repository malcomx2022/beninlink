<?php

/*
 * S119 — les textes que les scripts du back-office affichent eux-mêmes (dialogues SweetAlert,
 * notifications, sélecteur de période, carte). Rendus une fois dans `backend.partials.footer`
 * sous `window.trad` ; un script lit `trad.cle`, jamais un littéral.
 */
return [
    'status_updated'          => 'Statut mis à jour.',
    'priority_updated'        => 'Priorité mise à jour.',
    'confirm_delete'          => 'Voulez-vous vraiment supprimer cet enregistrement ?',
    'choose_file'             => 'Choisir un fichier',
    'current_location'        => 'Votre position actuelle.',
    'geolocation_unsupported' => 'Votre navigateur ne permet pas la géolocalisation.',
    'today'                   => 'Aujourd\'hui',
    'yesterday'               => 'Hier',
    'last_7_days'             => '7 derniers jours',
    'last_30_days'            => '30 derniers jours',
    'this_month'              => 'Ce mois-ci',
    'last_month'              => 'Le mois dernier',
    'apply'                   => 'Appliquer',
    'clear'                   => 'Effacer',
    'custom_range'            => 'Période personnalisée',
    'current_balance'         => 'Solde actuel : ',
    'not_enough_balance'      => 'Solde insuffisant.',
    'salary'                  => 'Salaire : ',
    'parcel_not_found'        => 'Colis introuvable.',
    'parcel_found'            => 'Colis trouvé.',
    'parcel_added'            => 'Colis ajouté.',
    'already_added'           => 'Déjà ajouté.',
    'min_14_characters'       => '14 caractères au minimum.',
    'max_14_characters'       => '14 caractères au maximum.',
    'select_user'             => 'Choisir un utilisateur',
    'select_account'          => 'Choisir un compte',
    'days_of_week'            => ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
    'month_names'             => ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
];
