# Audit UX / charte graphique — `web/` (front public + back-office)

> État des lieux et corrections à faire pour aligner l'interface web de `web/`
> sur la charte partagée par `mobile/` et `mobile-livreur/`.
> Relevé du **2026-09-18**, sur la branche `claude/ecstatic-lamport-xk8miy`.
> Document de constat et de plan.

## Journal

| Date | Décision / livraison |
|---|---|
| 2026-09-18 | **Audit publié** (constat, aucun écran modifié). |
| 2026-09-18 | **Arbitrage §9.1 tranché : `mobile/src/theme/colors.ts` l'emporte** sur la maquette pour les cinq jetons divergents. La maquette a été corrigée ; elle n'est plus une source concurrente. |
| 2026-09-18 | **Six pastilles corrigées validées** (§9.2) et implémentées. |
| 2026-09-18 | **Lot 1 livré.** Voir §10 — ce que le lot 1 a effectivement fait, et en quoi il s'écarte du plan. |
| 2026-09-18 | **Lot 2 livré.** Voir §11 — la sémantique des statuts, une **triple** copie réduite à une table, et deux constats de code mort que l'audit n'avait pas vus. |
| 2026-09-18 | **Lot 4 livré.** Voir §12 — la francisation, et le mécanisme d'anglais **invisible** que l'audit avait entièrement manqué : 45 chaînes rendues en anglais par `lang/fr.json`. |
| 2026-09-18 | **Lot 5 livré.** Voir §13 — la navigation et les langues. Le vrai défaut n'était pas le menu déroulant mais le **contrôleur**, qui acceptait n'importe quelle locale. Et un chantier neuf est ouvert : **les SMS envoyés aux clients sont en anglais**. |
| 2026-09-18 | **Lot 6 livré.** Voir §15 — accessibilité. Le troisième `http://`, absent des vues, faisait rendre à Laravel des URL en clair : le lien de réinitialisation de mot de passe et le `callback_url` de FedaPay. Une ligne manquait à notre configuration nginx. |
| 2026-09-18 | **Lot 3 livré.** Voir §14 — l'ocre réglable par transporteur. L'audit chiffrait le lot à « 1 migration » : l'ocre porte **quatre** jetons, dont deux sont des contrastes mesurés contre lui, qu'il fallait recalculer. Arbitrage §9.3 tranché. |

---

## 0. Ce que cet audit respecte

La règle d'or du projet — *appropriation, pas réécriture* — a une conséquence
directe sur une refonte visuelle, et c'est la principale conclusion de cet audit :

`docs/guides/socle/mise-a-jour-we-courier.md` chiffre la dette de fusion :
**238 fichiers du socle modifiés, 196 ajoutés, 0 supprimé**, dont **95 dans
`resources/views`** et **4 seulement dans `public/backend`**. Chaque fichier du
socle qu'on touche devient un conflit à rejouer à la prochaine montée de
We Courier. Les 196 fichiers **ajoutés**, eux, traversent une fusion sans un
conflit.

**Donc : la charte s'ajoute par une couche de jetons (fichiers neufs chargés en
dernier), elle ne se peint pas dans les 7 263 lignes de
`public/backend/libs/css/style.css`.** Tout ce plan est construit là-dessus.

---

## 1. La charte de référence — et un écart à trancher

### 1.1 La source de vérité
`mobile/src/theme/colors.ts` et `mobile/src/theme/typography.ts` sont **identiques**
à ceux de `mobile-livreur/` (vérifié par `diff` : aucune différence). C'est la
charte implémentée, et elle dit :

| Jeton | Valeur | Emploi (commentaires du fichier) |
|---|---|---|
| `primary` | `#12503A` | vert profond — en-têtes, éléments actifs |
| `primaryDark` / `primaryLight` | `#0D3A2A` / `#1C6B4E` | |
| `accent` | `#E0A63C` | ocre — **actions clés uniquement** |
| `accentDark` | `#C08D2A` | |
| `danger` | `#C0392B` | **rouge = incident**, et douane BLOQUANT |
| `warning` | `#D98324` | douane AVERTISSEMENT, retard, livraison partielle |
| `success` | `#1E8E5A` | colis livré |
| `info` | `#2C6E8F` | douane INFO, mentions neutres |
| `text` / `textMuted` | `#1A1A1A` / `#5F6B66` | |
| `background` / `surface` | `#F7F9F8` / `#FFFFFF` | |
| `border` / `disabled` | `#E1E6E3` / `#B8C2BD` | |

Typo : **Sora** 600/700 (titres **et chiffres**), **DM Sans** 400/500 (corps).
Échelle 11/13/15/18/22/28/34 · espacements 4/8/16/24/32 · rayons 6/10/16/999.

### 1.2 La maquette web validée existe déjà
`maquettes/3_back_office_web.html` (335 lignes) est **la refonte du back-office
déjà dessinée et validée** : sidebar groupée, topbar, cartes KPI, pastilles de
statut, timeline de suivi, tableaux. Elle est la cible de l'implémentation.

### 1.3 ✅ L'écart, tranché le 2026-09-18
La maquette et `colors.ts` **ne sont pas d'accord sur cinq jetons** :

| Jeton | `mobile/src/theme/colors.ts` | `maquettes/3_back_office_web.html` |
|---|---|---|
| rouge / incident | `#C0392B` | `--rouge:#D1495B` |
| fond d'écran | `#F7F9F8` | `--brume:#F4F6F3` |
| bordure | `#E1E6E3` | `--ligne:#E7ECE9` |
| encre | `#1A1A1A` | `--encre:#14201B` |
| succès | `#1E8E5A` | `--vert-vif:#1F9D6B` |

Un marchand qui passe de l'app au web verrait **deux rouges et deux gris
différents**. **Décision : `colors.ts` gagne** (c'est du code livré, exercé par
les deux apps, et son rouge `#C0392B` tient AA sur blanc — 5,44:1 — là où
`#D1495B` échoue à 4,36:1). La maquette **a été corrigée** sur ces cinq lignes au
lot 1, avec l'ancienne valeur notée en regard. Un test le tient désormais :
`WebBrandCharterTest::test_the_back_office_mockup_agrees_with_the_mobile_theme`. La maquette
apporte en revanche deux jetons **web seulement**, légitimes car le web a des
surfaces que l'app n'a pas : `--ardoise:#64748B` (en-têtes de colonnes,
métadonnées) et une nuance de fond de contenu. À déclarer comme extensions, pas
comme contradictions.

---

## 2. État des lieux — chiffré

### 2.1 La couleur : le violet We Courier est partout
**132 occurrences de `#7e0095`** dans `web/` (violet/magenta du socle), qui n'a
aucun rapport avec la charte :

| Fichier | Occurrences |
|---|---|
| `public/backend/libs/css/style.css` | **68** |
| `public/backend/libs/css/custom.css` | 20 |
| `public/backend/css/custom.css` | 7 |
| `public/backend/css/logs.css` | 5 |
| `app/Exports/InvoiceExport.php` | 5 |
| `public/installer/styleone.css` | 4 |
| `public/backend/css/reports_print.css` | 4 |
| `public/frontend/css/style.css` | 3 |
| `public/backend/css/progressbar.css` | 3 |
| vues courriel (marchand, société), PDF colis, facture PDF | 3 + 2 + 2 + 2 |
| `database/seeders/GeneralSettingsSeeder.php` | 2 |
| `database/migrations/…_create_general_settings_table.php` | 1 |
| `resources/views/backend/contact/contact_mail.blade.php` | 1 |

Points de définition qui commandent le reste :
`public/backend/libs/css/style.css:862` (`.bg-primary`), `:2689` (`.btn-primary`),
`:4035` (`.badge-primary`), `:1743` (`.nav-link.active` du sidebar), `:1714`
(icône du sidebar en `#6a027c`).

**L'ocre `#E0A63C` n'apparaît nulle part dans `web/`** — zéro occurrence. La
moitié de la charte est absente du web.

### 2.2 Deux architectures CSS opposées
| | Front public | Back-office |
|---|---|---|
| Feuille | `public/frontend/css/style.css` (1 144 l.) | `public/backend/libs/css/style.css` (7 263 l.) + 8 autres |
| Jetons | **oui** — `:root` avec `--bs-primary`, `--bs-info`… (l. 2-27) | **aucun** : hex en dur |
| Piloté par la base | **oui** — `frontend/layouts/master.blade.php:19-24` réinjecte `settings()->primary_color` et `text_color` dans `:root` | non |

Conséquence pratique : **le front public se rebrande en changeant deux valeurs ;
le back-office demande une couche d'override.** Les deux chantiers n'ont pas le
même coût et ne doivent pas être menés de la même façon.

### 2.3 La typographie : trois polices, aucune de la charte
- **Back-office** : `Circular Std` (`public/backend/vendor/fonts/circular-std/`).
- **Front public** : `frontend/layouts/master.blade.php:17` charge
  **`Bitter` + `Roboto`** depuis Google Fonts.
- **Deux anomalies vérifiées dans `public/frontend/css/style.css` :**
  1. **`Bitter` est téléchargée et jamais utilisée** — aucune règle CSS ni aucune
     vue ne la référence. Requête réseau pure perte.
  2. **l. 15 : `--h-font-family:'fangsong'`**, appliqué à `h1…h6` et `.h1….h6`
     (l. 40-45, en `!important`). `fangsong` est une famille générique de serif
     **chinois** : sur une machine latine elle n'existe pas, et **tous les titres
     du site public tombent sur le serif par défaut du navigateur** (Times / DejaVu
     Serif). Les titres de la vitrine BeninLink ne sont donc dans **aucune** police
     choisie. C'est le défaut le plus visible de l'audit, et le moins coûteux à corriger.
- **Sora et DM Sans ne sont ni chargées ni présentes** dans `web/`. Aucun `.woff2`
  dans le dépôt : les apps les tirent de npm (`@expo-google-fonts/sora`,
  `@expo-google-fonts/dm-sans`), ce qui n'aide pas le web.

### 2.4 Trois îlots déjà à la charte — sans jeton partagé
Les vues **ajoutées par BeninLink** portent déjà le vert, mais chacune **recopie
les hexadécimaux à la main** :
- `resources/views/backend/payment/fedapay_callback.blade.php:9-17` — `#12503A`,
  `#F7F9F8`, `#E1E6E3`, `#1A1A1A`, `#5F6B66` en dur, `font-family: system-ui`.
- `resources/views/api/docs.blade.php:13` — `#12503A` en dur.
- `resources/views/backend/invoice/statement_pdf.blade.php` — idem.

Trois copies indépendantes de la charte, aucune source commune : à la première
retouche de nuance, elles divergeront.

### 2.5 Et à l'inverse : les modules BeninLink héritent des mauvaises couleurs
`resources/views/backend/customs/alerts.blade.php:62-65` fait **exactement ce
qu'il faut** — réutiliser les conventions du socle :
```blade
{{-- Rouge = incident, comme partout dans la charte. --}}
<span class="badge badge-{{ $alert->level == 3 ? 'danger' : ($alert->level == 2 ? 'warning' : 'info') }}">
```
…mais `badge-danger`, `badge-warning`, `badge-info` sont les rouges/jaunes/bleus
**de Bootstrap**, pas `#C0392B` / `#D98324` / `#2C6E8F` de la charte. Les trois
niveaux douaniers du Module 4 s'affichent donc dans des couleurs qui ne sont pas
les leurs, alors que la vue est correctement écrite.

**C'est l'argument décisif :** requalifier les jetons répare cette vue et les
~380 autres **sans en ouvrir une seule**.

### 2.6 Sémantique des statuts colis : incohérente avec la charte

> ⚠️ **Ce paragraphe se trompe sur un point, corrigé au lot 2 :** `StatusParcel()`
> n'était **pas** le point unique. La même table existait en **trois** copies —
> voir §11.1. Le reste du constat ci-dessous est exact.

