<?php

/**
 * Les SMS que le produit envoie à ses destinataires.
 *
 * ┌─ POURQUOI CE FICHIER ──────────────────────────────────────────────────────┐
 * │ Le socle composait chaque SMS **en dur, sur place**, dans un branchement    │
 * │ `if (session('locale') == 'bn') … else …` recopié vingt-trois fois dans     │
 * │ deux repositories. Deux conséquences :                                      │
 * │                                                                             │
 * │  1. la langue du SMS était celle de **l'agent qui clique**, jamais celle du │
 * │     destinataire — un client béninois recevait « Your parcel is delivered » │
 * │     parce que l'opérateur avait basculé son interface en anglais ;          │
 * │  2. depuis le lot 5, `bn` ne peut plus entrer en session : la branche       │
 * │     bengali était **morte**, et l'anglais partait à tous les coups.         │
 * │                                                                             │
 * │ La langue se décide maintenant en UN endroit — `SmsTemplate::locale()` —    │
 * │ et le texte vit ici.                                                        │
 * └─────────────────────────────────────────────────────────────────────────────┘
 *
 * ⚠️ CONTRAINTE D'ENCODAGE — à respecter en modifiant ces phrases.
 * Un SMS tient 160 caractères s'il n'emploie que l'alphabet GSM 03.38 (7 bits),
 * et seulement **70** dès qu'un seul caractère en sort : le message bascule en
 * UCS-2 et coûte le double. L'alphabet accepte è é ù ì ò à ä ö ü ñ, É et Ç
 * majuscules — il refuse **ê â î ô û ë ï ç minuscule œ È À**, l'apostrophe
 * courbe « ’ », le tiret demi-cadratin « – » et l'espace insécable.
 * D'où « centre de tri » et non « entrepôt », « arrivé » et non « reçu ».
 * `SmsTemplateTest::test_every_french_template_fits_the_gsm7_alphabet` le vérifie.
 *
 * Les valeurs injectées (nom du client, raison sociale) échappent à cette règle :
 * elles viennent de la base. Un marchand nommé « Chez Thérèse » est sans risque,
 * « Le Bon Goût » fait basculer ce message-là en UCS-2. C'est acceptable — on
 * maîtrise le gabarit, pas les données.
 *
 * Les montants sont rendus par `SmsTemplate::amount()` : entiers, FCFA, espace
 * ordinaire comme séparateur de milliers (l'espace insécable de `formatAmount()`
 * sortirait de l'alphabet GSM-7).
 */
return [

    /* ── Colis ──────────────────────────────────────────────────────────── */

    'parcel_created' => 'Bonjour :customer, votre colis :tracking de :merchant est enregistré (:amount). - :brand',

    'pickup_assigned_agent' => 'Bonjour :agent, ramassez le colis :tracking chez :merchant (:phone, :address) avant le :date. - :brand',

    'pickup_assigned_merchant' => 'Bonjour :merchant, un agent de ramassage est affecté à votre colis :tracking : :agent, :agent_phone. Suivi : :url - :brand',

    'deliveryman_assigned_customer' => 'Bonjour :customer, votre colis :tracking de :merchant (:amount) est confié au livreur :agent, :agent_phone. Suivi : :url - :brand',

    'delivery_rescheduled_customer' => 'Bonjour :customer, la livraison de votre colis :tracking de :merchant (:amount) est reprogrammée. Livreur :agent, :agent_phone. Suivi : :url - :brand',

    'warehouse_received_customer' => 'Bonjour :customer, votre colis :tracking de :merchant est arrivé dans notre centre de tri, il sera livré au plus vite. Suivi : :url - :brand',

    'warehouse_received_merchant' => 'Bonjour :merchant, votre colis :tracking est arrivé au hub :hub. Suivi : :url - :brand',

    'returned_to_merchant' => 'Bonjour :merchant, votre colis :tracking vous est retourné par :agent, :agent_phone. Suivi : :url - :brand',

    'delivered_customer' => 'Bonjour :customer, votre colis :tracking est livré. Donnez votre avis : :url - :brand',

    'delivered_merchant' => 'Bonjour :merchant, votre colis :tracking est livré. Client :customer, :phone. Suivi : :url - :brand',

    'delivery_cancelled_customer' => 'Bonjour :customer, votre colis :tracking de :merchant est annulé. Suivi : :url - :brand',

    'delivery_cancelled_merchant' => 'Bonjour :merchant, la livraison de votre colis :tracking est annulée. Client :customer, :phone. Suivi : :url - :brand',

    'partial_delivered_customer' => 'Bonjour :customer, votre colis :tracking est partiellement livré. Montant à régler : :amount. Donnez votre avis : :url - :brand',

    'partial_delivered_merchant' => 'Bonjour :merchant, votre colis :tracking est partiellement livré. Client :customer, :phone. Montant encaissé : :amount. Suivi : :url - :brand',

    /* ── Portefeuille ───────────────────────────────────────────────────── */

    'wallet_recharged' => 'Bonjour :merchant, votre portefeuille :brand est rechargé de :amount.',

    'wallet_recharged_with_reference' => 'Bonjour :merchant, votre portefeuille :brand est rechargé de :amount. Référence : :reference',

    /* ── Authentification ───────────────────────────────────────────────── */

    'otp' => ':code est votre code de vérification :brand.',

];
