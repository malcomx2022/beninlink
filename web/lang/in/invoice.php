<?php

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;

return [
            'id'                      => 'पहचान',
            InvoiceStatus::PAID       => 'भुगतान किया है',
            InvoiceStatus::UNPAID     => 'अवैतनिक',
            InvoiceStatus::PROCESSING => 'प्रसंस्करण',
            'paid_out'                => 'बाहर का भुगतान किया',
            'invoice'                 => 'इनवॉइस',
            'status_updated'          => 'चालान स्थिति सफलतापूर्वक अपडेट की गई',
            'status_update'           => 'स्थिति अपडेट',
            'paid_invoice'            => 'भुगतान चालान',

            'invoice_generated_successfully' => 'चालान सफलतापूर्वक उत्पन्न हुआ',
            'invoice_generate_menually'      => 'चालान जनरेट करें',
            'generate'                       => 'बनाना',
            'invoice_description'            =>'जनरेट बटन पर क्लिक करने के बाद, मर्चेंट की भुगतान अवधि के आधार पर चालान जनरेट होगा।'

       ];
