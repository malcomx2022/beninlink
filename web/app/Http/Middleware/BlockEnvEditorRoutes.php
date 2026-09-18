<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * S27 — l'éditeur de `.env` n'est plus joignable par le web.
 *
 * ┌─ CE QUE C'ÉTAIT ───────────────────────────────────────────────────────────┐
 * │ `geo-sot/laravel-env-editor` enregistre onze routes sous `/env-editor`, et  │
 * │ son fournisseur est **auto-découvert** : il se charge dans tous les         │
 * │ environnements, production comprise, sans garde d'environnement. Le         │
 * │ middleware que sa configuration publiée leur donnait était `['web']` —      │
 * │ **ni `auth`, ni permission**.                                              │
 * │                                                                            │
 * │ Constaté, pas déduit : `GET /env-editor` répondait **200 sans aucune        │
 * │ authentification**, et `GET /env-editor/files` aussi. Un visiteur lisait et │
 * │ modifiait donc le fichier d'environnement : identifiants de base de         │
 * │ données, `APP_KEY`, clés FedaPay, identifiants de messagerie. `POST         │
 * │ /env-editor/key` régénère une clé — donc invalide toutes les données        │
 * │ chiffrées et toutes les sessions ; `restore-backup` remplace le fichier     │
 * │ entier ; `download` le télécharge.                                         │
 * │                                                                            │
 * │ La configuration nginx du projet ne les bloque pas : son `location /`       │
 * │ envoie tout à Laravel, et sa règle de refus ne couvre que les fichiers      │
 * │ commençant par un point.                                                   │
 * └────────────────────────────────────────────────────────────────────────────┘
 *
 * **404 et non 403** : une interface qui n'a pas à exister ne confirme pas son
 * existence. Rien dans le produit ne lie vers ces routes.
 *
 * ⚠️ Le paquet **reste installé**, et ce n'est pas un oubli :
 * `InstallerController` utilise sa **façade** (`EnvEditor::editKey()`) pour
 * écrire `.env` pendant l'installation. On coupe l'interface web, pas la
 * bibliothèque — même doctrine que S21 et D10 : désactiver le module, garder le
 * code. Pour rouvrir l'interface un jour, il faudrait retirer ce middleware de
 * `config/env-editor.php` **et** lui en donner un vrai : authentification,
 * super-administrateur, et une trace de qui a changé quoi.
 */
class BlockEnvEditorRoutes
{
    public function handle(Request $request, Closure $next)
    {
        abort(404);
    }
}