`app/Http/Helper/Helper.php:314-373` (`StatusParcel()`) rend la pastille de
statut. Sa table de couleurs contredit la charte :

| Statut | Rendu actuel | Charte (`mobile/src/domain/parcelStatus.ts` + `colors.ts`) |
|---|---|---|
| `PENDING` (en attente) | **`badge-danger`** — rouge | neutre. Le rouge est **réservé à l'incident** |
| `RECEIVED_BY_PICKUP_MAN` | **`badge-success`** — vert | le vert signifie **livré** |
| `PARTIAL_DELIVERED` | **`badge-success`** — vert | **orange** (`warning`) : c'est un incident |
| `RETURN_*` (9 statuts) | `dark` / `info` / `success` mélangés | **incident** — une seule famille |
| `ASSIGN_MERCHANT` | `badge-secondary` | idem retour |

Deux défauts s'ajoutent :
- **Vocabulaire Bootstrap 4** (`badge-pill badge-success`) alors que le back-office
  charge aussi Bootstrap 5 (`bg-success rounded-pill`) — cf. §2.8.
- **14 des 33 constantes de `ParcelStatus` ne sont pas couvertes** (vérifié par
  script) : tous les `*_CANCEL` (14, 15, 16, 17, 18, 20, 21, 22, 23, 25, 28, 29,
  31, 33). La fonction n'a **pas de `else`** : sur un code non listé, `$status`
  est indéfini → `Warning: Undefined variable` et cellule vide.
  **Ce n'est pas un bug actif** : les annulations passent par des routes propres
  (`parcel.delivered-cancel`, etc.) qui **ramènent le colis à l'étape amont**, si
  bien que `parcels.status` ne porte jamais un code `*_CANCEL`. C'est une
  fragilité latente que trois lignes ferment définitivement.

Enfin, `mobile/src/domain/parcelStatus.ts` ramène les 33 codes à **7 étapes
marchand** avec des regroupements nommés. **Le web n'a pas cette table** : le
back-office affiche les 33 libellés bruts, l'app en montre 7. Même colis, deux
lectures.

