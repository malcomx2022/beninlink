<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Backend\Parcel;
use App\Repositories\Reports\ReportsInterface;

class MerchantReportsController extends Controller
{
    protected $hub;
    protected $repo;
    public function __construct(ReportsInterface $repo)
    {
        $this->repo = $repo;
    }
    public function parcelReports(Request $request){
        $parcels = [];
        return view('backend.merchant_panel.reports.parcel_reports',compact('request','parcels'));
    }

    public function parcelSReports(Request $request){
        if($this->repo->merchantParcelReports($request)){
            $parcels      =  $this->repo->merchantParcelReports($request);
            $print        =   true;
            $parcel_ids   = '';
            foreach ($parcels as $key=>$parcel) {
                foreach ($parcel as $key => $parcl) {
                    $parcel_ids  = $parcl->id.','.$parcel_ids;
                }
            }
            return view('backend.merchant_panel.reports.parcel_reports',compact('parcels','request','print','parcel_ids'));
        }else{
            return redirect()->back();
        }
    }
    public function parcelReportsPrint(Request $request,$array){
        $parcel_ids  = [];
        foreach (explode(',',$array) as  $id) {
            if($id !== ""):
            $parcel_ids [] = $id;
            endif;
        }
        $parcels    = Parcel::companywise()->whereIn('id',$parcel_ids)->orderBy('id')->get();
        $parcels    = $parcels->groupBy('status');
        return view('backend.merchant_panel.reports.parcel_reports_print',compact('parcels'));
    }
}
