<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\FromView;

class ReportExports implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */


    public function view(): View
    {

        // $report_title           = $request->report_title;
            // $merchant_id            = $request->merchant_id;
            // $parcel_ids             = $request->parcel_ids;
            // $merchant_statement_ids = $request->merchant_statement_ids;
            // $total_parcel           = $request->total_parcel;
            // $total_paid_to_merchant = $request->total_paid_to_merchant;

            // if($user_type == 1):
            // elseif($user_type == 2):
            // elseif($user_type == 3):
            // endif;


        return View('backend.reports.print_view',[
            'user_type'=>Request::input('user_type'),
            'total_parcel'=>Request::input('total_parcel')
        ]);
    }
}
