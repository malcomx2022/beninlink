<?php

namespace App\Services\Invoicing;

use App\Models\Backend\Merchantpanel\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * **Le** PDF du relevé de règlement — un seul point de rendu (S75, R9).
 *
 * Le document téléchargé depuis les deux panneaux, celui servi par lien signé
 * à l'app, et celui joint au courriel sont **le même fichier** : même vue
 * (`backend.invoice.statement_pdf`), même source (`SettlementStatement`), même
 * format. Un test l'exige — le nom de la vue, entre guillemets, n'apparaît
 * nulle part ailleurs dans `app/` que dans la constante `VUE` ci-dessous.
 *
 * Et c'est ce qui porte la règle **D8** côté courriel : le relevé envoyé est
 * celui qu'on télécharge ; il ne se réémet pas « corrigé » en silence, parce
 * qu'il n'existe aucun second chemin pour le fabriquer.
 */
class SettlementPdf
{
    public const VUE = 'backend.invoice.statement_pdf';

    /** Le contenu binaire du PDF. */
    public static function render(Invoice $invoice): string
    {
        $statement = SettlementStatement::for($invoice);

        return Pdf::loadView(self::VUE, compact('statement'))->setPaper('a4')->output();
    }

    /** Le nom de fichier, identique au téléchargement et à la pièce jointe. */
    public static function fileName(Invoice $invoice): string
    {
        return 'releve-' . $invoice->invoice_id . '.pdf';
    }
}
