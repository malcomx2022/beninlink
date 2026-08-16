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

        'account_methods' => [
            'bkash',
            'nogod',
            'rocket'
        ],

        'account_types' => [
            'personal',
            'merchant',
        ],

];
