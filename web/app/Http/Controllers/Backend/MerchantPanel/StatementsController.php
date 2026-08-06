<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StatementsController extends Controller
{

    public function index(){
        $statements = MerchantStatement::where('merchant_id',auth()->user()->merchant->id)->orderByDesc('id')->paginate(10);
        $request = [];
        return view('backend.merchant_panel.statements.index',compact('statements','request'));
    }

    public function filter(Request $request){
        $id         = auth()->user()->merchant->id;
        $parcelID   = Parcel::where('tracking_id',$request->parcel_tracking_id)->first();
        $statements = MerchantStatement::where('merchant_id',$id)->orderByDesc('id')->where(function( $query ) use ( $request, $parcelID ) {
            if($request->date) {
                $date = explode('To', $request->date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('created_at', [$from, $to]);
                }
            }
            if($request->type) {
                $query->where('type',$request->type);
            }
            if(!blank($parcelID)) {
                $query->where(['parcel_id' => $parcelID->id]);
            }
            if($request->parcel_tracking_id && blank($parcelID)){
                $query->where(['parcel_id' => 0]);
            }
        })->paginate(10);
        return view('backend.merchant_panel.statements.index',compact('statements','request'));
    }
}
