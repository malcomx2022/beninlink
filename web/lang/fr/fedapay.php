<?php

return [
    'title' => 'Mobile Money (FedaPay)',
    'wallet_description' => 'Rechargement du portefeuille — :name',
    'initiated' => 'Paiement initié. Suivez les instructions de votre opérateur.',
    'recharge_button' => 'Recharger par Mobile Money',
    'wallet_credited' => 'Paiement confirmé : votre portefeuille est crédité.',
    'status' => 'État du paiement',

    // Le solde n'est crédité qu'après confirmation du webhook signé : ne jamais
    // laisser entendre à l'utilisateur que son solde est à jour au retour.
    'pending_notice' => 'Votre solde sera mis à jour dès la confirmation de votre opérateur.',

    'not_configured' => 'Le paiement Mobile Money n\'est pas encore configuré. Contactez l\'administrateur.',
    'merchant_only' => 'Seul un compte marchand peut effectuer une recharge.',
    'invalid_amount' => 'Le montant doit être un entier supérieur à zéro.',
    'unknown_reference' => 'Référence de paiement introuvable.',
    'error' => 'Le paiement n\'a pas pu être initié. Réessayez dans un instant.',

    // Abonnement SaaS (chantier 3, seconde moitié). Comme pour le wallet, seul
    // le webhook signé active le plan : le retour de la page ne promet rien.
    'subscribe_button' => 'Payer par Mobile Money',
    'subscription_description' => 'Abonnement BeninLink — :plan',
    'subscription_pending' => 'Paiement en cours de confirmation par votre opérateur. Votre abonnement sera activé automatiquement dès réception.',
    'subscription_activated' => 'Paiement confirmé : votre abonnement est actif.',
    'plan_not_found' => 'Plan introuvable.',
    'free_plan' => 'Ce plan est gratuit : aucun paiement n\'est nécessaire.',

    // F2/F3 — écran de réglages côté administration.
    'environment' => 'Environnement',
    'environment_sandbox' => 'Bac à sable (aucun argent réel)',
    'environment_live' => 'Production (argent réel)',
    'account' => 'Compte d\'encaissement',
    'account_own' => 'Le vôtre',
    'account_platform' => 'Celui de la plateforme',
    'account_hint' => 'Sans clé enregistrée ici, les recharges de vos marchands sont encaissées sur le compte FedaPay de la plateforme, pas sur le vôtre. L\'environnement, lui, se change dans la configuration du serveur.',
    'secret_key' => 'Clé secrète FedaPay',
    'secret_key_hint' => 'Depuis le Workbench FedaPay. Elle n\'est jamais réaffichée.',
    'webhook_secret' => 'Secret de signature des webhooks',
    'webhook_secret_hint' => 'Workbench FedaPay → Webhooks. Déclarez-y l\'URL :url',
    'webhook_secret_required' => 'Enregistrez le secret de signature des webhooks en même temps que la clé secrète : sans lui, les confirmations de paiement de votre compte seraient rejetées et aucun portefeuille ne serait crédité.',
    'secret_placeholder' => 'Non renseigné',
    'secret_kept' => 'Enregistré — laisser vide pour ne pas changer',
    'status_hint' => 'Couper la passerelle retire le paiement Mobile Money aux marchands sans toucher aux clés.',
];