### 2.7 Accessibilité — mesures
Ratios WCAG calculés (AA : **4,5:1** texte courant, **3:1** grands titres et
éléments d'interface) :

| Paire | Ratio | Verdict |
|---|---|---|
| **blanc sur ocre `#E0A63C`** | **2,17:1** | **échec** — un bouton ocre à texte blanc est illisible |
| **ocre `#E0A63C` en texte sur blanc** | **2,17:1** | **échec** — l'ocre n'est jamais un texte |
| encre `#3A2A06` sur ocre (choix de la maquette) | 6,40:1 | OK — **la maquette avait déjà raison** |
| blanc sur vert `#12503A` | 9,40:1 | OK |
| vert `#12503A` sur blanc | 9,40:1 | OK |
| `warning #D98324` en texte sur blanc | 2,91:1 | **échec** |
| `success #1E8E5A` en texte sur blanc | 4,14:1 | grands titres / UI seulement |
| `danger #C0392B` sur blanc | 5,44:1 | OK |
| `info #2C6E8F` sur blanc | 5,62:1 | OK |
| violet `#7e0095` sur blanc (actuel) | 9,05:1 | OK — le socle n'était pas fautif ici |

**Et les pastilles de la maquette elle-même échouent** en texte 10,5 px :
`p-transit` `#B9832A`/`#FBF0DA` = **2,93:1** ; `p-livre` `#157F55`/`#E1F3EA` =
**4,34:1**. Elles sont à assombrir à l'implémentation (valeurs proposées en §5.3).

Autres constats :
- **Le zoom est bloqué, et l'interface est réduite de force.**
  `resources/views/backend/partials/header.blade.php:6` :
  `minimum-scale=0.8, maximum-scale=0.8, user-scalable=no`. Tout le back-office est
  rendu **à 80 %** et **le pincer-pour-zoomer est désactivé** — sur un écran de
  téléphone, pour des agents qui saisissent des colis. Même ligne dans
  `installer/index.blade.php:7` et `backend/deliveryman/parcel/parcel-map.blade.php:6`.
  Le front public bloque aussi le zoom (`master.blade.php:6` :
  `maximum-scale=1, user-scalable=no`).
- **`lang="en"` en dur** dans `backend/partials/header.blade.php:2`, alors que la
  locale par défaut est `fr` (`config/app.php:86`). Le front public, lui, fait
  correctement `app()->getLocale()`.
- **125 `<img>` sur 151 sans attribut `alt`** dans `resources/views`.
- **`focus-visible` n'apparaît qu'une fois** dans les 7 263 lignes du CSS
  back-office (53 `:focus`) : la navigation au clavier est peu lisible.
- Le sidebar inactif `#71789e` sur blanc = **4,30:1** — sous AA pour du texte 14 px.

### 2.8 La dette qui contraint la refonte : Bootstrap 4 **et** 5 ensemble
`backend/partials/header.blade.php:10-11` charge `bootstrap-five/bootstrap.min.css`
**puis** `maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css`.
`backend/partials/footer.blade.php:5-6` charge les **deux JS**.

Ce n'est pas un oubli, c'est **ce qui fait tenir le back-office** :
**217 attributs `data-toggle=`** (syntaxe BS4) contre **17 `data-bs-toggle=`** (BS5)
dans les vues, et le sidebar en dépend jusque dans le CSS
(`style.css:1659`, `:1665`, `:1679`, `:1683` sélectionnent sur
`[data-toggle="collapse"]`). Retirer Bootstrap 4 casse le menu, les modales et les
dropdowns de tout le panneau.

**À porter au plan comme une contrainte, pas comme une correction :** la charte
s'applique **sans** toucher à cette pile. Une migration BS4 → BS5 est un chantier
distinct (217 attributs + 4 vues mixtes + les sélecteurs CSS), à ne pas
emboîter dans une refonte visuelle.

### 2.9 Francisation résiduelle de l'interface
Le chantier 1 (francisation + FCFA) a traité l'essentiel — `lang/fr/` compte 87
fichiers et `formatAmount()` sort bien des entiers en FCFA
(`app/Http/Helper/Helper.php:1062-1068`, miroir exact de
`mobile/src/domain/money.ts`). Il reste des poches **dans les écrans d'entrée du
produit**, qui sont les plus vus :

- **`resources/views/auth/login.blade.php`** — la page de connexion est en anglais
  en dur : `@section('title','Login')` (l. 3), `"Please enter your user
  information."` (l. 13), `placeholder="Enter Email or Mobile"` (l. 22),
  `placeholder="Password"` (l. 34), `Sign in` (l. 53), `OR` (l. 57).
- **11 `@section('title', '…')` non traduits** : `register`, `passwords/email`,
  `passwords/confirm`, `passwords/reset`, `login`,
  `super-admin/company/company_signup`, `category/{create,index,edit}`,
  `reports/parcel-total/parcel_total_reports`, `delivery_type/create`.
- **85 `placeholder="…"` en anglais** (`Enter image` ×12, `Enter receipt` ×6,
  `Enter Tracking Id` ×5, `Enter Amount` ×4, `Enter Date` ×5…).
  ⚠️ **Sous-compté : il y en avait 161.** Le relevé d'origine ne prenait pas les
  formats de date (`yyyy-mm-dd` ×28) ni `Location Here!` ×18. Corrigé au lot 4.
- **`public/backend/libs/js/custom.js:13` et `:30`** : `confirmButtonText: 'Yes'`,
  `denyButtonText: 'Cancel'` en dur — alors que l'autre boîte de dialogue du même
  fichier (l. 50-51) utilise bien les variables `yes` / `cancel` injectées par
  `backend/partials/footer.blade.php:29-30`. **Certaines confirmations de
  suppression s'affichent en anglais, d'autres en français.**
  ⚠️ **Le diagnostic était incomplet** : `denyButtonText` n'est pas l'option du
  bouton Annuler dans SweetAlert2 (c'est `cancelButtonText`), et `showOkButton`
  n'existe pas. Ces lignes **ne faisaient rien** — traduire `denyButtonText`
  n'aurait rien changé à l'écran. Et le même défaut se répétait **14 fois** dans
  `public/backend/js/parcel/custom.js`, sur les confirmations d'annulation de
  statut. Voir §12.3.
- Les libellés `data-title` des actions de statut sont en anglais dans
  `Helper.php:260-290` (`"pickup assign"`, `"Delivery man assign"`, …).

### 2.10 Sélecteur de langue : sept locales pour un produit béninois

> ⚠️ **Constat incomplet, corrigé au lot 5.** Le sélecteur n'était que la façade :
> `LocalizationController::setLocalization()` acceptait **n'importe quelle** chaîne
> et la mettait en session. `/localization/xx` suffisait à afficher les **clés
> brutes** partout, durablement. Voir §13.2.
`backend/partials/navber.blade.php:17-43` (et le bloc est **recopié deux fois** dans
le même fichier, mobile + bureau) propose **anglais, bengali, hindi, arabe,
français, espagnol, chinois** — avec drapeaux. `lang/` confirme les sept dossiers.
Sur un produit dont la langue actée est le **français**, six entrées sont du bruit,
et `bn` / `in` / `zh` sont incomplets (72 à 77 fichiers contre 87 pour `fr`).

### 2.11 Architecture de l'information : le sidebar
Le sidebar réel (`backend/partials/sidebar.blade.php`, 663 lignes) aligne **~50
entrées sous un seul intitulé « MENU »** (`nav-divider`, l. 4), dans un `<ul>` plat.
La maquette validée groupe en trois familles — **Pilotage** (Tableau de bord,
Colis, Suivi) · **Réseau** (Marchands, Livreurs, Branches) · **Gestion** (Finances,
Paramètres) — ce qui est la correction d'ergonomie la plus rentable du back-office,
et elle ne coûte que des `nav-divider` supplémentaires.

Le panneau marchand (`merchant_panel/partials/sidebar.blade.php`, 120 lignes) est le
**jumeau web de l'app marchand** : c'est là que la cohérence avec `mobile/` se voit
le plus, et c'est là qu'il faut reprendre les intitulés de l'app (Colis,
Portefeuille, **Relevés de règlement**, Alertes douanières, Tarifs).

### 2.12 Impasses UX
- **Entrée de menu vers une page vide.** `merchant_panel/partials/sidebar.blade.php:36`
  expose « Retrait » (`online.payment.index`) **sans condition**, alors que la
  décision **D10** coupe le module : dans
  `merchant_panel/onlinepayment/index.blade.php:37-92`, **chaque** passerelle est
  derrière `@if(onlinePayoutEnabled() && …)`. Résultat : un marchand clique
  « Retrait » et arrive sur une page qui n'affiche qu'un titre et une rangée vide
  — **sans message d'état vide expliquant pourquoi**. Même schéma dans
  `backend/payout/index.blade.php:62-128`.
- **Filtre du tableau de bord** : `backend/dashboard.blade.php:33-35` — champ de
  date `placeholder="YYYY-MM-DD"` (format non francisé), largeur en
  `style="width: 15%"` en dur, `float-right`, sans `<label>`.
- **135 `table-responsive` pour 177 `<table>`** : **42 tableaux débordent** sur
  petit écran.
- **767 attributs `style="…"`** dans les vues. À nuancer honnêtement : la majorité
  est dans des gabarits **PDF / impression / courriel**
  (`merchant/invoice/invoice_pdf` 51, `parcel_export_pdf` 43,
  `reports/…/print` 34, courriels d'inscription 31 ×2), où le style en ligne est
  la règle et non un défaut. Le style en ligne des **écrans** est le problème ;
  celui des PDF ne l'est pas.

### 2.13 Le modèle de données ne sait pas stocker l'ocre
`database/migrations/2014_05_31_094551_create_general_settings_table.php:31-32` ne
définit que **`primary_color`** et **`text_color`**. Il n'existe **aucune colonne
pour la couleur d'accent**. Le multi-tenant sait donc rebrander **une seule**
couleur : l'ocre — moitié de la charte, porteuse de toutes les actions clés — n'a
nulle part où vivre.

### 2.14 Réseau : 150 références à `cdn.jsdelivr.net`
Comptées dans les vues : **150** `cdn.jsdelivr.net`, 17 `maps.googleapis.com`,
16 `cdnjs.cloudflare.com`, 8 `cdn.datatables.net`, 7 `maxcdn.bootstrapcdn.com`,
4 `stackpath.bootstrapcdn.com`, 2 `code.jquery.com`, **2 `cdn.bootcss.com` en
`http://`** (contenu mixte : bloqué par le navigateur sur une page HTTPS).
Pour des utilisateurs béninois en 3G, chaque CDN est une latence et un point de
panne. Ajouter Sora + DM Sans **par un CDN de plus** serait aggraver le problème :
il faut les **auto-héberger**.

---

## 3. Synthèse : le classement

| # | Constat | Gravité | Coût | Où |
|---|---|---|---|---|
| 1 | Titres du site public en `fangsong` → serif par défaut | **haute** | **1 ligne** | `frontend/css/style.css:15` |
| 2 | Zoom bloqué + interface réduite à 80 % | **haute** | 3 lignes | `header.blade.php:6` + 2 |
| 3 | 132 × `#7e0095`, ocre absent | **haute** | couche de jetons | cf. §2.1 |
| 4 | Sora / DM Sans absentes | **haute** | auto-hébergement | — |
| 5 | Blanc sur ocre = 2,17:1 (illisible) | **haute** | règle de charte | à écrire |
| 6 | Sémantique des statuts fausse (rouge = attente, vert = ramassé) | **haute** | 1 fonction | `Helper.php:314` |
| 7 | Niveaux douaniers aux couleurs Bootstrap | moyenne | réglé par #3 | `customs/alerts.blade.php:62` |
| 8 | Page de connexion en anglais | moyenne | 1 vue + clés | `auth/login.blade.php` |
| 9 | Confirmations de suppression en anglais | moyenne | 2 lignes | `custom.js:13,30` |
| 10 | 50 entrées de menu à plat | moyenne | `nav-divider` | `sidebar.blade.php` |
| 11 | « Retrait » → page vide sans message | moyenne | 1 `@if` + état vide | `merchant_panel/…:36` |
| 12 | 125 `<img>` sans `alt` · `lang="en"` · `focus-visible` | moyenne | mécanique | vues |
| 13 | Pas de colonne `accent_color` | moyenne | 1 migration | `general_settings` |
| 14 | 42 tableaux non responsives | basse | mécanique | vues |
| 15 | 7 locales dont 3 incomplètes | basse | 1 bloc ×2 | `navber.blade.php` |
| 16 | 150 refs CDN, 2 en `http://` | basse | auto-hébergement | vues |
| — | *Bootstrap 4 + 5 cohabitent (217 `data-toggle`)* | *contrainte* | *hors lot* | cf. §2.8 |

---

## 4. Corrections — la couche de jetons (préalable à tout)

**Trois fichiers neufs, aucun fichier du socle modifié en profondeur.**

### 4.1 `public/beninlink/css/tokens.css` — la charte, une fois
Déclare sur `:root` les jetons de `mobile/src/theme/` (couleurs, échelle
typographique, espacements, rayons), plus les extensions web de la maquette
(`--bl-ardoise`, surfaces). **C'est le seul endroit où un hexadécimal de charte
s'écrit dans `web/`** — les trois îlots du §2.4 viennent ensuite s'y brancher.

### 4.2 `public/beninlink/css/theme-backoffice.css` — l'override du back-office
Chargé **après** toutes les feuilles du socle. Requalifie les points de définition
relevés au §2.1, sans les éditer :
`.btn-primary`, `.bg-primary`, `.text-primary`, `.badge-primary`,
`.border-top-primary`, `.nav-left-sidebar .nav-link.active` (et son icône),
`.card`, `.breadcrumb`, `table thead th`, les états de focus.
Y ajouter la correction d'accessibilité : **`.btn-warning` / tout fond ocre reçoit
un texte encre `#3A2A06`, jamais blanc** (§2.7).

### 4.3 `public/beninlink/fonts/` — Sora et DM Sans auto-hébergées
Quatre `.woff2` exactement, ceux que les apps utilisent
(`mobile/app/_layout.tsx:62-66`) : **Sora 600, Sora 700, DM Sans 400, DM Sans 500**.
Déclarés en `@font-face` dans `tokens.css`, avec `font-display: swap`. Pas un CDN
de plus (§2.14).

### 4.4 Deux lignes dans le socle, et deux seulement
Un `<link>` vers `tokens.css` + `theme-backoffice.css` **en dernier** dans
`backend/partials/header.blade.php`, et un `<link>` vers `tokens.css` +
`theme-frontend.css` dans `frontend/layouts/master.blade.php`. Deux fichiers du
socle touchés d'une ligne chacun : le coût de fusion le plus bas possible.

### 4.5 Les trois îlots se rebranchent
`fedapay_callback.blade.php`, `api/docs.blade.php`,
`invoice/statement_pdf.blade.php` : remplacer les hex recopiés par les jetons
(pour le PDF, garder les valeurs en dur si le moteur ne résout pas les variables
CSS — mais **les commenter comme copie de `tokens.css`**).

---

## 5. Corrections — front public

### 5.1 La typographie (le gain le plus immédiat)

> ⚠️ **Le lot 1 a fait mieux que ce que ce paragraphe prescrivait.** Les points 1
> et 2 demandaient d'éditer `public/frontend/css/style.css` — un fichier du socle.
> L'implémentation **redéfinit les variables depuis `theme-frontend.css`**, chargée
> après : même résultat à l'écran, et **un fichier du socle en moins** dans la carte
> de fusion. Les deux points restent ci-dessous pour mémoire du défaut constaté.

1. `public/frontend/css/style.css:15` — `--h-font-family:'fangsong'` →
   `var(--bl-font-heading)` (Sora). **Corrige à lui seul tous les titres du site.**
2. l. 34 — `font-family: 'Roboto'` du `body` → DM Sans.
3. `frontend/layouts/master.blade.php:17` — **supprimer** le `<link>` Google Fonts
   `Bitter` + `Roboto` (Bitter n'est utilisée nulle part, §2.3) et charger les
   polices auto-hébergées.

### 5.2 Les couleurs
4. `frontend/css/style.css:7` — `--bs-primary:#7e0095` → `#12503A`, et les jetons
   voisins (`--bs-info`, `--bs-danger`, `--bs-success`, `--section-bg`,
   `--text-color`, `--h-color`) alignés sur `colors.ts`.
5. **Défaut en base** : `GeneralSettingsSeeder.php:39-40,58-59` et le `default()` de
   la migration → `#12503A`. Un tenant existant garde sa valeur : prévoir une
   **migration de données** qui ne réécrit que les lignes encore à `#7e0095`.
6. **Ajouter `accent_color`** (nullable, défaut `#E0A63C`) à `general_settings`
   (§2.13), l'exposer dans `general_settings/index.blade.php` à côté des deux
   sélecteurs existants (l. 182-193), et le réinjecter dans `:root` depuis
   `master.blade.php:19-24`. **Une colonne ajoutée, pas une colonne modifiée** :
   hors conflit de fusion, et le white-label multi-transporteur reste entier.

### 5.3 L'usage de l'ocre — la règle à écrire noir sur blanc
`banner.blade.php:8` met le titre du héros sur `bg-primary`, et
`banner.blade.php:17` / `tracking.blade.php:17` mettent le bouton « Suivre » en
`input-group-text bg-primary`. Après bascule ils deviennent verts — correct.
**L'ocre prend l'action clé** (le bouton « Suivre », le « S'inscrire » du navbar)
— **avec texte encre `#3A2A06`, jamais blanc** : 2,17:1 contre 6,40:1 (§2.7).
À inscrire dans `tokens.css` en commentaire, pour que la règle survive à l'audit.

### 5.4 Le reste
7. `master.blade.php:6` — `maximum-scale=1, user-scalable=no` →
   `width=device-width, initial-scale=1`.
8. Pastilles de statut de `pages/tracking.blade.php` (timeline publique) : passer
   aux six familles de la maquette, **avec les textes assombris** pour tenir AA en
   10,5 px (ratios recalculés) :

   | Famille | Fond | Texte maquette | Ratio | **Texte corrigé** | Ratio |
   |---|---|---|---|---|---|
   | attente | `#EEF1F4` | `#64748B` | 4,20:1 | **`#4A5A67`** | **6,28:1** |
   | transit | `#FBF0DA` | `#B9832A` | **2,93:1** | **`#8A5A00`** | **5,24:1** |
   | hub | `#E3F1FB` | `#2678B0` | 4,15:1 | **`#1B5C7A`** | **6,38:1** |
   | assigné | `#DEF2EE` | `#0F7B6C` | 4,43:1 | **`#0E6A5E`** | **5,57:1** |
   | livré | `#E1F3EA` | `#157F55` | **4,34:1** | **`#0F6B45`** | **5,67:1** |
   | retour | `#FBE6E9` | `#D1495B` | 3,65:1 | **`#9E2A20`** | **6,27:1** |

   *Ces six valeurs corrigent la maquette validée. À faire valider en retour.*
9. `alt` sur les `<img>` de `navbar`, `footer` et des sections.

---

## 6. Corrections — back-office

### 6.1 Ce qui se règle par la couche de jetons, sans ouvrir une vue
Le violet des boutons, badges, cartes, en-têtes de tableau, fil d'Ariane, du
sidebar actif et de son icône — **et** les trois niveaux douaniers du Module 4
(§2.5), **et** les onglets `btn-primary` / `btn-outline-primary` de
`customs/alerts.blade.php:31-33`. **C'est le cœur du rendement de ce plan.**

### 6.2 Les statuts colis — une fonction, `Helper.php:314-373`
Réécrire la table de `StatusParcel()` selon la charte, **sans toucher aux codes**
(ils appartiennent au contrat d'API) :
- `PENDING` → **neutre**, plus rouge ;
- ramassage / entrepôt / hub → **transit** (ocre pâle) et **hub** (bleu pâle) ;
- `DELIVERED` / `DELIVER` → **livré** (vert) ;
- `PARTIAL_DELIVERED` → **avertissement** (orange), plus vert ;
- les 9 `RETURN_*` + `ASSIGN_MERCHANT` → **retour**, une seule famille ;
- **un `else` de repli** qui ferme les 14 codes non couverts (§2.6).
Sortir des classes `bl-pill bl-pill--<famille>` définies dans `tokens.css`, ce qui
**affranchit du vocabulaire Bootstrap 4** sans migrer quoi que ce soit.

**Puis porter côté web la table 33 → 7 de `mobile/src/domain/parcelStatus.ts`** —
en PHP, dans un service **ajouté** (hors conflit de fusion), pour que le
back-office puisse grouper comme l'app. Les libellés restent ceux du backend
(`lang/fr/parcelStatus.php`) : on aligne les **couleurs et les regroupements**,
jamais les textes, et **jamais les valeurs numériques**.

### 6.3 Accessibilité
- `backend/partials/header.blade.php:6` → `width=device-width, initial-scale=1`.
  Idem `installer/index.blade.php:7` et
  `backend/deliveryman/parcel/parcel-map.blade.php:6`. **Trois lignes qui rendent
  le back-office utilisable au téléphone**, ce qui compte pour des agents en hub.
- `header.blade.php:2` : `lang="en"` → `lang="{{ str_replace('_','-',app()->getLocale()) }}"`,
  comme le front public le fait déjà.
- États `:focus-visible` visibles sur liens, boutons et champs — à écrire **dans
  l'override**, pas dans les 7 263 lignes.
- Reprendre `alt` par lot, en commençant par le sidebar, la topbar et le tableau
  de bord.
- Sidebar inactif `#71789e` (4,30:1) → `--bl-text-muted` `#5F6B66` (5,55:1).

### 6.4 Francisation
- `auth/login.blade.php` : les six chaînes du §2.9 vers `lang/fr/`. C'est **la
  première page de l'application** ; elle est aujourd'hui en anglais.
- Les 11 `@section('title', …)` et les 85 `placeholder` par lot.
- `public/backend/libs/js/custom.js:13,30` : `'Yes'` / `'Cancel'` → les variables
  `yes` / `cancel` déjà injectées par `footer.blade.php:29-30`. **Deux lignes**, et
  les confirmations de suppression cessent d'être bilingues.
- `Helper.php:260-290` : les `data-title` anglais vers des clés de traduction.

### 6.5 Navigation et états vides
- `backend/partials/sidebar.blade.php` : introduire les trois `nav-divider`
  **Pilotage / Réseau / Gestion** de la maquette, en **conservant** chaque
  `hasPermission()` et chaque `request()->is()` — c'est un regroupement, pas une
  réécriture. Reprendre les libellés de l'app pour le panneau marchand (§2.11).
- `merchant_panel/partials/sidebar.blade.php:36` : entourer « Retrait » d'un
  `@if(onlinePayoutEnabled())`, et donner à
  `merchant_panel/onlinepayment/index.blade.php` un **état vide explicite** quand
  aucune passerelle n'est active (§2.12). Idem `backend/payout/index.blade.php`.
- Sélecteur de langue (`navber.blade.php`, les **deux** blocs) : réduire à
  **français + anglais**. Écrire la liste **une fois** dans un partiel inclus deux
  fois — le bloc est aujourd'hui recopié.
- Filtre du tableau de bord (`dashboard.blade.php:33-35`) : `<label>`, format de
  date francisé, largeur en classe plutôt qu'en `style="width: 15%"`.

### 6.6 Le tableau de bord, selon la maquette
`backend/dashboard.blade.php` (399 l.) rend les KPI en
`card border-3 border-top border-top-primary` + `<h1>` : après bascule des jetons
ils sont **verts et cohérents**, ce qui suffit pour le lot 1. La forme de la
maquette (pastille d'icône 34 px sur fond teinté, chiffre en Sora 800/24 px,
libellé 12 px gris) se pose dans un second temps — et **par des classes
`bl-kpi` dans l'override**, sans redécouper la grille du socle.

### 6.7 Tableaux et style en ligne
`table-responsive` sur les **42** tableaux qui en manquent. Le style en ligne des
**écrans** migre vers des classes ; celui des **PDF, impressions et courriels**
**reste en ligne** — c'est la règle de l'art pour ces cibles, pas une dette
(§2.12).

---

## 7. Ce qu'il ne faut pas faire

1. **Ne pas éditer `public/backend/libs/css/style.css`** (7 263 lignes, vendeur).
   `public/backend` ne compte que **4 fichiers modifiés** dans la carte de fusion :
   ne pas y ajouter une feuille entière (§0).
2. **Ne pas retirer Bootstrap 4.** 217 `data-toggle` et quatre sélecteurs CSS du
   sidebar en dépendent (§2.8). La charte s'applique **par-dessus** la pile.
3. **Ne pas toucher aux valeurs de `ParcelStatus`** — elles sont le contrat d'API
   que `mobile/` et `mobile-livreur/` consomment. On change **les couleurs et les
   regroupements**, jamais les codes.
4. **Ne jamais mettre de texte blanc sur l'ocre** (2,17:1). Encre `#3A2A06`.
5. **Ne pas ajouter un CDN** pour Sora / DM Sans. Auto-héberger (§2.14) — et pendant
   qu'on y est, les deux `http://cdn.bootcss.com` sont du contenu mixte à traiter.
6. **Ne pas écraser le `primary_color` d'un tenant** qui l'a personnalisé : migrer
   seulement les lignes encore à la valeur d'usine.
7. **Ne pas recopier un hexadécimal de charte dans une vue.** C'est ce qui a
   produit les trois îlots divergents du §2.4.

---

## 8. Ordre de chantier

| Lot | Contenu | Effet visible |
|---|---|---|
| **0** ✅ | Trancher l'écart maquette / `colors.ts` (§1.3) · valider les six pastilles corrigées (§5.3) | *fait le 2026-09-18* |
| **1** ✅ | `tokens.css` + `theme-backoffice.css` + polices auto-hébergées + les 2 `<link>` · `--h-font-family` · les 3 viewports · `lang` | **fait** — voir §10 |
| **2** ✅ | `StatusParcel()` : sémantique + `else` de repli · pastilles `bl-pill` · douane aux couleurs de la charte | **fait** — voir §11. L'audit avait sous-estimé le chantier : la table existait en **trois** copies, pas une |
| **3** ✅ | `accent_color` en base + réglage + `:root` | **fait** — voir §14. L'audit n'avait vu qu'une colonne : l'ocre porte **quatre** jetons, dont trois sont des contrastes mesurés contre lui. Les rendre réglables sans les recalculer aurait livré du texte illisible |
| **4** ✅ | `auth/login` · les 2 lignes de `custom.js` · les 11 titres · les 85 placeholders · les `data-title` | **fait** — voir §12. L'audit comptait 85 placeholders : il y en avait **161**, et il avait manqué **45 chaînes** rendues en anglais par `lang/fr.json` |
| **5** ✅ | Sidebar groupé · « Retrait » conditionné + état vide · sélecteur de langue réduit à FR/EN · filtre du tableau de bord | **fait** — voir §13. Le sélecteur n'était que la partie visible : `LocalizationController` acceptait **n'importe quelle** chaîne |
| **6** ✅ | `focus-visible` · `alt` par lot · 42 `table-responsive` · les 2 `http://` | **fait** — voir §15. Trois des quatre items étaient déjà livrés par le lot 1. Les deux comptes étaient faux : **47** images sans `alt` et non 127 (le compte naïf coupe les balises Blade), **5** tableaux d'écran et non 42. Et le `http://` le plus coûteux n'était pas dans une vue |
| **7** | Forme fine de la maquette (KPI, topbar, timeline de suivi) | Fidélité à la maquette |
| **hors lot** | Migration Bootstrap 4 → 5 (217 `data-toggle`) | *chantier propre, jamais emboîté ici* |

Après chaque lot : **`php artisan test`** (`.github/workflows/deploy.yml` déploie
sur un push `main`, rien ne part sans la suite) et une vérification visuelle sur
les trois paniers d'écrans — front public, back-office opérateur, panneau marchand.

---

## 9. Ce qui reste à trancher

1. ~~**L'écart de cinq jetons** maquette / `colors.ts` (§1.3).~~
   **Tranché le 2026-09-18 : `colors.ts` gagne.** La maquette est corrigée, et un
   test empêche la divergence de revenir.
2. ~~**Les six pastilles assombries** du §5.3.~~ **Validées et implémentées**
   dans `tokens.css`. Un test recalcule les six contrastes à chaque exécution :
   un commentaire pourrait mentir, pas lui.
3. ~~**Jusqu'où pousser le multi-tenant.**~~ **Tranché le 2026-09-18 : option (b).**
   `primary_color` (lot 1) et `accent_color` (lot 3) sont réglables par
   transporteur ; la charte est le **défaut d'usine, pas une prison**. Avec une
   nuance que l'implémentation a imposée : tant que le transporteur est resté sur
   la charte, **rien n'est injecté** — `tokens.css` garde le dernier mot sur
   ses propres valeurs, mesurées à la main (§14.2).
4. **La refonte visuelle du back-office va-t-elle jusqu'à la maquette** (lot 7),
   ou s'arrête-t-elle à la cohérence de charte (lots 1-6) ? Les lots 1-6 donnent
   l'essentiel du bénéfice pour une fraction du risque de fusion.
5. **`Circular Std`** : la police du back-office est sous licence propriétaire et
   embarquée dans le socle. La remplacer par DM Sans règle aussi cette question —
   à confirmer comme intention.

---

## 10. Lot 1 — ce qui a été livré le 2026-09-18

### 10.1 Les fichiers ajoutés (hors conflit de fusion)

| Fichier | Rôle |
|---|---|
| `web/public/beninlink/css/tokens.css` | **La charte, une seule fois.** Jetons `--bl-*` recopiés de `mobile/src/theme/`, extensions web marquées comme telles, `@font-face`, et les couleurs de texte sur fond coloré **avec leur ratio en commentaire**. |
| `web/public/beninlink/css/theme-frontend.css` | La charte appliquée au site public. |
| `web/public/beninlink/css/theme-backoffice.css` | La charte appliquée au back-office : requalifie **tous** les sélecteurs du socle relevés au §2.1, sans éditer une ligne de ses 7 263. |
| `web/public/beninlink/fonts/` | Sora + DM Sans auto-hébergées (4 `woff2`), les deux licences **OFL** et un `LISEZMOI.md`. |
| `web/database/migrations/2026_09_18_100000_set_beninlink_brand_color.php` | Le vert en base, **sans écraser** la couleur d'un transporteur. |
| `web/tests/Feature/WebBrandCharterTest.php` | 13 tests, dont celui qui **compare `tokens.css` à `colors.ts` jeton par jeton**, et deux qui **recalculent les contrastes**. |

### 10.2 Les fichiers du socle touchés — et combien

**Six**, tous d'une poignée de lignes :

| Fichier | Ce qui change |
|---|---|
| `backend/partials/header.blade.php` | `lang` ; viewport ; **+2 `<link>`** après `@stack('styles')` |
| `frontend/layouts/master.blade.php` | viewport ; **−1 `<link>`** (Google Fonts) ; **+2 `<link>`** avant le bloc du tenant |
| `installer/index.blade.php` · `deliveryman/parcel/parcel-map.blade.php` | viewport |
| `…_create_general_settings_table.php` · `GeneralSettingsSeeder.php` | défaut de couleur |

Et **trois vues BeninLink** (pas du socle) débranchées de leurs hexadécimaux
recopiés : `api/docs`, `payment/fedapay_callback`, et `invoice/statement_pdf` —
ce dernier garde ses littéraux, **dompdf ne résout pas `var()`**, mais il dit
désormais de qui il les copie et pourquoi.

### 10.3 Deux écarts assumés par rapport au plan

**a) `theme-frontend.css` plutôt que l'édition de `style.css` (§5.1).**
Le plan disait « éditer `public/frontend/css/style.css:15` ». L'implémentation
redéfinit `--h-font-family` et `--font-family` depuis une feuille chargée après.
Même rendu, **un fichier du socle en moins** dans la carte de fusion. Le plan
était correct sur le défaut, perfectible sur le moyen.

**b) La couleur en base est passée du lot 3 au lot 1.**
Le plan gardait `primary_color` pour le lot 3. Mais le site public tire sa couleur
de `settings()->primary_color`, réinjectée dans `:root` : **aucune feuille CSS ne
pouvait verdir la vitrine.** Un lot 1 purement CSS aurait laissé le site public
violet, c'est-à-dire aurait livré la moitié de ce qu'il annonçait. Trois choses
ont donc été faites ici :

1. le **défaut de colonne** — porteur, pas cosmétique : `CompanyRepository::company_create()`
   **ne renseigne jamais** `primary_color`, donc chaque nouveau transporteur en
   héritait, violet compris ;
2. le **seeder** (les deux sociétés) ;
3. une **migration de données** qui ne convertit que la valeur d'usine `#7e0095`,
   et corrige aussi le défaut de colonne sur une base déjà migrée — réservé à
   MySQL/MariaDB, SQLite le tient de la migration de création.

Reste au lot 3 ce qui s'y trouvait vraiment : la colonne **`accent_color`**, son
réglage dans l'écran des paramètres, et son injection dans `:root`.

**c) Un défaut que l'audit avait sous-estimé : le vert de succès.**
Le §2.7 notait `success #1E8E5A` comme « grands titres / UI seulement » (4,14:1)
sans en tirer de conséquence. À l'implémentation, la conséquence est apparue :
Bootstrap pose du **texte blanc** sur `.badge-success`, et une pastille « Livré »
est du **petit** texte — donc elle échouait AA, dans les deux sens (blanc sur le
vert, et le vert en texte sur blanc). Deux jetons d'extension mesurés ont été
ajoutés, sur le même modèle que l'ocre :

| Jeton | Valeur | Emploi | Ratio |
|---|---|---|---|
| `--bl-success-text` | `#0F6B45` | `.text-success` sur blanc | 6,54:1 |
| `--bl-success-strong` | `#15704A` | fond de `.badge-success` | 6,09:1 (blanc dessus) |

`--bl-success` garde la valeur de la charte (`#1E8E5A`) pour tout le reste : ce
sont deux variantes d'usage, pas une réécriture de la charte. Même traitement pour
l'orange, qui échouait plus franchement (blanc dessus : **2,91:1**) : il porte
désormais l'encre de l'ocre (4,77:1). Un test vérifie ces paires **en recalculant
les contrastes**, et il échouera aussi le jour où `--bl-success` tiendrait AA tout
seul — pour dire alors que ces deux variantes sont devenues inutiles.

### 10.4 Ce que le lot 1 ne livre PAS

- **L'ocre n'est pas encore réglable par transporteur** : il est figé dans
  `tokens.css`. C'est le lot 3.
- **`StatusParcel()` est inchangé** : « en attente » reste rouge, la livraison
  partielle reste verte. Les classes `.bl-pill--*` **existent déjà** ; le lot 2
  n'a que du PHP à écrire.
- **Le sidebar reste à plat** (lot 5) et **la page de connexion reste en anglais**
  (lot 4).
- L'ocre n'est posé que sur **deux** actions du site public (« Suivre »,
  « S'inscrire ») et sur une classe `.btn-accent` **offerte mais pas encore
  posée** au back-office : quelle action est « l'action clé » de chaque écran est
  une décision d'ergonomie, pas de CSS.

### 10.5 Vérifier sur un serveur

```bash
php artisan migrate          # indispensable : sans elle, la vitrine reste violette
php artisan view:clear       # les vues compilées gardent les anciens <link>
php artisan test             # 461 tests
```

Puis, **vidage du cache navigateur fait**, contrôler les trois paniers :

1. **site public** — titres en Sora (et non plus en serif par défaut), bouton
   « Suivre » ocre à texte sombre, pincer-pour-zoomer rétabli ;
2. **back-office opérateur** — sidebar vert, cartes KPI vertes, **alertes
   douanières aux trois couleurs de la charte** sans qu'on ait ouvert la vue,
   interface à 100 % et non plus à 80 % ;
3. **panneau marchand** — mêmes couleurs, mêmes polices que l'app marchand, écran
   contre écran.

### 10.6 Le filet

`WebBrandCharterTest` fixe ce qui se déferait en silence à la prochaine montée de
We Courier : les deux `<link>` **et leur position** (après `@stack('styles')` au
back-office, avant le bloc du tenant au site public), le zoom, la langue déclarée,
la signature `wOF2` des quatre polices, l'absence d'hexadécimal recopié, et la
non-régression de la couleur en base. Deux tests vont plus loin que du texte : ils
**recalculent les contrastes** — les six pastilles et le texte sur l'ocre — parce
qu'un commentaire annonçant « 5,24:1 » peut mentir, un calcul non.

Vérifié : en faisant dériver un jeton, en remettant l'ancien rouge dans la
maquette, en rétablissant le viewport bloquant, `lang="en"` et en retirant un
`<link>`, **les tests concernés échouent** — ils ne sont pas décoratifs.

---

## 11. Lot 2 — ce qui a été livré le 2026-09-18

### 11.1 L'audit avait sous-estimé le chantier : la table existait en **trois** copies

Le §2.6 annonçait « `StatusParcel()` est le **point unique** ». C'était faux. La
même table, avec la même sémantique fautive, le même trou de 14 codes, le même
`else` absent et la même variable non initialisée, vivait à trois endroits :

| Copie | État | Portée |
|---|---|---|
| `Helper.php` — `StatusParcel()` | vivante | 5 vues (états et impressions) |
| `Parcel.php` — `getParcelStatusAttribute()` | vivante | **6 vues** — c'est le badge principal du produit |
| `Parcel.php` — `getStatusParcelAttribute()` | **morte** | aucune : rien ne lit `$parcel->status_parcel`, et la colonne n'existe pas |

La troisième était fautive par construction : un accesseur Eloquent reçoit la
valeur de **sa colonne**, pas un code de statut. Appelée, elle recevait `null` et
retombait sur « Undefined variable ».

**Les trois délèguent désormais à `App\Services\Parcel\ParcelStage`** — 174 lignes
de table dupliquée remplacées par 59 lignes de délégation documentée. Aucune
signature ne change : les onze vues appelantes sont intactes.

### 11.2 La sémantique, statut par statut

`ParcelStage` est le **portage de `mobile/src/domain/parcelStatus.ts`** : la même
réduction 33 → 7, avec la même règle pour les annulations (un `_CANCEL` ramène le
colis à l'étape **amont**, il ne crée pas d'étape « annulé »). C'est cette règle
qui ferme, en passant, les 14 codes que le socle ne couvrait pas.

| Statut | Avant | Après |
|---|---|---|
| En attente | **rouge** (`badge-danger`) | neutre — le rouge est réservé à l'incident |
| Reçu par le ramasseur | **vert** | transit — le vert veut dire *livré* |
| Livraison partielle | **vert** | **orange**, incident |
| Les 9 retours + `ASSIGN_MERCHANT` | `dark` / `info` / `success` mêlés | une seule famille |
| Transfert entre hubs | route en **rouge**, avec un « To » anglais | famille *entrepôt*, flèche `→` |
| Les 14 codes `_CANCEL` | *rien* — variable indéfinie, cellule vide | l'étape amont |
| Un code inconnu | *rien* | neutre, avec le vrai libellé |

Le repli est volontairement le **neutre** : si l'éditeur ajoute un statut, mieux
vaut une pastille grise qu'un vert ou un rouge inventé. Le libellé, lui, reste
celui du backend (`lang/fr/parcelStatus.php`) — l'opérateur lit toujours le vrai
statut, la couleur n'a jamais porté l'information seule.

### 11.3 Une septième pastille, que la maquette n'avait pas

La maquette définit six familles ; il n'y a pas de ligne pour la **livraison
partielle**. Or `colors.ts` lui réserve explicitement l'orange (« Orange =
AVERTISSEMENT douanier, retard, **livraison partielle** »).

La septième famille est donc ajoutée — et c'est la **seule à fond plein**. Ce
n'est pas un choix esthétique : un fond pâle orange ne se distingue pas du pâle
ocre de « transit », **1,05:1 entre les deux fonds**, deux familles illisibles
côte à côte dans une même colonne. Le fond plein tranche, et c'est le statut où
l'opérateur doit agir — le COD encaissé ne correspond pas à la commande. 4,77:1.

**À faire valider** : c'est un ajout à une maquette validée.

### 11.4 Six vues peignaient encore un statut à la main

Quatre vues **vivantes** peignaient « livraison partielle » en `badge-success`
(vert) et un retour en `badge-info` (bleu) directement dans le HTML — dont les
**deux écrans de détail de facture**, côté transporteur et côté marchand. C'est
l'endroit où le défaut coûtait le plus : du vert, sur un relevé d'argent, pour un
colis dont l'encaissement ne suit pas la commande.

Elles passent toutes par `StatusParcel()`. Un test empêche la recopie de revenir.

### 11.5 Où la pastille devait vivre — et le piège des documents autonomes

Le composant `.bl-pill` était dans `theme-backoffice.css` (lot 1). Il en sort,
pour `public/beninlink/css/components.css` : **trois états d'impression sont des
documents autonomes** qui ne chargent ni Bootstrap ni la charte. Ils reçoivent
donc `tokens.css` **et** `components.css` — sans les jetons, les `var(--bl-pill-*)`
seraient vides, et les pastilles transparentes **sans aucune erreur visible**.

Au passage, `reports_print.css` peignait les badges dans une **troisième palette**
(`#da0419`, `#5969ff`, `#21ae41`…), ni Bootstrap ni la charte.

Et à l'impression, les pastilles passent en **contours** plutôt qu'en fonds
pleins : sept aplats, c'est de l'encre pour rien, et en niveaux de gris ils se
confondent. Les sept contours tiennent AA sur blanc (5,93 à 13,87:1).

### 11.6 Deux constats de code mort, trouvés en chemin

Aucun des deux n'est corrigé par ce lot — il n'y a **aucun comportement à
corriger** — mais les deux mordraient au premier branchement :

**a) `resources/views/backend/merchant/invoice/invoice_pdf.blade.php` — vue morte
portant une erreur fatale.** Rien ne la rend. Elle référence
`ParcelStatus::RETURN_TRANSFER_BY_HUB` et `ParcelStatus::RETURN_RECEIVED_PARCEL`,
**qui n'existent pas** dans l'énumération. Vérifié : pour un colis **livré** —
le cas normal sur un relevé — PHP lève `Error: Undefined constant`. Seul le
court-circuit du `||` la sauve quand le colis est en retour. Elle porte aussi une
chaîne anglaise en dur (« And Partial Delivered »).

**b) `app/Mail/InvoicePDFSend.php` — mailable mort, avec un nom de vue qui ne
résout pas sous Linux.** Jamais instancié. Il demande
`backend.merchant.invoice.invoice_mail_pdf`, or le fichier s'appelle
**`Invoice_mail_pdf.blade.php`** — majuscule. Vérifié par `view()->exists()` :
`invoice_mail_pdf` → **introuvable**, `Invoice_mail_pdf` → trouvée. Cela
fonctionnerait sur le poste de développement (WAMP, casse insensible) et
échouerait en production. Il porte aussi `from: admin@example.com` et le sujet
« Invoice P D F Send » — des restes de l'éditeur.

