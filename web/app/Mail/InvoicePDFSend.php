<?php

namespace App\Mail;

use App\Models\Backend\Merchantpanel\Invoice;
use App\Services\Invoicing\SettlementPdf;
use App\Services\Invoicing\SettlementStatement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Le relevé de règlement d'un marchand, envoyé en pièce jointe (PDF).
 *
 * Hérité du socle sous ce nom, et resté **mort** depuis : jamais instancié, il
 * portait un expéditeur codé en dur (`admin@example.com`), un sujet anglais
 * (« Invoice P D F Send ») et un nom de vue — `invoice_mail_pdf` — qui ne
 * résolvait pas sous Linux, le fichier s'appelant `Invoice_mail_pdf.blade.php`
 * (**S69**, lot de nettoyage T1). Le fichier est conservé (règle du projet :
 * 0 fichier supprimé du socle) et rendu juste, pour que le premier qui le
 * branche obtienne le relevé officiel, pas une panne en production.
 *
 * **Branché depuis S75 (R9, 2026-10-03)** : `StatementMailer` l'envoie à
 * l'émission de chaque relevé, au courriel du compte marchand, avec le PDF de
 * `SettlementPdf` — le même que le téléchargement. Jamais de réémission
 * modifiée : un relevé émis ne change pas, une correction est un nouvel état.
 *
 * Trois règles du dépôt, les mêmes que `MerchantFeedMail` :
 * - **D13** — `ShouldQueue` : l'envoi, et le rendu du PDF, quittent la requête.
 * - **F4** — aucune lecture de `settings()` : la marque est **passée**, résolue
 *   sur la société du marchand destinataire ; le relevé lui-même vient de
 *   `SettlementStatement`, qui lit la société par identifiant.
 * - **Un seul document** : le PDF joint est celui de
 *   `backend.invoice.statement_pdf`, le relevé du chantier 4 — pas une seconde
 *   mise en page à maintenir.
 */
class InvoicePDFSend extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** Non `readonly` : `SerializesModels` la réaffecte à la sortie de la file. */
    public Invoice $invoice;

    /**
     * @param array{nom:?string,logo:mixed,courriel:?string,telephone:?string,mentions:?string} $marque
     */
    public function __construct(
        Invoice $invoice,
        public readonly array $marque,
        public readonly string $destinataire,
    ) {
        $this->invoice = $invoice;
    }

    public function build()
    {
        $statement = SettlementStatement::for($this->invoice);
        $numero = $statement['number'];

        $message = $this->subject(
            __('statement.title') . ' ' . __('statement.number') . ' ' . $numero
        );

        // `from()` refuse une adresse vide : une société dont le courriel de
        // contact n'est pas renseigné part avec l'expéditeur de l'application.
        if (filled($this->marque['courriel'] ?? null)) {
            $message = $message->from($this->marque['courriel']);
        }

        // S75 (R9) : LE PDF officiel, celui du téléchargement — un seul point de rendu.
        $pdf = SettlementPdf::render($this->invoice);

        return $message
            ->view('backend.merchant.invoice.Invoice_mail_pdf', [
                'destinataire' => $this->destinataire,
                'statement' => $statement,
                'companyName' => $this->marque['nom'],
                'companyLogo' => $this->marque['logo'],
                'courriel' => $this->marque['courriel'],
                'telephone' => $this->marque['telephone'],
                'mentions' => $this->marque['mentions'],
            ])
            ->attachData($pdf, SettlementPdf::fileName($this->invoice), ['mime' => 'application/pdf']);
    }
}
