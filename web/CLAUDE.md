# CLAUDE.md — web/ (backend Laravel)

> Chargé quand Claude Code travaille dans web/. Complète le CLAUDE.md racine.
> Garder < 200 lignes. Aucun secret ici (les clés vivent dans web/.env).

## Rôle
Cœur de BeninLink : multi-tenant par sous-domaine, API consommée par les deux
apps Flutter, paiements, facturation, données. **C'est le contrat** ; on le fait
évoluer avant les apps.

## Ne jamais casser
- La **logique multi-tenant** (scoping par sous-domaine) : toute requête reste
  tenant-aware ; ne jamais contourner le scope locataire.
- L'**abstraction de paiement** existante : on **ajoute** FedaPay comme une
  implémentation du contrat commun, on ne modifie pas le flux des autres gateways.
- Le **cycle de vie des colis** : on franchit ses états, on ne les redéfinit pas.

## Décisions actées
- **FedaPay** = passerelle Mobile Money BJ. Le **webhook signé est la seule
  source de vérité** pour créditer/activer. Traitement **idempotent**.
- Devise **XOF** : montants **entiers**, jamais de décimales.
- Locale **FR** par défaut.

## Chantiers (ordre)
1. Francisation + format FCFA (locale FR, devise XOF).
2. Identifiants légaux : **IFU, RCCM, CNSS** (migration + validation + factures).
3. **FedaPay** : brancher sur la recharge wallet et l'abonnement SaaS.
4. Facturation **SYSCOHADA** (mentions BJ, TVA locale, export plan comptable).
5. Reporting SaaS : **MRR, ARR, Churn, LTV, CAC** (lecture des tables existantes).
6. Spec **OpenAPI/Swagger** sur l'API mobile existante (contrat des apps Flutter).

## Commandes
- Dépendances : `composer install`
- Migrations : `php artisan migrate`
- Tests (obligatoire avant commit) : `php artisan test`
- Lint d'un fichier : `php -l chemin/vers/Fichier.php`
- Vérifier la version PHP réelle attendue : voir `web/composer.json`.

## Conventions
- Réutiliser les conventions déjà présentes dans We Courier (repérer un exemple
  existant avant d'écrire du neuf).
- Tout module de paiement modifié est couvert par des tests PHPUnit, y compris
  l'idempotence du webhook.
- Jamais de clés en dur : `FEDAPAY_*` restent dans `web/.env`.

## Étape 0 — cartographie (à faire AVANT de coder ici)
Demander à Claude de repérer, sans rien modifier, puis compléter ci-dessous :
- [ ] Paquet / mécanisme de multi-tenancy (config, middleware, résolution) : `____`
- [ ] Interface des passerelles de paiement (d'après la classe Paystack) : `____`
- [ ] Service de crédit du wallet marchand : `____`
- [ ] Module d'activation / renouvellement d'abonnement : `____`
