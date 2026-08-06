# CLAUDE.md — BeninLink (monorepo)

> Carte du dépôt lue par Claude Code au début de chaque session.
> Ce fichier est l'INDEX. Le détail vit dans les CLAUDE.md par dossier
> et dans .claude/rules/. Garder < 200 lignes. Aucun secret ici.
> Dernière revue : 2026-07-06.

## Nature du dépôt
Monorepo de **trois livrables** issus du socle commercial **We Courier SAAS** :

| Dossier | Techno | Rôle |
|---|---|---|
| `web/` | Laravel / PHP 8.3 | Backend : multi-tenant, API, paiements, facturation. **Cœur.** |
| `courier_merchant_saas-main/` | Flutter / Dart | App **marchand** (colis, wallet, recharge). |
| `courier_delivery_saas-main/` | Flutter / Dart | App **livreur** (affectations, preuve, COD). |

## Règle d'or du projet
- **Appropriation, pas réécriture** : la valeur ajoutée s'ajoute **par extension**
  autour du socle We Courier ; ne jamais modifier son cœur de façon invasive.
- **`web/` est le contrat** : on fait évoluer le backend d'abord ; les deux apps
  Flutter consomment son API. Jamais l'inverse.
- **Ordre des dépendances** : un changement d'API part de `web/`, puis les apps suivent.

## Décisions actées (transverses)
- **Mobile Money** : **FedaPay** (MTN MoMo + Moov Money Bénin).
- **Mobile** : on **garde Flutter** (pas de réécriture React Native).
- **Devise** : **XOF (FCFA)** — montants **entiers, sans décimales**, partout.
- **Langue** : **français** par défaut, dans les trois dossiers.
- Cadre : subvention **NEXT IMPACT**. Taux de référence **1 USD = 577 FCFA**.

## Où atterrit chaque chantier
| Chantier | web | app marchand | app livreur |
|---|---|---|---|
| Francisation + FCFA | oui | oui | oui |
| IFU / RCCM / CNSS | oui | oui (onboarding) | — |
| FedaPay | oui (gateway+webhook) | oui (WebView recharge) | — |
| SYSCOHADA | oui | affichage factures | — |
| Reporting SaaS (MRR…) | oui | — | — |
| OpenAPI / Swagger | oui (génère) | consomme | consomme |

Le module FedaPay va **entièrement dans `web/`**. Les apps n'accèdent jamais aux
clés FedaPay : elles ouvrent l'`payment_url` renvoyée par le backend.

## Travailler dans ce monorepo avec Claude Code
- Lancer `claude` à la racine pour une vue d'ensemble.
- Pour un travail ciblé, `cd web` ou `cd` dans une app : le CLAUDE.md local
  se charge et affine le contexte.
- Toujours commencer un gros changement en **plan mode**.
- **Étape 0 (cartographie)** avant de coder dans `web/` : voir web/CLAUDE.md.

## Déploiement (rappel)
- Seul **`web/`** part sur le VPS Infomaniak (racine nginx = `web/public`).
- Les apps Flutter se **compilent** (APK/AAB, IPA) et se distribuent via les
  stores / Firebase App Distribution ; leur seul lien serveur est l'URL d'API.
