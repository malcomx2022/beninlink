# CLAUDE.md — BeninLink (monorepo · Option A actée)

> Index du dépôt lu par Claude Code. Le détail vit dans les CLAUDE.md par dossier
> et dans .claude/rules/. Garder < 200 lignes. Aucun secret ici.
> Décision : Option A — socle We Courier (backend seul) + apps React Native neuves.
> Dernière revue : 2026-07-06.

## Structure (active)
| Dossier | Techno | Rôle | Financement |
|---|---|---|---|
| `web/` | Laravel / PHP 8.3 | Backend We Courier : multi-tenant, API, paiements, facturation. **Cœur / contrat.** | Ligne 10 (backend MVP) |
| `mobile/` | React Native / Expo | App **marchand** (PME) : colis, wallet, factures, douane. | **Ligne 11 / TDR-L8 — financé** |
| `mobile-livreur/` | React Native / Expo | App **livreur** : courses, statuts, encaissement COD, gains. | **Fenêtre Création ouverte le 2026-09-05** (v1 livrée, hors ligne 11) |

## Déprécié (référence seulement — ne pas développer)
- `courier_merchant_saas-main/` (Flutter) et `courier_delivery_saas-main/` (Flutter) :
  apps mobiles d'origine We Courier. **Conservées pour la licence/le code source de
  référence**, remplacées par `mobile/` et `mobile-livreur/`. Ne rien y coder.

## Règle d'or du projet
- **Appropriation, pas réécriture** : la valeur s'ajoute **par extension** autour de
  We Courier ; ne jamais modifier son cœur de façon invasive.
- **`web/` est le contrat** : on fait évoluer le backend d'abord ; les apps mobiles
  **consomment son API**. Jamais l'inverse. Une app n'invente pas d'endpoint.
- **Ordre** : un changement d'API part de `web/`, puis `mobile/` et `mobile-livreur/` suivent.

## Décisions actées (transverses)
- **Mobile** : **React Native / Expo** (acté Option A). Les apps Flutter sont dépréciées.
- **Mobile Money** : **FedaPay** (MTN MoMo + Moov Money Bénin) — côté `web/` uniquement.
- **Devise** : **XOF (FCFA)** — montants **entiers, sans décimales**, partout.
- **Langue** : **français** par défaut.
- **Statuts colis** (source = backend We Courier) : En attente → Ramassage assigné →
  Entrepôt → Livreur assigné → Livré ; + Livraison partielle, Retour, Annulé.
- Cadre : subvention **NEXT IMPACT**. Taux **1 USD = 577 FCFA**.

## Design system (partagé mobile)
- Vert profond `#12503A` (primaire) · Ocre `#E0A63C` (actions clés) · rouge = incident.
- Typo **Sora** (titres/chiffres) + **DM Sans** (corps). FCFA en entiers.
- `mobile/` et `mobile-livreur/` partagent la même charte.

## Où atterrit chaque chantier
| Chantier | web/ | mobile/ (marchand) | mobile-livreur/ |
|---|---|---|---|
| Francisation + FCFA | oui | oui | oui |
| IFU / RCCM / CNSS | oui | oui (inscription) | — |
| FedaPay | oui (gateway+webhook) | oui (WebView recharge) | — |
| SYSCOHADA / relevés | oui | oui (factures = relevés) | — |
| Alertes douanières | oui (Module 4) | oui (création + tableau) | — |
| Reporting SaaS (MRR…) | oui | — | — |
| OpenAPI / Swagger | oui (génère) | consomme | consomme |
| Suivi / statuts colis | oui | oui (timeline) | oui (changer statut) |

Le module **FedaPay** vit **entièrement dans `web/`**. Les apps ouvrent seulement
l'`payment_url` renvoyée par le backend ; elles n'accèdent jamais aux clés.

## Travailler avec Claude Code
- `claude` à la racine pour la vue d'ensemble ; `cd web` / `cd mobile` / `cd mobile-livreur`
  pour un contexte ciblé (le CLAUDE.md local se charge).
- Gros changement → **plan mode** d'abord.
- **Étape 0 (cartographie)** avant de coder dans `web/` : voir web/CLAUDE.md.

## Déploiement (rappel)
- Seul **`web/`** part sur le VPS (racine nginx = `web/public`).
- Les apps RN se **compilent** (EAS build : APK/AAB, IPA) et se distribuent via stores /
  Expo ; leur seul lien serveur est l'URL d'API.
