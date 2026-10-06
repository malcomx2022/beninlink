# Migrer le back-office de Bootstrap 4 à Bootstrap 5

> Chantier **hors lot** de `docs/guides/charte-web/` — et il l'est resté du début
> à la fin du plan, à raison. Ce guide le mesure, le découpe, et nomme ce qui
> casserait en silence. Les deux premières étapes sont **faites** ; les trois
> autres attendent une décision, parce qu'elles changent l'apparence.
> Dernière mesure : 2026-09-19, sur `main`.

---

## 0. Pourquoi les deux versions cohabitent

Ce n'est pas un oubli, c'est **ce qui fait tenir le back-office**.

| Fichier | Ce qu'il charge |
|---|---|
| `backend/partials/header.blade.php` | Bootstrap **5**.2.0 (local), puis Bootstrap **4** (local depuis l'étape A) |
| `backend/partials/footer.blade.php` | le JS de Bootstrap **5**, puis celui de Bootstrap **4** |

Les deux JS s'exécutent, et c'est exactement ce qui marche : Bootstrap 5 câble
les `data-bs-*`, Bootstrap 4 câble les `data-*`. Retirer l'un des deux casse la
moitié des menus, modales et onglets du panneau.

---

## 1. L'inventaire, mesuré

L'audit de la charte annonçait « **217 `data-toggle`** ». C'est exact — et c'est
la plus petite des trois mesures qui comptent.

### 1.1 Les attributs de comportement

| Attribut | Bootstrap 4 (vues) | Bootstrap 5 déjà présent |
|---|---|---|
| `data-toggle` | **217** | 17 |
| `data-target` | **66** | 15 |
| `data-dismiss` | **46** | 16 |
| `data-parent` | 2 | 1 |
| **Total** | **331** | 49 |

Renommage mécanique, scriptable, vérifiable.

### 1.2 Les classes — le chiffre brut, et le chiffre utile

Le comptage brut des classes renommées par Bootstrap 5 donne **~2 770**
occurrences dans les vues : `form-group` ×1 161, `float-left/right` ×473,
`font-weight-*` ×338, `pl-*`/`pr-*` ×215, `text-left/right` ×158…

**Ce chiffre trompe.** La plupart de ces noms sont redéfinis par le `style.css`
du socle ou par la charte : les retirer de Bootstrap 4 ne changerait rien,
puisque la règle vient d'ailleurs.

La mesure utile est donc : *quelles classes employées par les vues sont
fournies par Bootstrap 4, absentes de Bootstrap 5, et redéfinies ni par le socle
ni par la charte ?*

> **26 classes distinctes, 749 occurrences.**

| Classe | Occ. | Devient |
|---|---|---|
| `font-weight-bold` / `-light` | 337 | `fw-bold` / `fw-light` |
| `pl-*` / `pr-*` | 213 | `ps-*` / `pe-*` |
| `sr-only` | 56 | `visually-hidden` |
| `badge-pill` | 35 | `rounded-pill` |
| `btn-block` | 26 | `d-grid` sur le parent |
| `ml-*` / `mr-*` | 48 | `ms-*` / `me-*` |
| `input-group-prepend` / `-append` | 10 | retirés (le `.input-group-text` suffit) |
| `custom-select` | 4 | `form-select` |
| `text-sm-right`, `float-sm-right`, `form-inline` | 11 | `text-sm-end`, `float-sm-end`, utilitaires flex |

C'est **quatre fois moins** que le chiffre brut, et c'est là que porte le travail.

### 1.3 L'API jQuery — la bonne nouvelle

Bootstrap 5 supprime l'API jQuery (`$('#x').modal('show')`). Le dépôt n'en
compte que **six** appels, tous dans notre propre code :

| Fichier | Appel |
|---|---|
| `backend/wallet_request/index.blade.php` | `$('#add-to-wallet-modal').modal('show')` |
| `public/backend/js/parcel/custom.js` | `$("#"+type).modal('show')` |
| `public/backend/js/dynamic-modal.js` | `.modal('hide')` ×2 |
| `public/backend/libs/js/main-js.js` | `.tooltip()`, `.popover()` |

Six reprises. C'est ce qui rend la migration envisageable.

### 1.4 Les greffons tiers — un seul point d'attention

Contrairement à ce qu'on pouvait craindre, presque aucun greffon chargé ne
dépend de Bootstrap : `datatables`, `toastr`, `sweetalert`, `charts`,
`slimscroll` en sont indépendants. Le dossier `vendor/` en contient d'autres
adossés à Bootstrap 4 (`bootstrap-select`, `bootstrap-colorpicker`,
`daterangepicker`) — **aucune vue ne les charge**.

Reste **summernote** (32 références), qui publie un build `summernote-bs5`.
À vérifier au moment de l'étape E, pas avant.

---

## 2. Ce qui casserait **en silence**

C'est le cœur de ce guide, et la raison pour laquelle l'étape B a été faite
d'avance.

Le `style.css` du socle dessine la flèche des entrées de menu dépliables avec
**neuf** règles qui sélectionnent sur `[data-toggle="collapse"]` :

```css
.nav-left-sidebar .nav-link[data-toggle="collapse"]::after { … }
```

Renommer les attributs des vues en `data-bs-toggle` rend ces neuf règles
**inopérantes d'un coup**. Chaque sous-menu perd sa flèche et sa rotation à
l'ouverture. Aucune erreur, aucun avertissement dans la console : juste des
flèches disparues, sur tous les écrans à la fois.

**C'est déjà désamorcé** — voir l'étape B.

---

## 3. Le découpage

Chaque étape est réversible, et les deux premières n'ont **aucun** effet visuel.

### Étape A — Bootstrap 4 servi par le dépôt ✅ *faite*

Le socle prenait la feuille sur `maxcdn.bootstrapcdn.com` en **4.1.1**, pendant
que le pied de page servait le script de la copie locale en **4.1.0**. La
feuille et le script n'étaient donc **pas de la même version** — un décalage que
personne n'avait relevé parce qu'il ne produit rien de visible.

La copie locale les réaligne et retire un CDN tiers de chaque page du
back-office. Gain immédiat, risque nul.

### Étape B — doubler les neuf sélecteurs du chevron ✅ *faite*

Les neuf règles du §2 sont recopiées dans `theme-backoffice.css` sous
`[data-bs-toggle]`. Tant que les vues portent l'ancien attribut, elles ne
correspondent à rien : **la page est inchangée**. Le jour où une vue passe au
nouvel attribut, sa flèche suit sans qu'on y pense.

On ne touche pas aux 7 263 lignes du socle — c'est la méthode du lot 1.

### Étape C — renommer les 331 attributs ⏳

Mécanique et scriptable : `data-toggle` → `data-bs-toggle`, `data-target` →
`data-bs-target`, `data-dismiss` → `data-bs-dismiss`, `data-parent` →
`data-bs-parent`.

⚠️ **Deux précautions.** `data-toggle="tooltip"` et `data-toggle="popover"` sont
lus par `main-js.js` via jQuery : les renommer sans reprendre ces deux lignes
éteint les infobulles. Et `data-toggle` apparaît aussi dans des greffons tiers
(`data-toggle="datepicker"`…) qui n'ont rien à voir avec Bootstrap — un
remplacement aveugle les casse. **Filtrer sur les valeurs de Bootstrap**
(`collapse`, `modal`, `tab`, `dropdown`, `pill`, `tooltip`, `popover`, `buttons`).

Après cette étape, Bootstrap 4 peut encore être chargé : les deux attributs
coexistent sans se gêner. **C'est ce qui rend l'étape réversible.**

### Étape D — renommer les 26 classes ⏳

749 occurrences, également scriptables, mais **celle-ci se voit**. Elle demande
la vérification visuelle sur les trois paniers d'écrans que le §8 de l'audit
réclame depuis le début, et qu'aucun des sept lots n'a faite.

### Étape E — retirer Bootstrap 4 ⏳

Dans cet ordre : le **CSS** d'abord (l'écart se voit tout de suite), le **JS**
ensuite (les six appels jQuery du §1.3 et le build `summernote-bs5`). Garder les
fichiers : la règle du projet est « 0 fichier supprimé du socle ».