Ces deux-là appartiennent à un lot de nettoyage, avec les **routes mortes** déjà
relevées dans `web/CARTOGRAPHIE.md`. Rien ne s'y décide seul : la règle du projet
est **0 fichier supprimé du socle**.

### 11.7 Le filet

`ParcelStageTest` — 14 tests. Le principal **lit `mobile/src/domain/parcelStatus.ts`
et compare la table code par code** : une retouche d'un côté sans l'autre fait
échouer la suite, plutôt que de laisser un marchand voir deux stades différents
pour le même colis selon qu'il regarde son téléphone ou le back-office. Les autres
fixent les 33 codes rangés, le repli, la charte statut par statut, l'accord des
**trois** points d'entrée sur la même sortie, l'échappement du libellé, la garde
sur les deux hubs, l'absence de recopie dans les vues, et le câblage des documents
autonomes.

Vérifié que ces tests mordent : en remettant « livraison partielle » en *livré* et
en retirant un code de la table, trois tests échouent. **Une limite connue** : le
test des annulations ne peut pas détecter un code *retiré* de la table quand son
étape attendue est `WAIT`, puisque c'est aussi le repli — c'est le test « les 33
codes ont une étape » qui l'attrape.

### 11.8 Ce que le lot 2 ne livre PAS

- **La chronologie de suivi n'est pas touchée** — ni celle du back-office
  (`parcel/logs`) ni la **publique** (`frontend/pages/tracking`). Elles lisent
  `parcel_events.parcel_status`, une colonne, pas l'accesseur, et ont leur propre
  habillage (`timeline.css`, `logs.css`). Le lot 1 en a corrigé les couleurs ; leur
  **forme** (les nœuds de la maquette) est le lot 7.
  C'est pourquoi le site public **ne charge pas** `components.css` : aucune de ses
  vues ne rend de pastille, et une feuille inutilisée est une requête pour rien.
