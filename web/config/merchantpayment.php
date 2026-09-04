<?php
return [
        'payment_method'=>[
                            'bank',
                            'mobile',
                            'cash'
            ],
        // Banques du Bénin. Chaque entrée doit avoir sa clé dans lang/*/merchant.php.
        // Liste indicative, à valider avec le métier avant mise en production.
        'banks' => [
            'boa_benin',
            'ecobank_benin',
            'societe_generale_benin',
            'uba_benin',
            'nsia_banque_benin',
            'banque_atlantique_benin',
            'orabank_benin',
            'bsic_benin',
            'coris_bank_benin',
            'bgfibank_benin',
            'autre_banque',
        ],

        // Opérateurs Mobile Money du Bénin (décision actée : MTN MoMo et Moov
        // Money). Les anciennes clés bKash/Nagad/Rocket restent traduites dans
        // lang/*/merchant.php pour afficher les comptes déjà enregistrés.
        'account_methods' => [
            'mtn_momo',
            'moov_money',
        ],

        'account_types' => [
            'personal',
            'merchant',
        ],

];
