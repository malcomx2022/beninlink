<?php

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;

return [
            'id'                      => 'IDENTIFICACIÓN',
            InvoiceStatus::PAID       => 'PAGADA',
            InvoiceStatus::UNPAID     => 'NO PAGADO',
            InvoiceStatus::PROCESSING => 'PROCESANDO',
            'paid_out'                => 'Pagado',
            'invoice'                 => 'Factura',
            'status_updated'          => 'Estado de la factura actualizado con éxito',
            'status_update'           => 'Actualización de estado',
            'paid_invoice'            => 'factura pagada',

            'invoice_generated_successfully' => 'Factura generada con éxito',
            'invoice_generate_menually' => 'Generar factura',
            'generate'                 => 'Generar',
            'invoice_description'     =>'Después de hacer clic en el botón Generar, la factura se generará según el período de pago del comerciante.'


       ];