- **Les regroupements de l'app ne sont pas portés** (`TIMELINE_ORDER`,
  `TAB_STAGES`) : aucune vue du web ne groupe encore les colis par étape. Les
  porter maintenant serait du code mort — or ce lot vient d'en constater deux cas.
- Le sidebar reste à plat (lot 5), la page de connexion en anglais (lot 4), l'ocre
  non réglable par transporteur (lot 3).

---

## 12. Lot 4 — ce qui a été livré le 2026-09-18

### 12.1 Ce que l'audit avait manqué : l'anglais **invisible**

Le §2.9 listait de l'anglais écrit en dur. Il existait un second mécanisme, que la
lecture des vues ne révèle pas :

`__('Reset Password')` n'est pas une chaîne en dur — c'est une **clé JSON**. Sans
entrée correspondante dans `lang/fr.json`, Laravel **rend la clé**, donc l'anglais,
**sans erreur ni avertissement**. Or `lang/fr.json` ne portait que **5 entrées**,
toutes des messages de validation de mot de passe.

Résultat : **45 chaînes s'affichaient en anglais** dans une application
prétendument francisée, dont

- `Remember Me`, `Password`, `Confirm Password`, `Reset Password`, `Forgot Your Password?`,
  `Login`, `Logout`, `Register` — l'ensemble du parcours de connexion ;
