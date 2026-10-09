<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'courier_app_only' => 'Ce compte est un compte livreur : il se connecte dans l\'application livreur, pas sur le site.',
    'failed' => 'Vous n\'êtes pas une personne active, veuillez contacter l\'administrateur !',
    // 'failed' => 'These credentials do not match our records.',
    'social_refused' => 'Ce compte ne peut pas se connecter sur ce site. Connectez-vous avec votre identifiant et votre mot de passe, ou contactez votre transporteur.',
    'password'          => 'Le mot de passe fourni est incorrect.',
    'throttle'          => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',
    'token_refresh'     => 'Actualisation des jetons',
    'token_delete'      => 'Révocation des jetons',
    'forbidden_user_type' => 'Ce compte n\'a pas accès à cette ressource.',
    'signin_msg'        => 'Connexion réussie !',
    'profile_msg'       => 'Profil réussi !',
    'credentials_msg'   => 'Les informations d\'identification ne correspondent pas',
    'resend_otp_msg'    => 'Renvoyer le OTP',
    'invalid_otp'       =>  'OTP invalide',
    'error_msg'         => 'Quelque chose a mal tourné.',
    'password_reset_link'   => 'Lien de réinitialisation du mot de passe',
    'password_reset'   => 'Réinitialisation du mot de passe',
    'update_password'   => 'Mettre à jour le mot de passe',
    'password_update'   => 'Mot de passe mis à jour avec succès',
    'password_old'      => 'L\'ancien mot de passe ne correspond pas !',
    'profile_update'    => 'Profil mis à jour avec succès.',

    /* ── Lot 4 (2026-09-18) — les écrans d'entrée du produit ──────────────────
       La page de connexion était entièrement en anglais en dur : c'est la
       PREMIÈRE page que voit une PME. Ces clés la couvrent, ainsi que
       l'inscription et la réinitialisation de mot de passe. */
    'sign_in'               => 'Se connecter',
    'sign_in_hint'          => 'Entrez vos identifiants pour accéder à votre espace.',
    'or'                    => 'ou',
    'forgot_password'       => 'Mot de passe oublié ?',
    'no_account'            => 'Pas encore de compte ?',
    'sign_up'               => 'Créer un compte',
    'registration_form'     => 'Formulaire d\'inscription',
    'register_my_account'   => 'Créer mon compte',
    'send_reset_link'       => 'Envoyer le lien de réinitialisation',
    'confirm_password_hint' => 'Confirmez votre mot de passe avant de continuer.',

    /* Bloc de comptes de démonstration : rendu seulement si DEMO est défini
       dans .env — ce que .env.example ne fait pas. Traduit quand même, une
       instance de recette pouvant l'activer. */
    'demo_accounts'         => 'Comptes de démonstration',
    'demo_admin'            => 'Administrateur',
    'demo_branch'           => 'Agence',
    'demo_merchant'         => 'Marchand',
    'demo_company_panel'    => 'Panneau société',


];
