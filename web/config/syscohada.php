<?php

/*
|--------------------------------------------------------------------------
| SYSCOHADA — export comptable des relevés de règlement (chantier 4)
|--------------------------------------------------------------------------
| Plan de comptes utilisé par l'export journal (App\Services\Invoicing\
| SyscohadaJournal). Les numéros suivent le SYSCOHADA révisé (AUDCIF) :
|   classe 4 tiers, classe 5 trésorerie, classe 7 produits.
|
| ⚠️ Ce paramétrage est une PROPOSITION à faire valider par l'expert-comptable
| du transporteur avant tout import dans un logiciel comptable : les
| sous-comptes (4111, 7061, 4712…) et les codes de journaux dépendent du plan
| de comptes propre à chaque entreprise. Modifier ici, jamais dans le code.
|
| Lecture comptable retenue pour un relevé :
|   1. Journal des ventes (VE) — la prestation de livraison facturée au marchand :
|        D 4111 Clients (TTC)  /  C 7061 Prestations (HT)  /  C 4431 TVA facturée
|   2. Journal d'opérations diverses (OD) — compensation avec le COD encaissé
|      pour le compte du marchand, dette constatée au 4712 à l'encaissement :
|        D 4712 Créditeurs divers (TTC)  /  C 4111 Clients (TTC)
|   3. Journal de banque (BQ), seulement quand le relevé est PAYÉ — le net
|      reversé au marchand :
|        D 4712 Créditeurs divers (net)  /  C 521 Banque (net)
| Chaque écriture est équilibrée par construction.
*/

return [

    'journals' => [
        'sales' => 'VE',
        'settlement' => 'OD',
        'bank' => 'BQ',
    ],

    'accounts' => [
        'customers' => ['code' => '4111', 'label' => 'Clients'],
        'delivery_services' => ['code' => '7061', 'label' => 'Prestations de services de livraison'],
        'vat_collected' => ['code' => '4431', 'label' => 'État, TVA facturée sur ventes'],
        'cod_liability' => ['code' => '4712', 'label' => 'Créditeurs divers — COD encaissé pour compte de marchands'],
        'bank' => ['code' => '521', 'label' => 'Banques locales'],
    ],

    /*
    | Séparateur et encodage du CSV. Le point-virgule et le BOM UTF-8 sont ce
    | qu'Excel en français attend ; la plupart des logiciels comptables
    | acceptent les deux.
    */
    'csv' => [
        'delimiter' => ';',
        'bom' => true,
    ],
];
