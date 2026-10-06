# CLAUDE.md — BeninLink (monorepo · Option A actée)

> Index du dépôt lu par Claude Code. Le détail vit dans les CLAUDE.md par dossier
> et dans .claude/rules/. Garder < 200 lignes. Aucun secret ici.
> Décision : Option A — socle We Courier (backend seul) + apps React Native neuves.
> Dernière revue : 2026-10-06 (S107 — dette technique T2, T3, T9 tranchée (D15) ; S106 — R1, R2 et R8 actés par des défauts réversibles ; S105 — la page des plans ne dépend plus d'un réglage Stripe absent ; S104 — un compte livreur n'entre pas au back-office web ; S103 — les cartes de colis des deux apps rendues en test ; S102 — le registre dit la production vraie ; S101 — les listes de colis signalent le document douanier à collecter ; S100 — la CI des pull requests n'attend plus le déploiement de main ; S99 — le garde du .env refuse le mode debug en production ; S98 — mot de passe oublié dans l'app livreur ; S97 — le garde du .env refuse un cache non partagé ; S96 — les entrées d'authentification de l'API limitées contre la force brute ; S95 — le livreur voit l'alerte douanière de sa course ; S94 — l'alerte douanière sur la fiche colis web ; S93 — la vitrine d'une société neuve parle français ; S92 — les semences parlent du Bénin ; S91 — l'app marchand ne sert plus que le barème par zones ; S90 — l'API négocie sa langue par Accept-Language ; S89 — un déploiement refusé remet l'ancien code ; S88 — l'installateur fermé sur une base installée ; S87 — plus de mot de passe public sur les comptes d'amorçage ; S86 — une installation neuve réussit son premier déploiement ; S85 — le contrat de l'app livreur tenu en PHPUnit ; les dates de ce fichier,
> de web/CARTOGRAPHIE.md et de docs/DECISIONS_METIER.md sont tenues à jour par lot).

## Structure (active)
| Dossier | Techno | Rôle | Financement |
|---|---|---|---|
| `web/` | Laravel / **PHP 8.3** | Backend We Courier : multi-tenant, API, paiements, facturation. **Cœur / contrat.** | Ligne 10 (backend MVP) |
| `mobile/` | React Native / Expo | App **marchand** (PME) : colis, wallet, factures, douane. | **Ligne 11 / TDR-L8 — financé** |
| `mobile-livreur/` | React Native / Expo | App **livreur** : courses, statuts, encaissement COD, gains. | **Fenêtre Création ouverte le 2026-09-05** (v1 à v3 et visuels livrés, hors ligne 11) |

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
| Notifications poussées | oui (transport D11) | oui (fil + push) | oui (push seul) |

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