- `Page Not Found`, `Internal Server Error`, `Service Unavailable`, `Unauthorized`,
  `Access Forbidden`, `Page Expired`, `Too Many Requests` — **toutes les pages d'erreur** ;
- `Select Merchant`, `Select Shop`, `Select Delivery Man`, `Select Weight` — les
  sélecteurs des formulaires de colis ;
- huit confirmations de suppression (`Do you want to delete blog ?`…) ;
- `View Proof of Delivery`, `Check Merchant ID`, `Return Charge`, `Showing` ;
- `Amount(Tk)` — **et là c'est une fuite de devise**, voir §12.4 ;
- `WemaxDevs Product Activation` — la marque de l'éditeur, sur l'écran d'activation.

**C'est l'item le plus rentable de tout le plan** : 45 chaînes corrigées dans
**un seul fichier de données**, sans toucher une vue, donc **à coût de fusion nul**.

Quatre clés restent **volontairement** sans traduction : `NEXMO SMS`, `TWILIO SMS`,
`REVE SMS`, `Razorpay`. Sans entrée, Laravel rend la clé — ce qu'on veut d'une
marque. Une entrée identité ressemblerait à une erreur de traduction, et un test
vérifie qu'elles restent absentes.

### 12.2 Les placeholders : 161, pas 85

Le relevé d'origine oubliait les deux plus gros groupes :

| Valeur | Occurrences | Remarque |
|---|---|---|
| `yyyy-mm-dd` / `YYYY-MM-DD` | **30** | `merchantPlaceholder.date_format` disait déjà `aaaa-mm-jj` : la convention existait et était violée 30 fois |
| `Location Here!` | **18** | le champ d'adresse sur la carte, à la création de chaque colis |
| `Enter image` / `Enter Image` | 15 | |
| le reste | 80 | |

**143 ont été traduites**, en réutilisant les clés de `lang/fr/placeholder.php`
(82 déjà présentes, en français) et en ajoutant 18 clés manquantes, avec parité
`fr`/`en`.

**Les 18 restantes sont des exceptions assumées**, et le test les nomme :

- **9 dans l'installeur** — il n'utilise **aucun** `__()` : ce n'est pas de
  l'anglais résiduel dans une application francisée, c'est un **module non
  internationalisé**. L'interner demande un fichier de langue et la reprise de sept
  vues : chantier propre, et il tourne une fois, chez l'intégrateur.
- **8 dans deux vues SSLCommerz** — voir §12.6.
- **1 : `placeholder="TG"`** dans `delivery_zone/index` — un **code pays ISO**
  (Togo, `maxlength=2`). Le traduire le casserait. Un passage naïf l'aurait fait.

### 12.3 Les boîtes de dialogue : le défaut n'était pas celui annoncé

Le §2.9 disait « `confirmButtonText: 'Yes'` en dur ». Vrai, mais insuffisant :

- **`denyButtonText` n'est pas l'option du bouton Annuler** dans SweetAlert2 —
  c'est `cancelButtonText`. `denyButtonText` règle le bouton « deny », qui n'est
  pas affiché ici.
- **`showOkButton` n'existe pas** (c'est `showConfirmButton`, vrai par défaut).

Ces lignes **ne faisaient donc rien**. Le bouton affichait le « Cancel » par défaut
de la bibliothèque, en anglais, **quoi qu'on y écrive** : traduire `denyButtonText`
aurait produit un diff satisfaisant et zéro changement à l'écran.

Et le défaut ne touchait pas deux dialogues mais **seize** : les deux de
`libs/js/custom.js`, plus **quatorze** dans `public/backend/js/parcel/custom.js` —
les confirmations d'**annulation de statut**, c'est-à-dire une action métier réelle
(annuler un ramassage, annuler une livraison), entièrement en anglais :

> *Do you want to cancel the pickup assign?* — avec `Yes` / `Cancel`.

Le « pickup assign » venait d'un attribut `data-title` écrit en anglais dans
`Helper.php` (17 occurrences). La formulation juste nomme le statut **annulé**,
déjà traduit par le backend : `ParcelStage::cancelledLabel()` porte la
correspondance annulation → statut défait (14 paires), et la question devient

> *Voulez-vous annuler « Ramassage assigné » ?*

### 12.4 Deux fuites de devise, et deux fautes de français

Trouvées en chemin, toutes dans les fichiers de langue **français** :

| Fichier | Contenu | Correction |
|---|---|---|
| `lang/fr/parcel.php` | `'Encaissement en espèces (Tk)'` | **Tk = taka bangladais**, sur le libellé du COD |
| `lang/fr/levels.php` | `'Montant (Tk)'` | idem, 55 usages |
| `lang/fr.json` (clé) | `Amount(Tk)` | idem |
| `lang/fr/auth.php` | `'UMettre à jour le mot de passe'` | coquille visible |
| `lang/fr/reports.php` | `'parcel_total_summery' => 'Total Summery'` | de l'**anglais** dans le fichier français, et « Summery » est une coquille du socle pour « Summary » |

La devise **n'est pas recodée en dur** : elle est réglable par transporteur
(`general_settings.currency`), et un fichier de langue ne peut pas appeler
`currencySymbol()`. Les montants portent déjà leur symbole via `formatAmount()` —
le libellé n'en a pas besoin. Une vue qui veut afficher l'unité l'ajoute elle-même.

⚠️ Le `(Tk)` existe aussi dans `es`, `zh`, `ar`, `bn`, `in`. **Non corrigé à
dessein** : le lot 5 retire ces locales (§2.10), les traiter serait du travail jeté.

### 12.5 Une mesure que ce dépôt ne permet pas

`docs/guides/socle/` affirme que « le premier commit du dépôt porte le socle
intact », et s'en sert pour chiffrer le coût de fusion. **Ce n'est pas vérifiable
ici** : le dépôt a **deux racines** (historique greffé), et les deux portent déjà
des ajouts BeninLink (`lang/fr/customs.php`, `ChargeCalculator.php`). Impossible
donc de dire combien des 109 fichiers touchés par ce lot étaient encore intacts.

Le périmètre a été arbitré au jugement, pas sur un chiffre — et la francisation est
le **chantier 1** de `web/CLAUDE.md`, donc son coût de fusion a déjà été accepté par
le projet. À corriger dans le guide `socle/`, ou à expliquer.

### 12.6 Un troisième cas de vue morte

`backend/merchant_panel/sslecommerz/exampleEasycheckout.blade.php` et
`exampleHosted.blade.php` portent un formulaire d'adresse **américain**
(« 1234 Main St », « Apartment or suite », « you@example.com »). Deux raisons de ne
pas les traduire, et la seconde est décisive :

1. SSLCommerz est **coupée** (S21, `config/payments.php`) ;
2. **aucune route ne mène à ces vues** — `SslCommerzPaymentController::exampleEasyCheckout()`
   existe, mais `routes/web.php` ne route que `pay-via-ajax`, `success`, `fail`,
   `cancel` et `ipn`.

À joindre aux deux constats du §11.6 pour le lot de nettoyage.

### 12.7 Le filet

`FrenchInterfaceTest` — 9 tests. Les deux qui comptent :

- **Toute clé JSON anglaise utilisée dans une vue doit avoir une traduction
  française.** C'est le test qui rend le mécanisme du §12.1 visible : un nouveau
  `__('Some English')` sans entrée dans `fr.json` fait échouer la suite, au lieu de
  s'afficher en anglais sans bruit.
- **Aucune vue ne porte de `placeholder` en dur**, avec la liste des exceptions
  **dans le test** — ce qui force à justifier une exception plutôt qu'à la laisser
  passer.

Les autres fixent les titres traduits, l'absence de devise bangladaise en `fr` et
`en`, les noms d'option SweetAlert2 (`showOkButton` et `denyButtonText` interdits —
c'est ce qui rendait la correction précédente illusoire), la correspondance
annulation → statut défait, la page de connexion, et la **parité fr/en** des trois
fichiers touchés (une clé oubliée en anglais s'afficherait en français à un
anglophone, sans erreur).

Vérifié que ces tests mordent : en retirant une traduction de `fr.json`, en
remettant un placeholder anglais, en rétablissant `denyButtonText` et en remettant
`(Tk)`, **quatre tests échouent**.

### 12.8 Ce que le lot 4 ne livre PAS

- **L'installeur reste en anglais** (§12.2) — chantier propre : un fichier de langue
  à créer, sept vues à reprendre, zéro `__()` aujourd'hui.
- **Les locales `es`, `zh`, `ar`, `bn`, `in` ne sont pas touchées** : le lot 5 les
  retire, et `bn`/`in`/`zh` sont déjà incomplètes (72 à 77 fichiers contre 87).
- **Les noms de feuille des exports Excel** (`data-title="Parcel Status Reports"`,
  lus par `reports/reports.js`) restent en anglais : ils nomment un fichier
  téléchargé, pas un écran. À prendre avec le lot 5.
- Le sidebar reste à plat, l'ocre non réglable par transporteur, la forme des
  chronologies de suivi (lot 7). *(Les deux premiers ont été livrés depuis : le
  lot 5 au §13 et le lot 3 au §14.)*
---

## 13. Lot 5 — ce qui a été livré le 2026-09-18

### 13.1 Le menu : un regroupement, pas une réécriture

26 entrées de premier niveau alignées sous un seul « MENU ». La maquette en groupe
trois (Pilotage / Réseau / Gestion) — **pour un menu de huit entrées** ; à 26,
« Gestion » en aurait avalé une vingtaine. Les trois de la maquette sont donc la
colonne vertébrale, et **trois s'y ajoutent** :

| Groupe | Entrées |
|---|---|
| **Pilotage** | Tableau de bord, Colis, Alertes douanières, Demandes de ramassage |
| **Réseau** | Marchands, Livreurs, Centres |
| **Finances** | Demandes de recharge, Paiements reçus, Retrait, Comptabilité, Paie |
| **Rapports et contrôle** | Rapports, Journaux, Vérification fraude |
| **Relation et contenu** | Tâches, Support, Notifications, Actualités, Site vitrine |
| **Administration** | Utilisateurs et rôles, Actifs, Abonnement, Abonnements, Reporting SaaS, Réglages |

⚠️ **Extension à faire valider**, comme la septième pastille du lot 2.

Le panneau **marchand** garde les trois groupes de la maquette : huit entrées
seulement, et l'ordre suit celui de l'app marchand — un marchand doit retrouver
ses écrans au même endroit sur les deux surfaces.

**Un intitulé ne s'affiche que si au moins une de ses entrées l'est** : sa
condition est le OU des permissions de ses membres. Sans cela, un agent aux droits
restreints verrait des titres sans rien dessous.

