<?php

return [
    'title' => 'Mobile Money (FedaPay)',
    'wallet_description' => 'Rechargement du portefeuille — :name',
    'initiated' => 'Paiement initié. Suivez les instructions de votre opérateur.',
    'status' => 'État du paiement',

    // Le solde n'est crédité qu'après confirmation du webhook signé : ne jamais
    // laisser entendre à l'utilisateur que son solde est à jour au retour.
    'pending_notice' => 'Votre solde sera mis à jour dès la confirmation de votre opérateur.',

    'not_configured' => 'Le paiement Mobile Money n\'est pas encore configuré. Contactez l\'administrateur.',
    'merchant_only' => 'Seul un compte marchand peut effectuer une recharge.',
    'invalid_amount' => 'Le montant doit être un entier supérieur à zéro.',
    'unknown_reference' => 'Référence de paiement introuvable.',
    'error' => 'Le paiement n\'a pas pu être initié. Réessayez dans un instant.',
];