---

## 4. Ce que la suite de tests tient

`web/tests/Feature/BootstrapMigrationTest.php` :

- l'**étape A** ne se défait pas, et les deux fichiers Bootstrap 4 servis
  annoncent la **même version** ;
- les **neuf** sélecteurs du socle sont toujours neuf, et toujours doublés ;
- l'**inventaire** du §1 reste vrai, avec des bornes larges — elles ne figent
  pas un chiffre, elles empêchent ce guide de décrire un dépôt qui n'existe
  plus. Un franchissement par le bas est une bonne nouvelle qui demande de
  relire ; par le haut, c'est que quelqu'un écrit du Bootstrap 4 **neuf** ;
- les **deux** Bootstrap sont encore chargés, **à dessein** : le jour où l'un
  disparaît, le test échoue et renvoie ici.

---

## 5. Faut-il migrer ?

La question mérite d'être posée avant les étapes C à E, comme le guide de montée
du socle la pose pour We Courier.

**Pour :** Bootstrap 4 n'est plus maintenu depuis janvier 2023 ; le back-office
télécharge **deux** frameworks CSS (335 Ko cumulés) là où un suffirait ; et tant
que les deux cohabitent, chaque écran neuf doit choisir sa syntaxe — les 17
`data-bs-*` déjà présents montrent que le mélange a commencé tout seul.

**Contre :** rien de ce que le produit doit faire n'en dépend. Les étapes D et E
changent l'apparence de chaque écran, et le projet n'a **jamais** fait de
vérification visuelle — sept lots de charte ont été livrés sur la foi de fichiers
et de calculs. Migrer sans ce regard, c'est déplacer un risque qu'on ne sait pas
mesurer.

> **Tranché le 2026-10-06 (S107, D15)** : les étapes A et B suffisent ; C, D et E ne sont pas
> entreprises. À rouvrir après la recette pilote, avec le contrôle visuel humain (E5), si un
> défaut d'écran le demande.

**Recommandation : les étapes A et B, faites, suffisent pour aujourd'hui.** Elles
retirent un CDN, réalignent deux versions et désamorcent le piège silencieux, le
tout sans qu'un pixel bouge. Les étapes C à E se décident quand quelqu'un pourra
regarder les écrans — et C peut se jouer seule, sans D ni E, le jour où on veut
arrêter d'écrire du Bootstrap 4 neuf.
