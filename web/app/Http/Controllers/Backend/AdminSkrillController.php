<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantOnlinePaymentReceived;
use App\Repositories\Account\AccountInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Obydul\LaraSkrill\SkrillClient;
use Obydul\LaraSkrill\SkrillRequest;
use Redirect;

class AdminSkrillController extends Controller
{

    public function __construct(AccountInterface $accountRepo)
    {
        $this->skrilRequest = new SkrillRequest();
        $this->skrilRequest->return_url  = url('admin/payout/payment-completed');
        $this->skrilRequest->cancel_url  = url('admin/payout/payment-cancelled');
        $this->skrilRequest->logo_url    = settings()->logo_image;
        $this->accountRepo               = $accountRepo;
    }

    // Make Payment
    private $skrilRequest;
    public function index (Request $request){
        $merchant_id      = $request->get('merchant_id');
        $accounts         = $this->accountRepo->getAll();
        return view('backend.payout.skrill',compact('merchant_id','accounts'));
    }
    public function makePayment(Request $request)
    {
        
        $this->skrilRequest->pay_to_email = MerchantSearchSettings($request->merchant_id,'skrill_merchant_email');

        $merchant = Merchant::find($request->merchant_id);

        // create object instance of SkrillRequest
        $this->skrilRequest->transaction_id  = uniqid(); // generate transaction id
        $this->skrilRequest->amount          = $request->amount;
        $this->skrilRequest->currency        = 'USD';
        $this->skrilRequest->language        = 'EN';
        $this->skrilRequest->prepare_only    = '1';
        $this->skrilRequest->merchant_fields = settings()->name.','.settings()->email;
        $this->skrilRequest->site_name       = settings()->name;
        $this->skrilRequest->customer_email  = $merchant->user->email;

        // create object instance of SkrillClient
        $client = new SkrillClient($this->skrilRequest);
 
        $sid    = $client->generateSID(); //return SESSION ID
        // handle error
        $jsonSID = json_decode($sid);
        
        if ($jsonSID != null && $jsonSID->code == "BAD_REQUEST"):
            Toastr::error($jsonSID->message,'Error');
            return redirect()->back(); 
        endif;
        // do the payment
        $redirectUrl = $client->paymentRedirectUrl($sid); //return redirect url
        if ($jsonSID != null && $jsonSID->code != "BAD_REQUEST"){
            $this->skrilRequest->return_url  =  $this->storePayment($request,$this->skrilRequest->transaction_id) ;
        }
        return Redirect::to($redirectUrl); // redirect user to Skrill payment page
    }


    private function storePayment($request,$tid=''){
        $paymnet = DB::table('merchant_online_payment_receiveds')
        ->updateOrInsert([
            'payment_type'   => PaymentType::SKRILL,
            'amount'         => $request->get('amount') * settings()->excenseRate->exchange_rate,
            'note'           => 'Payment',
            'transaction_id' => $tid,
            'account_id' => $request->get('account_id'),
            'merchant_id'    => $request->merchant_id,
            'created_at'     => Date('Y-m-d H:i:s'),
            'updated_at'     => Date('Y-m-d H:i:s'),
        ]);
        $merchant                   = Merchant::find($request->merchant_id);
        $amounts                    =( $request->get('amount') * settings()->excenseRate->exchange_rate);
        $merchant->current_balance  =($merchant->current_balance -   $amounts);
        $merchant->save();
        $account = Account::find($request->account_id);
        $account->balance   = ($account->balance - $amounts);
        $account->save();
        return url('/admin/payout/payment-completed');
    }


    public function ipn(Request $request)
    {
        // skrill data - get more fields from Skrill Quick Checkout Integration Guide 7.9 (page 23)
        $transaction_id    = $request->transaction_id;
        $mb_transaction_id = $request->mb_transaction_id;
        $invoice_id        = $request->invoice_id; // custom field
        $order_from        = $request->order_from; // custom field
        $customer_email    = $request->customer_email; // custom field
        $biller_email      = $request->pay_from_email;
        $customer_id       = $request->customer_id;
        $amount            = ($request->amount * settings()->excenseRate->exchange_rate);
        $currency          = $request->currency;
        $status            = $request->status;

        // status message
        if ($status == '-2') {
            $status_message = 'Failed';
        } else if ($status == '2') {
            $status_message = 'Processed';
        } else if ($status == '0') {
            $status_message = 'Pending';
        } else if ($status == '-1') {
            $status_message = 'Cancelled';
        }

        // now store data to database
        $skrill_ipn                  = new MerchantOnlinePaymentReceived();
        $skrill_ipn->company_id      = settings()->id;
        $skrill_ipn->transaction_id  = $transaction_id;
        $skrill_ipn->invoice_id      = $invoice_id;
        $skrill_ipn->order_from      = $order_from;
        $skrill_ipn->customer_email  = $customer_email;
        $skrill_ipn->biller_email    = $biller_email;
        $skrill_ipn->customer_id     = $customer_id;
        $skrill_ipn->amount          = $amount;
        $skrill_ipn->currency        = $currency;
        $skrill_ipn->status          = $status_message;
        $skrill_ipn->created_at      = Date('Y-m-d H:i:s');
        $skrill_ipn->updated_at      = Date('Y-m-d H:i:s');
        $skrill_ipn->save();
    }
    public function paymentCompleted(){
        Toastr::success('Payment successfully completed.',__('message.success'));
        return redirect()->route('payout.index');
    }
    public function  PaymentCancelled(){
       Toastr::error(__('parcel.error_msg'),__('message.error'));
        return redirect()->route('payout.index');
    }
}
