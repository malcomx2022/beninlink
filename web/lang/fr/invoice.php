<?php

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;

return [
    'id' => 'ID',
    InvoiceStatus::PAID => 'PAYÉ',
    InvoiceStatus::UNPAID => 'NON PAYÉ',
    InvoiceStatus::PROCESSING => 'EN TRAITEMENT',
    'paid_out' => 'Payé',
    'invoice' => 'Facture',
    'status_updated' => 'Le statut de la facture a été mis à jour avec succès',
    'status_update' => 'Mise à jour du statut',
    'paid_invoice' => 'Facture payée',
    'invoice_generated_successfully' => 'Facture générée avec succès',
    'invoice_generate_menually' => 'Générer une facture',
    'generate' => 'Générer',
    'invoice_description' => 'Après avoir cliqué sur le bouton Générer, la facture sera générée en fonction de la période de paiement du marchand.',
];