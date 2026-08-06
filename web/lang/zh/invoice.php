<?php

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;

return [
            'id'                      => 'ID',
            InvoiceStatus::PAID       => '有薪酬的',
            InvoiceStatus::UNPAID     => '未付',
            InvoiceStatus::PROCESSING => '加工',
            'paid_out'                => '支付',
            'invoice'                 => '发票',
            'status_updated'          => '发票状态更新成功',
            'status_update'           => '状态更新',
            'paid_invoice'            => '已付发票',

            'invoice_generated_successfully' => '发票生成成功',
            'invoice_generate_menually' => '发票生成',
            'generate'                 => '产生',
            'invoice_description'     =>'点击生成按钮后，会根据商户的付款周期生成发票。'


       ];
