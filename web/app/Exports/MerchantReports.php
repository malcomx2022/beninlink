<?php

namespace App\Exports;

use App\Models\Backend\Merchant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\FromView;

class MerchantReports implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {

        return View('backend.reports.print_view',[
            'report_title'          => Request::input('report_title'),
            'user_type'             => Request::input('user_type'),
            'total_parcel'          => Request::input('total_parcel'),
            'merchant'              => Merchant::find(Request::input('merchant_id')),
            'parcel_ids'            => Request::input('parcel_ids'),
            'total_paid_to_merchant'=> Request::input('total_paid_to_merchant'),
            'merchant_statement_ids'=> Request::input('merchant_statement_ids')
        ]);
    }
}
