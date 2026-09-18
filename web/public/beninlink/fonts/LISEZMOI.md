# Polices de la charte BeninLink

Sora (titres et chiffres) et DM Sans (corps), **auto-hébergées**. Ce sont les
mêmes familles que `mobile/` et `mobile-livreur/`, qui les chargent depuis npm
(`@expo-google-fonts/sora`, `@expo-google-fonts/dm-sans`) — voir
`mobile/src/theme/typography.ts`.

## Pourquoi auto-hébergées et pas un CDN

Les vues de `web/` référencent déjà ~150 fois `cdn.jsdelivr.net` et une douzaine
d'autres domaines. Pour des utilisateurs béninois en 3G, chaque CDN est une
latence de plus et un point de panne de plus. Ces quatre fichiers partent du même
serveur que le reste du site.

## Les fichiers

Ce sont des polices **variables** : un seul fichier par famille couvre toutes les
graisses dont la charte a besoin (Sora 600/700, DM Sans 400/500).

| Fichier | Sous-ensemble | Taille |
|---|---|---|
| `sora-latin.woff2` | latin | 25 Ko |
| `sora-latin-ext.woff2` | latin étendu | 12 Ko |
| `dm-sans-latin.woff2` | latin | 37 Ko |
| `dm-sans-latin-ext.woff2` | latin étendu | 18 Ko |

Le sous-ensemble **latin** suffit au français : il couvre `U+0000-00FF` (tous les
accents), `U+0152-0153` (**Œ œ**) et `U+20AC` (€). Le **latin étendu** est fourni
pour les noms **fon et yoruba** (ɖ, ɛ, ɔ, ẹ, ọ, ṣ), qui vivent dans
`U+0100-02BA` et `U+1E00-1E9F` — un marchand ou une adresse peut en porter.
Grâce aux `unicode-range` déclarés dans `tokens.css`, le navigateur ne télécharge
`latin-ext` que si un tel caractère apparaît réellement : **coût nul** dans le cas
courant, et pas de carré vide dans le cas béninois.

## Licence

Les deux familles sont sous **SIL Open Font License 1.1**, qui autorise la
redistribution à condition de joindre la licence — c'est ce que font
`OFL-Sora.txt` et `OFL-DMSans.txt`, à conserver à côté des `.woff2`.

- Sora — Copyright 2019 The Sora Project Authors
- DM Sans — Copyright 2014 The DM Sans Project Authors

## Mettre à jour

Reprendre les URL depuis
`https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=DM+Sans:wght@400;500&display=swap`
(avec un en-tête `User-Agent` de navigateur récent, sinon Google sert du TTF),
retélécharger les quatre `.woff2` et **vérifier que les `unicode-range` de
`tokens.css` correspondent toujours** à ceux de la feuille servie.
