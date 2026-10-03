<?php

namespace App\Services\Invoicing;

use App\Mail\InvoicePDFSend;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchantpanel\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie le relevé de règlement au marchand, **à son émission** — décision
 * **R9** du porteur (2026-10-03, S75).
 *
 * Le mailable `InvoicePDFSend` était prêt depuis S69 et volontairement non
 * branché : cadence, destinataire et opposabilité étaient à trancher. C'est
 * fait : à chaque relevé émis (`InvoiceRepository::store()`, planifié ou
 * manuel), un courriel part **en file** (**D13**) au courriel du compte
 * marchand, avec **le** PDF officiel en pièce jointe (`SettlementPdf`, le même
 * que le téléchargement).
 *
 * Trois règles :
 *
 * - **hors transaction** : on envoie après que le relevé est écrit (**D8**) ;
 *   un courriel qui échoue ne défait jamais un relevé ;
 * - **F4** : la marque vient de la société **du relevé**, lue par identifiant,
 *   jamais de `settings()` — le planificateur n'a pas de société ambiante ;
 * - **jamais de réémission modifiée** : le relevé n'a aucun chemin de
 *   modification de ses montants ; une correction est un nouvel état, pas un
 *   PDF remplacé. Ce service n'envoie qu'à l'émission.
 */
class StatementMailer
{
    /** `true` si un courriel a été mis en file. */
    public function envoyer(Invoice $invoice): bool
    {
        $destinataire = (string) ($invoice->merchant?->user?->email ?? '');
        if ($destinataire === '' || !filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
            Log::info('Relevé non envoyé : le compte marchand n’a pas de courriel', [
                'invoice_id' => $invoice->invoice_id,
                'merchant_id' => $invoice->merchant_id,
            ]);

            return false;
        }

        try {
            Mail::to($destinataire)->queue(new InvoicePDFSend($invoice, $this->marqueDe((int) $invoice->company_id), $destinataire));

            return true;
        } catch (\Throwable $e) {
            // Un courriel qui ne part pas ne fait pas échouer l'émission du relevé.
            Log::warning('Relevé : mise en file du courriel en échec', [
                'invoice_id' => $invoice->invoice_id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Les éléments de marque de la société du relevé — les mêmes cinq lectures
     * que `EmailChannel::marqueDe()`, par identifiant (F4).
     *
     * @return array{nom:?string,logo:mixed,courriel:?string,telephone:?string,mentions:?string}
     */
    private function marqueDe(int $companyId): array
    {
        $societe = GeneralSettings::with('rxlogo')->find($companyId);

        return [
            'nom' => $societe?->name,
            'logo' => $societe?->rxlogo?->original,
            'courriel' => $societe?->email,
            'telephone' => $societe?->phone,
            'mentions' => $societe?->copyright,
        ];
    }
}
