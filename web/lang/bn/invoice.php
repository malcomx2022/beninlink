<?php

use App\Enums\InvoiceStatus;

return [
            'id'                      =>   'আইডি',
            InvoiceStatus::PAID       => 'পরিশোধ ',
            InvoiceStatus::UNPAID     => 'অবৈতনিক',
            InvoiceStatus::PROCESSING => 'প্রক্রিয়াকরণ',
            'paid_out'                => 'পরিশোধিত',
            'invoice'                 => 'চালান',
            'status_updated'          => 'চালানের স্থিতি সফলভাবে আপডেট হয়েছে ৷',
            'status_update'           => 'অবস্থা হালনাগাদ',
            'paid_invoice'            => 'পরিশোধিত চালান',

            'invoice_generated_successfully' => 'চালান সফলভাবে তৈরি হয়েছে৷',
            'invoice_generate_menually' => ' চালান তৈরি করুন',
            'generate'                 => 'তৈরি করুন',
            'invoice_description'     =>'জেনারেট বোতামে ক্লিক করার পরে, ব্যবসায়ীর অর্থপ্রদানের সময়কালের উপর নির্ভর করে চালান তৈরি হবে ।'

        ];
