<?php

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;

return [
            'id'                      => 'بطاقة تعريف',
            InvoiceStatus::PAID       => 'دفع',
            InvoiceStatus::UNPAID     => 'غير مدفوع',
            InvoiceStatus::PROCESSING => 'يتم المعالجة',
            'paid_out'                => 'دفع',
            'invoice'                 => 'فاتورة',
            'status_updated'          => 'تم تحديث حالة الفاتورة بنجاح',
            'status_update'           => 'تحديث الحالة',
            'paid_invoice'            => 'فاتورة مدفوعة',

            'invoice_generated_successfully' => 'تم إنشاء الفاتورة بنجاح',
            'invoice_generate_menually'      => 'إنشاء الفاتورة',
            'generate'                       => 'يولد',
            'invoice_description'            => 'بعد النقر فوق الزر "إنشاء" ، سيتم إنشاء الفاتورة بناءً على فترة الدفع الخاصة بالتاجر.'

       ];
