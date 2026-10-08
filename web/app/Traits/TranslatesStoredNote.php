<?php

namespace App\Traits;

use Illuminate\Support\Facades\Lang;

/**
 * **S118** — une note de relevé écrite comme une clé se lit traduite.
 *
 * Le dépôt des colis écrit la note d'une ligne de relevé en traduisant une clé `statementNote.*`.
 * Cinq de ces clés n'existaient pas : la ligne gardait la clé brute
 * (`statementNote.return_received_by_merchant_statment`), et le relevé du marchand
 * l'affichait telle quelle. Les clés existent depuis S118 ; les lignes déjà écrites
 * ne sont pas réécrites (une écriture comptable ne se modifie pas, D8), elles se
 * **lisent** traduites. Une note libre, ou une clé inconnue, est rendue telle quelle.
 */
trait TranslatesStoredNote
{
    public function getNoteAttribute($valeur)
    {
        if (is_string($valeur) && preg_match('/^statementNote\.[A-Za-z0-9_]+$/', $valeur) && Lang::has($valeur)) {
            return __($valeur);
        }

        return $valeur;
    }
}