**Ce qui prouve que rien n'a été perdu.** Le regroupement déplace des blocs dans un
fichier de 663 lignes : une relecture ne peut pas le garantir à l'œil. Les jeux de
**routes** et de **permissions** ont donc été comparés avant / après —
**68 routes et 15 routes, identiques ; les 97 permissions, toutes présentes** — et
les deux listes sont désormais **inscrites dans le test**. Une entrée supprimée
fait échouer la suite.

### 13.2 Les langues : le défaut n'était pas le menu déroulant

Le §2.10 relevait sept langues dont trois incomplètes, et un sélecteur recopié
sept fois dans quatre vues. Vrai, mais accessoire. Le vrai défaut :

**`LocalizationController::setLocalization()` acceptait n'importe quelle chaîne** et
la mettait en session. `/localization/xx` suffisait à basculer l'interface sur une
locale inexistante — donc à afficher les **clés brutes** (`levels.name`) partout —
et l'utilisateur y restait, la session étant persistante, sans savoir qu'il devait
appeler `/localization/fr` pour s'en sortir.

Trois corrections, dans cet ordre d'importance :

1. **Le contrôleur** ne retient que ce que `config/locales.php` déclare. Une valeur
   inconnue est **ignorée**, pas rabattue sur le français : un lien mal formé ne
   doit pas changer la langue de quelqu'un qui avait choisi l'anglais.
2. **Le middleware** `LanguageManager` posait la valeur de session telle quelle. Il
   valide, et **nettoie la session** — sans quoi une session ouverte avant ce lot
   resterait coincée en `zh` à chaque requête.
3. **Le sélecteur** est écrit **une fois** (`resources/views/partials/locale-*.blade.php`),
   alimenté par la config. Les 49 liens recopiés disparaissent.

Les dossiers `lang/es`, `zh`, `ar`, `bn`, `in` **ne sont pas supprimés** — la règle
du projet est « 0 fichier supprimé du socle ». Ils ne sont plus servis. Y rebrancher
une langue demande de compléter ses fichiers, puis de l'ajouter à la config.

Détail de charte : le drapeau du français est celui du **Bénin**, pas de la France —
c'est ce que montre la maquette (« 🇧🇯 FR »), et c'est juste : la langue servie est
celle du pays du produit.

### 13.3 ⚠️ Un chantier neuf, et il est plus important que celui-ci

En traquant les usages de la locale, **21 branches `session('locale') == 'bn'`** sont
apparues dans `app/Repositories/Parcel/ParcelRepository.php`. Elles choisissent la
langue des **SMS envoyés au client final**. Il y a une variante bengalie, une
variante anglaise… et **aucune variante française** :

> *Dear Aïcha, Your parcel is successfully created. Your parcel with ID BL-0001
> parcel from Kola Distribution (15000)*

**20 messages** sont ainsi construits en anglais dans ce seul fichier, et il y a des
SMS dans trois autres dépôts (`Merchant`, `Company`, `Wallet`). C'est le texte le
**plus vu de tout le produit** — il arrive sur le téléphone d'un client qui n'a
jamais ouvert l'application — et l'audit ne l'avait pas vu, parce qu'il n'a regardé
que les vues et le JavaScript.

**Non traité ici**, et à dessein : ce n'est pas de la navigation, ce sont 20 gabarits
de message à porter en clés de langue avec substitution, dans un fichier que
`docs/guides/socle/` classe parmi ceux qu'il ne faut pas laisser l'éditeur
réécrire. Cela mérite son lot, avec la question métier qui va avec : faut-il aussi
la langue du **destinataire** plutôt que celle de la session de l'agent qui a créé
le colis ? Un client béninois ne reçoit pas un SMS dans la langue choisie par
l'opérateur qui a cliqué.

### 13.4 Plus d'impasse sur « Retrait »

Le module de retrait en ligne est coupé (**D10**). La **page** conditionnait déjà
chaque passerelle ; le **menu** ne conditionnait rien : le marchand cliquait et
arrivait sur un écran qui n'affichait qu'un titre et une rangée vide.

L'entrée de menu est conditionnée, **et** les deux pages (marchand et transporteur)
portent un état vide explicite — le menu n'y mène plus, mais une URL en favori, si.

*Détail d'implémentation qui a son intérêt :* l'état vide était d'abord écrit en
`@unless`. Blade le compile en `<?php if (! …)` — avec une espace après `if` — ce
qui échappait au comptage `'<?php if('` de `OnlinePayoutModuleDisabledTest`. Écrit
`@if (!…)`, la convention du dépôt tient. C'est au code neuf de s'aligner sur le
test en place, pas au test de plier.

### 13.5 Le filtre des tableaux de bord

Champ de date sans `<label>` — donc non annoncé par un lecteur d'écran, et le
placeholder disparaît dès qu'on tape. Libellé ajouté, masqué visuellement. La
largeur passe du `style="width: 15%"` en dur (et `30%` sur l'autre tableau de bord)
à une classe : un pourcentage sur un champ de saisie donne une largeur qui n'a rien
à voir avec son contenu.

Les deux utilitaires (`.bl-sr-only`, `.bl-filter-date`) vivent dans
`components.css` plutôt que d'emprunter `sr-only` (Bootstrap 4) ou
`visually-hidden` (Bootstrap 5) : le back-office charge les deux aujourd'hui, et le
jour où l'un partira, ceci tiendra encore.

### 13.6 Une erreur commise en écrivant ce lot, et le test qu'elle a produit

Le générateur des conditions de groupe a d'abord produit `A || || B`. **Blade
compile cela sans broncher** : l'erreur de syntaxe n'apparaît qu'au **rendu**.
`php artisan view:cache` est passé au vert, et un test qui ne rend pas la page ne
l'aurait pas vue non plus.

D'où `test_the_touched_views_compile_to_valid_php`, qui **lint le PHP compilé** des
treize vues de ce lot. Vérifié : en réintroduisant le double `||`, il échoue.
C'est le test le plus utile du lot, et il n'existerait pas sans l'erreur.

### 13.7 Un défaut préexistant, signalé et non corrigé

La garde du menu **Réglages** ne liste que cinq permissions
(`delivery_category_read`, `delivery_charge_read`, `delivery_type_read`,
`liquid_fragile_read`, `packaging_read`) alors que son sous-menu en compte une
quinzaine. Un agent à qui l'on n'accorde que `general_settings_read` **ne voit pas
le menu Réglages** et ne peut donc pas atteindre les réglages généraux.

Non corrigé : élargir cette garde change **qui voit quoi**, et c'est une décision de
permissions, pas d'ergonomie. (L'intitulé de groupe « Administration », lui, liste
bien les quinze : le groupe s'affiche, même si l'entrée reste masquée.)

### 13.8 Ce que le lot 5 ne livre PAS

- **Les SMS en anglais** (§13.3) — son propre lot, et le plus visible de tous.
- **Les 42 tableaux sans `table-responsive`**, les `alt` manquants, `focus-visible`
  et les deux `http://` : c'est le **lot 6**. *(Une note de séance les avait rangés
  par erreur dans le lot 5 ; le plan de §8 fait foi.)*
- **Les dossiers de langue `es`/`zh`/`ar`/`bn`/`in`** restent sur le disque (règle
  du projet) ; ils ne sont plus servis.
- **Les noms de feuille des exports Excel** restent en anglais (relevé au §12.8).
- La forme des chronologies de suivi (lot 7). *(L'ocre non réglable par
  transporteur, que ce lot laissait aussi de côté, a été livré depuis — §14.)*

---

## 14. Lot 3 — ce qui a été livré le 2026-09-18

### 14.1 Ce que l'audit avait sous-estimé : l'ocre n'est pas **une** couleur

Le §8 annonçait « `accent_color` en base », et la ligne 13 du classement chiffrait
le coût à « 1 migration ». Le lot 1 avait pourtant déjà écrit, dans `tokens.css`,
que l'ocre vit en **quatre** jetons :

| Jeton | Rôle | Contrainte |
|---|---|---|
| `--bl-accent` | l'ocre lui-même | — |
| `--bl-accent-dark` | variante de survol | — |
| `--bl-on-accent` | l'encre posée **dessus** | **6,40:1** sur l'ocre |
| `--bl-accent-text` | l'ocre **en texte** sur blanc | **5,93:1** |

Les deux derniers sont des contrastes **mesurés contre le premier**. Rendre le
premier réglable en laissant les autres fixes, c'est poser le brun chaud `#3A2A06`
sur le bleu marine qu'un transporteur aura choisi : **1,01:1**. Les deux couleurs
ont presque la même luminance — le libellé du bouton ne serait pas « difficile à
lire », il serait **invisible**.

Le lot 1 avait écrit que ces valeurs sont « mesurées, pas choisies à l'œil » : on
ne pouvait donc pas les laisser figées quand leur référence, elle, bouge.

D'où une classe plutôt qu'une simple colonne : `App\Services\Brand\AccentColor`
**recalcule les trois jetons dérivés** à partir de la couleur du transporteur.

### 14.2 La charte garde le dernier mot sur elle-même

Quand le transporteur est resté sur l'ocre, `AccentColor::jetons()` rend un tableau
**vide** : aucun bloc `<style>` n'est émis, et `tokens.css` continue de servir ses
quatre valeurs. C'est volontaire à deux titres :

1. les valeurs de la charte ont été **mesurées à la main** au lot 1 et sont
   meilleures que ce qu'un calcul produit — `#3A2A06` est un brun chaud, pas un
   noir. Le calcul ne sert qu'aux couleurs que **personne n'a mesurées** ;
2. une installation par défaut — c'est-à-dire la nôtre — **ne paie pas un octet**
   pour ce lot.

### 14.3 La promesse tenue : aucune couleur ne peut rendre un texte illisible

`AccentColor::encre()` essaie d'abord les deux couleurs de la charte (l'encre douce
`#1A1A1A` et le blanc) et garde la meilleure ; si aucune n'atteint AA, elle retombe
sur le **noir ou le blanc purs**, qui l'atteignent toujours. La couleur la plus
défavorable qui soit — celle dont les contrastes au noir et au blanc s'égalisent —
y tient encore **4,58:1**.

`AccentColor::texte()` assombrit l'accent par pas de 8 % jusqu'à AA sur blanc : la
teinte choisie est **gardée**, pas remplacée par un gris. C'est ce que la charte
fait déjà à la main pour l'ocre (`#8A5A00`) et pour le vert de succès.

Ce n'est pas une promesse de commentaire : `WebAccentColorTest` **balaie 4 096
couleurs** et recalcule chaque contraste. Un balayage plus fin, de 140 608 couleurs,
a servi à l'écriture : **zéro échec**, pire cas **4,50:1** — la cible elle-même.

### 14.4 Les fichiers

| Fichier | Rôle |
|---|---|
| `web/app/Services/Brand/AccentColor.php` | **Toute la règle.** Normalisation, contraste, les trois dérivés. Ne connaît pas la base. |
| `web/resources/views/beninlink/brand-accent.blade.php` | Le fragment injecté. Il n'écrit que ce que la classe lui donne. |
| `web/database/migrations/2026_09_18_110000_add_accent_color_to_general_settings.php` | La colonne, avec le défaut de la charte. |
| `web/tests/Feature/WebAccentColorTest.php` | 12 tests, dont le balayage et la comparaison jeton par jeton avec `tokens.css`. |

Quatre fichiers existants, d'une poignée de lignes chacun : le seeder (les deux
sociétés), `GeneralSettingsRepository::update()`, l'écran des paramètres, et un
`@include` dans **chacune des deux mises en page**. Les sept `lang/*/levels.php`
reçoivent le libellé — y compris les cinq locales que le lot 4 laissait de côté :
sans entrée, l'écran afficherait la clé brute `levels.accent_color`.

Ce sont bien **deux** mises en page et pas plus : les cinq autres vues qui chargent
`tokens.css` (les trois impressions, `api/docs`, `fedapay_callback`) n'emploient
aucun jeton d'accent — et un test le vérifie, pour que l'ajout d'un accent dans
l'une d'elles ne passe pas inaperçu.

### 14.5 Un défaut voisin, corrigé au passage

