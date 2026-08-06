<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AccountTransactionController extends Controller
{

    public function index(){
        $accounts = MerchantPayment::where('merchant_id',Auth::user()->merchant->id)->get();
        $transactions = Payment::where('merchant_id',Auth::user()->merchant->id)->orderByDesc('id')->paginate(10);
        $request = [];
        return view('backend.merchant_panel.account_transaction.index',compact('accounts','transactions','request'));
    }

    public function filter(Request $request){
        $id = Auth::user()->merchant->id;
        if($request->date && $request->type == null && $request->account == null) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->paginate(10);
        }

        else if($request->type && $request->date == null && $request->account == null){
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->where('status', $request->type)->paginate(10);
        }

        else if($request->account && $request->type == null && $request->date == null){
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->where('merchant_account',$request->account)->paginate(10);
        }

        else if($request->date && $request->type && $request->account == null) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->where('status',$request->type)->paginate(10);
        }

        else if($request->date == null && $request->type && $request->account) {
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->where('status',$request->type)->where('merchant_account',$request->account)->paginate(10);
        }
        else if($request->date && $request->type == null && $request->account) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->where('merchant_account',$request->account)->paginate(10);
        }
        else if($request->date && $request->type && $request->account) {
            $date = explode('To', $request->date);

            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->where('status',$request->type)->where('merchant_account',$request->account)->paginate(10);
        }
        else{
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->paginate(10);
        }
        $accounts = MerchantPayment::where('merchant_id',$id)->get();
        return view('backend.merchant_panel.account_transaction.index',compact('accounts','transactions','request'));

    }
}
