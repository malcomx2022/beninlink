<?php

/*
|--------------------------------------------------------------------------
| SYSCOHADA — export comptable (chantier 4, décision D2)
|--------------------------------------------------------------------------
| Plan de comptes utilisé par l'export journal (App\Services\Invoicing\
| SyscohadaJournal). Les numéros suivent le SYSCOHADA révisé (AUDCIF) :
|   classe 4 tiers, classe 5 trésorerie, classe 7 produits.
|
| État au 2026-10-03 (S73) : les HUIT questions de la fiche
| `docs/guides/comptabilite/plan-de-comptes.md` sont tranchées par le porteur.
| Trois numéros restent des PLACEHOLDERS en attendant que l'expert-comptable
| les arrête — ils sont marqués « à valider » ci-dessous. Modifier ici, jamais
| dans le code : `tests/Feature/PlanDeComptesSigneTest` fige ce fichier pour
| qu'un changement soit visible et délibéré.
|
| Lecture comptable retenue pour un relevé :
|   1. Journal des ventes (VE) — la prestation de livraison facturée au marchand :
|        D 4111 Clients (TTC)  /  C 7061 Prestations (HT)  /  C 4431 TVA facturée
|      Le frais de RETOUR est une prestation taxable (question 6) : il entre
|      dans le HT et sa TVA dans le 4431, comme la livraison.
|   2. Journal d'opérations diverses (OD) — compensation avec le COD encaissé
|      pour le compte du marchand, dette constatée au compte COD :
|        D COD encaissé pour compte de tiers (TTC)  /  C 4111 Clients (TTC)
|   3. Journal de banque (BQ), seulement quand le relevé est PAYÉ — le net
|      reversé au marchand, à la date de l'ordre de virement (question 5,
|      `invoices.paid_on`) :
|        D COD encaissé pour compte de tiers (net)  /  C 521 Banque (net)
|
| Les deux flux ajoutés par la question 8 :
|   4. Recharge de portefeuille marchand (Mobile Money, approuvée) — une AVANCE
|      reçue, pas un produit :
|        D 521 Banque  /  C avances reçues (auxiliaire marchand)
|   5. Remise d'espèces d'un livreur au hub (COD collecté qu'il rapporte) :
|        D 571 Caisse  /  C compte de transit livreurs
|   Les abonnements SaaS ne donnent AUCUNE écriture ici : ils sont dans les
|   livres de la société éditrice, pas du transporteur.
| Chaque écriture est équilibrée par construction.
*/

return [

    'journals' => [
        'sales' => 'VE',
        'settlement' => 'OD',
        'bank' => 'BQ',
        // Question 8 : les remises d'espèces des livreurs. Code à aligner sur
        // le logiciel comptable cible, comme les trois autres (question 4).
        'cash' => 'CA',
    ],

    /*
    | Sous-comptes auxiliaires par marchand (question 1 de D2).
    |
    | DÉFAUT depuis le 2026-10-03 (S73) : `merchant_code` — 4111 devient
    | 4111 + code marchand (« 4111PIL001 »), de même pour le compte COD et les
    | avances reçues. C'est l'usage courant dans Sage et Saari, et cela rend le
    | lettrage possible sans dépouiller les libellés.
    |
    |   SYSCOHADA_AUXILIARY=merchant_code → auxiliaire (défaut)
    |   SYSCOHADA_AUXILIARY=collectif     → un seul compte collectif par racine
    |
    | Les caractères non alphanumériques du code sont retirés (les logiciels
    | comptables n'acceptent pas les tirets dans un numéro de compte), et la
    | longueur totale est bornée par `auxiliary_length`.
    */
    'auxiliary' => env('SYSCOHADA_AUXILIARY', 'merchant_code'),
    'auxiliary_length' => 13,

    'accounts' => [
        'customers' => ['code' => '4111', 'label' => 'Clients'],
        'delivery_services' => ['code' => '7061', 'label' => 'Prestations de services de livraison'],
        // Question 3 : 4431, taux société 18 % (`configs.vat_rate`, D1) — figés.
        'vat_collected' => ['code' => '4431', 'label' => 'État, TVA facturée sur ventes'],
        // Question 2 : le principe d'un compte DÉDIÉ au COD encaissé pour compte
        // de tiers est acté (ces fonds ne sont pas un produit du transporteur).
        // ⚠️ À VALIDER : le numéro exact viendra de l'expert-comptable (4713 ?
        // 4718 ? 419 ?). 4712 reste posé en attendant — changer ici seulement.
        'cod_liability' => ['code' => '4712', 'label' => 'COD encaissé pour compte de marchands (compte dédié — numéro à valider)'],
        'bank' => ['code' => '521', 'label' => 'Banques locales'],
        // Question 8 — ⚠️ À VALIDER : les recharges de portefeuille sont des
        // avances reçues du marchand. 4191 (clients, avances et acomptes reçus)
        // est la proposition ; l'expert-comptable arrête le numéro.
        'wallet_advances' => ['code' => '4191', 'label' => 'Clients, avances reçues — portefeuille marchand (à valider)'],
        // Question 8 — ⚠️ À VALIDER : les espèces que le livreur rapporte au hub
        // transitent par un compte de tiers avant la caisse. 4713 est la
        // proposition ; l'expert-comptable arrête le numéro.
        'courier_transit' => ['code' => '4713', 'label' => 'Livreurs, fonds en transit (à valider)'],
        'cash' => ['code' => '571', 'label' => 'Caisse'],
    ],

    /*
    | Les comptes de TIERS, seuls à recevoir l'auxiliaire marchand : un produit,
    | la TVA, la banque ou la caisse n'ont pas de tiers.
    */
    'auxiliarised' => ['customers', 'cod_liability', 'wallet_advances'],

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