La couleur finit dans un bloc `<style>`. Blade échappe `<` et `>` — on ne sort donc
pas de l'élément — mais **`;`, `{` et `}` passent**, et suffisent à injecter du CSS
dans sa propre vitrine. `accent_color` est **normalisée à l'écriture** : un
hexadécimal, ou rien. Une saisie douteuse ne s'enregistre pas, et la page retombe
sur l'ocre de `tokens.css`.

⚠️ **`primary_color` et `text_color`, eux, ne le sont toujours pas** : ils datent du
socle et le lot 1 ne les a pas touchés sur ce point. La portée est faible (il faut
le droit `general_settings_update`, et l'effet s'arrête au site du transporteur
lui-même), mais c'est le même trou. À prendre avec le lot 6, qui touche déjà ces
vues — ou plus tôt si l'on ouvre l'écran des paramètres à un rôle moins large.

### 14.6 Les tests mordent, vérifié

Quatre régressions introduites exprès, une à la fois :

| Ce qu'on défait | Ce qui échoue |
|---|---|
| `jetons()` ne rend que `--bl-accent` (le lot 3 « naïf ») | 2 tests |
| la normalisation retirée à l'écriture | 1 test |
| l'`@include` retiré du back-office | 1 test |
| la migration retirée | 5 tests |

### 14.7 Ce que le lot 3 ne livre PAS

- **Les jetons de pastille ne suivent pas l'accent.** `--bl-pill-*` sont des
  **sémantiques de statut**, partagées avec `mobile/` : un colis en transit doit
  être de la même couleur pour tout le monde, quel que soit le transporteur. Un
  test le fige.
- **Le back-office n'injecte toujours pas `primary_color`.** Seul le site public le
  fait, depuis le socle. Ce n'est pas une régression de ce lot — c'est un constat
  qu'il rend visible, à traiter avec le lot 6 ou 7.
- **Aucun aperçu en direct** dans l'écran des paramètres : on enregistre, puis on
  recharge. Un aperçu demanderait du JavaScript dans une vue du socle.

---

## 15. Lot 6 — ce qui a été livré le 2026-09-18

### 15.1 Trois items sur quatre étaient déjà faits

Le §8 annonçait `focus-visible`, les `alt`, les 42 `table-responsive` et les deux
`http://`. À la vérification, **le lot 1 avait déjà livré** :

| Item | Où il vit depuis le lot 1 |
|---|---|
| `:focus-visible` sur liens, boutons et champs | `theme-frontend.css` §4 et `theme-backoffice.css` §10 |
| Les trois `viewport` qui bloquaient le zoom | `header.blade.php`, `master.blade.php`, `installer`, `parcel-map` |
| Le sidebar inactif à 4,30:1 | `.nav-left-sidebar .navbar-nav .nav-link` → `--bl-text-muted` |

Ils ne sont pas refaits. Ils sont désormais **tenus par des tests** — ce sont des
lignes du socle, donc précisément ce qu'une montée de We Courier réécrit.

### 15.2 Les images : 47, pas 127 — et le piège de mesure vaut d'être dit

Le premier comptage en a trouvé **127**. Il était faux, et d'une façon qui se
reproduira chez quiconque refait la mesure vite :

```
<img[^>]*>     ← FAUX sur du Blade
```

`->` contient un `>`. La classe `[^>]` s'arrête donc au premier `->` venu, en
plein milieu de la balise, et un

```html
<img src="{{ $u->image }}" alt="Photo de profil">
```

passe pour **dépourvu d'alt**. Le compte juste, avec un lecteur qui respecte les
guillemets, est **47 images dans 29 fichiers**. Le test embarque ce lecteur, et
son commentaire explique pourquoi une expression régulière ne suffit pas.

### 15.3 Un `alt` ne se remplit pas au kilomètre

`alt="image"` répété 47 fois est **pire que rien** : un lecteur d'écran annonce
alors « image » sans fin, et l'utilisateur perd le peu qu'il avait. Chaque image
a donc été classée.

**37 sont décoratives et reçoivent `alt=""`** — qui ne veut pas dire « je n'ai
pas trouvé quoi écrire » mais « saute-moi » :

- la vignette d'un tableau d'administration dont le **titre est dans la cellule
  voisine** (articles, services, partenaires, arguments) ;
- l'**avatar d'une notification**, dont le nom de la personne est rendu juste à
  côté — l'annoncer deux fois est du bruit ;
- l'illustration qui accompagne les écrans de connexion et d'inscription ;
- les icônes de réseaux sociaux des courriels : elles sont dans des `<a>` **sans
  `href`**, donc ce ne sont pas des liens (défaut du socle relevé, non corrigé
  ici — il ne relève pas de ce lot).

**Les dix autres portent une information que rien d'autre sur la page ne donne** :

| Image | Ce que l'`alt` dit | Pourquoi |
|---|---|---|
| Logo du transporteur (7 vues) | `settings()->name` | Le logo **identifie le site**. Il ne s'appelle pas « logo » |
| Logo de partenaire | le nom du partenaire | Il est **seul dans un lien** : sans alt, le lien est muet |
| Pièce d'identité, registre de commerce | « Pièce d'identité téléversée » | La **présence** du document est l'information |
| Badges Google Play / App Store | « Télécharger sur… » | Ce sont des liens |
| Aperçu de bannière | « Bannière actuelle » | Dire ce qu'on voit, pas le nom du champ |

**La plus importante est celle de la page de suivi public.** Quand aucun colis ne
correspond au numéro saisi, le bloc ne contient **qu'une image** — pas une ligne
de texte. Sans `alt`, la page ne disait strictement rien à qui ne la voit pas :
le formulaire semblait n'avoir rien fait. Elle dit maintenant « Aucun colis ne
correspond à ce numéro de suivi ».

Les six textes passent par **`lang/fr.json`**, le mécanisme du lot 4, plutôt que
par du français codé en dur : un seul fichier touché, et le test du lot 4 couvre
**automatiquement** les nouvelles clés.

### 15.4 Les tableaux : 5 écrans, pas 42

Il y a bien **57** `<table>` sans `table-responsive`. Mais **52 ne sont pas des
écrans**, et l'enveloppe n'y veut rien dire — `table-responsive` pose un
`overflow-x: auto` sur un conteneur de page :

| Cible | Tables | Pourquoi l'enveloppe est inutile |
|---|---|---|
| PDF (dompdf) | 22 | Une page PDF ne défile pas ; l'attribut est ignoré |
| Impression | 18 | Une feuille de papier non plus |
| Courriel | 9 | Le client de messagerie retire les `div` de mise en page ; Outlook rend en Word |
| Installeur | 3 | Il tourne une fois, chez l'intégrateur, sur un poste de travail |

C'est la même règle que le §6.7 posait pour le style en ligne : **ce qui vise le
papier ou le courriel ne suit pas les conventions de l'écran.**

Restent **5 vrais écrans**, tous enveloppés — dont les **trois matrices de
permissions** (`role/create`, `role/edit`, `user/permissions`), les tableaux les
plus larges de l'application : sur un téléphone, c'est la page entière qui
défilait, et les colonnes de droite devenaient inatteignables. Cela compte pour
des agents en hub, qui travaillent au téléphone.

La liste des familles exclues vit **dans le test** : ajouter une exception
demande de la justifier là, pas de la laisser passer en silence.

### 15.5 Le contenu mixte — et le `http://` qui n'était pas dans une vue

Les deux `http://` annoncés chargeaient Toastr depuis `cdn.bootcss.com`, dans
l'**installeur**. Un navigateur bloque une ressource en clair sur une page servie
en `https` : l'installeur perdait ses messages, sans rien dire ailleurs que dans
la console. Le dépôt **livre déjà Toastr localement** (`public/backend/vendor/`) :
les deux références pointent dessus, ce qui règle le contenu mixte **et** retire
un CDN — dans le sens du §7.5.

**Le troisième était ailleurs, et il coûte davantage.**

En fastcgi, nginx termine le TLS et **ne le dit pas à PHP**. Sans
`fastcgi_param HTTPS on`, `$_SERVER['HTTPS']` reste vide — et c'est la seule
chose que Symfony consulte ici : `TrustProxies` ne s'applique pas, il n'y a pas
de proxy HTTP devant, et `$proxies` vaut `null`. Notre propre
`docs/guides/infra/nginx/beninlink.conf` ne posait pas cette ligne.

Mesuré en construisant la requête telle que PHP-FPM la reçoit :

| Configuration | `isSecure()` | `url('/reinitialiser')` |
|---|---|---|
| `beninlink.conf` tel qu'il était | **non** | `http://pme.beninlink.app/reinitialiser` |
| avec `fastcgi_param HTTPS on` | oui | `https://pme.beninlink.app/reinitialiser` |

Ce que cela vise :

- le **lien de réinitialisation de mot de passe** envoyé par courriel ;
- le **`callback_url` remis à FedaPay** (`FedaPayController` le construit avec
  `route()`) — l'URL sur laquelle le client revient après avoir payé.

L'en-tête HSTS déjà posé fait remonter un navigateur en `https`, mais seulement
après une première visite du domaine, et il ne couvre pas une URL transmise à un
tiers. **Une ligne** corrige la racine, et la détection de schéma de l'installeur
(`if (!empty($_SERVER['HTTPS']))`) redevient juste du même coup — sans la
retoucher.

### 15.6 Ce que ce lot coûte à la carte de fusion, mesuré

C'est le **seul lot qui ne pouvait pas s'ajouter par-dessus** : aucune feuille de
style n'écrit un `alt`. Il touche donc des vues du socle, et le chiffre mérite
d'être posé plutôt que tu :

- **35 vues** en tout : **29** reçoivent un `alt`, **5** une enveloppe de
  tableau, et l'installeur ses deux références locales ;
- **15** d'entre elles étaient **déjà modifiées** par BeninLink — coût de fusion
  nul, le conflit existait déjà ;
- le coût réellement ajouté à la carte est donc de **20 fichiers**, et ils sont
  nommés dans le message du commit.

Pour comparaison, le lot 1 en avait touché **six**, et par choix : il pouvait
s'ajouter par-dessus. Ici, non — et c'est la raison pour laquelle cet item
traînait depuis le début du plan.

⚠️ Ce chiffre est mesuré contre `eb56a37`, que `docs/guides/socle/` donne pour la
base de fusion. Le lot 4 a signalé que cette affirmation n'est **pas vérifiable**
en l'état (le dépôt a deux racines, toutes deux portant déjà des ajouts
BeninLink). L'ordre de grandeur tient ; la précision, non, tant que ce guide
n'est pas corrigé.

### 15.7 Les tests mordent, vérifié

Cinq régressions introduites exprès, une à la fois, depuis l'arbre livré :

| Ce qu'on défait | Ce qui échoue |
|---|---|
| l'`alt` de la page de suivi retiré | 2 tests |
| un `alt=""` d'avatar remplacé par « Photo » | 1 test |
| une matrice de permissions dénudée | 1 test |
| le CDN en clair rétabli | 1 test |
| `fastcgi_param HTTPS on` retirée d'nginx | 1 test |

### 15.8 Ce que le lot 6 ne livre PAS

- **Les `<a>` sans `href`** des pieds de courriel : ce sont de faux liens, relevés
  ici mais hors du périmètre d'un lot d'accessibilité des images.
- **Le contraste des textes du socle en dehors du sidebar** n'a pas été
  re-balayé : les six pastilles et les surfaces sémantiques l'ont été au lot 1.
- **Aucun audit au lecteur d'écran** : ce lot corrige ce qu'un fichier peut
  prouver. Les ordres de tabulation, les libellés de formulaire et les régions
  ARIA demandent un essai réel, et c'est un autre travail.
- **L'installeur reste en anglais** (§12.8), et son `lang="en"` est donc **juste**
  — il n'est pas internationalisé du tout. Le corriger sans le traduire ferait
  annoncer du français à un lecteur d'écran sur une page anglaise.
