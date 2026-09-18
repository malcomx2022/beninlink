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
`app/Http/Helper/Helper.php:314-373` (`StatusParcel()`) est le **point unique**
qui rend la pastille de statut. Sa table de couleurs contredit la charte :

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
- **`public/backend/libs/js/custom.js:13` et `:30`** : `confirmButtonText: 'Yes'`,
  `denyButtonText: 'Cancel'` en dur — alors que l'autre boîte de dialogue du même
  fichier (l. 50-51) utilise bien les variables `yes` / `cancel` injectées par
  `backend/partials/footer.blade.php:29-30`. **Certaines confirmations de
  suppression s'affichent en anglais, d'autres en français.**
- Les libellés `data-title` des actions de statut sont en anglais dans
  `Helper.php:260-290` (`"pickup assign"`, `"Delivery man assign"`, …).

### 2.10 Sélecteur de langue : sept locales pour un produit béninois
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
| **2** | `StatusParcel()` : sémantique + `else` de repli · pastilles `bl-pill` · douane aux couleurs de la charte | Les statuts se lisent **comme dans l'app**. Les classes `.bl-pill--*` sont **déjà définies** par le lot 1 : il ne reste que du PHP |
| **3** | `accent_color` en base + réglage + `:root` | L'ocre devient réglable par transporteur. ⚠️ **Les défauts de couleur et la migration de données sont déjà faits** au lot 1 (voir §10) : ce lot ne porte plus que l'accent |
| **4** | `auth/login` · les 2 lignes de `custom.js` · les 11 titres · les 85 placeholders · les `data-title` | Plus d'anglais sur les écrans d'entrée |
| **5** | Sidebar groupé · « Retrait » conditionné + état vide · sélecteur de langue réduit à FR/EN · filtre du tableau de bord | Ergonomie du back-office |
| **6** | `focus-visible` · `alt` par lot · 42 `table-responsive` · les 2 `http://` | Accessibilité et petits écrans |
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
3. **Jusqu'où pousser le multi-tenant.** Trois options : (a) la charte est figée
   dans `tokens.css` ; (b) `primary_color` + `accent_color` restent réglables par
   transporteur, la charte n'étant que le **défaut** ; (c) les deux, avec un
   verrou pour le tenant BeninLink. Recommandation : **(b)** — le socle est
   multi-tenant par nature, et un transporteur qui achète le SaaS voudra sa
   couleur. La charte est un défaut d'usine, pas une prison.
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
