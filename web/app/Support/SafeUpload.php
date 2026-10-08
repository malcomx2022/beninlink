<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * L'extension d'un fichier téléversé, déduite de son CONTENU et bornée à une liste (**S126**).
 *
 * Les dépôts du socle nommaient le fichier écrit sous `public/uploads/` par
 * `getClientOriginalExtension()` — l'extension que le CLIENT annonce. Un marchand inscrit par
 * lui-même joignait `x.php` à un ticket de support (aucune règle de validation sur la pièce
 * jointe), le fichier devenait `public/uploads/support/AAAAMMJJ.php`, et nginx l'exécutait :
 * exécution de code sur le serveur commun à tous les transporteurs.
 *
 * Ici l'extension vient du type MIME reconnu par `finfo` (`guessExtension()`) et doit figurer dans
 * `EXTENSIONS` ; sinon le téléversement est refusé. Ni `php`, ni `html`, ni `svg` (script servi
 * depuis notre domaine) n'y sont. Un fichier piégé qui se fait passer pour une image garde une
 * extension d'image, que nginx sert sans l'exécuter.
 */
final class SafeUpload
{
    public const EXTENSIONS = ['jpg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];

    public static function extension(UploadedFile $fichier): string
    {
        $extension = strtolower((string) $fichier->guessExtension());
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;

        if (! in_array($extension, self::EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => __('This file type is not accepted.'),
            ]);
        }

        return $extension;
    }
}
