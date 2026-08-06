<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\PaymentType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantOnlinePayment;
use App\Models\Backend\MerchantOnlinePaymentReceived;
use App\Repositories\Account\AccountInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Srmklive\PayPal\Services\ExpressCheckout;
class OnlinePaymentController extends Controller
{

    public function __construct(MerchantOnlinePayment $MOPModel,MerchantOnlinePaymentReceived $MOPRmodel,AccountInterface $accountRepo){
        $this->MOPModel = $MOPModel;
        $this->MOPRmodel = $MOPRmodel;
        $this->accountRepo = $accountRepo;
    }
    //start stripe payment gateway
    public function merchantPaymentReceived(){
        $Payments   = $this->MOPRmodel->where('merchant_id',Auth::user()->merchant->id)->orderByDesc('id')->paginate(10);
        return view('backend.merchant_panel.online_payment_received.index',compact('Payments'));
    }
    //payout list
    public function index(){
        $oPayments   = $this->MOPModel->where(['merchant_id'=>Auth::user()->merchant->id])->orderByDesc('id')->paginate(10);
        return view('backend.merchant_panel.onlinepayment.payment_list',compact('oPayments'));
    }
    public function stripe(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.stripe',compact('accounts'));
    }
    public function stripePost(Request $request){
        \Config([
            'services.stripe.secret'        => globalSettings('stripe_secret_key'),
        ]);

        $stripe = Stripe::charges()->create([
            'source' => $request->get('tokenId'),
            'currency' => 'BDT',
            'amount' => $request->get('amount')
        ]);
        $paymnet = DB::table('merchant_online_payments')
        ->updateOrInsert([
            'company_id'     => settings()->id,
            'payment_type'   => PaymentType::STRIPE,
            'amount'         => $request->get('amount'),
            'note'           => 'Payment',
            'transaction_id' => $request->get('tokenId'),
            'merchant_id'    => Auth::user()->merchant->id,
            'account_id'     => $request->account_id
        ]);

        $merchant                   = Merchant::find(Auth::user()->merchant->id);
        $merchant->current_balance  =($merchant->current_balance - $request->get('amount'));
        $merchant->save();
        $account                   = Account::find($request->account_id);
        $account->balance          = ($account->balance + $request->get('amount'));
        $account->save();
        return response()->json(['success'=>true],200);
    }

    //Start Paypal Payment Gateway
    public function paypalIndex(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.paypal',compact('accounts'));
    }
    public function paypalpayment(Request $request)
    {
        try {
            $payment = DB::table('merchant_online_payments')
            ->updateOrInsert([
                'company_id'     => settings()->id,
                'payment_type'   => PaymentType::PAYPAL,
                'amount'         => $request->get('amount'),
                'note'           => 'Payment',
                'transaction_id' => $request->get('orderID'),
                'merchant_id'    => Auth::user()->merchant->id,
                'account_id'     => $request->account_id
            ]);
            $merchant                   = Merchant::find(Auth::user()->merchant->id);
            $merchant->current_balance  =($merchant->current_balance - $request->get('amount'));
            $merchant->save();

            $account                   = Account::find($request->account_id);
            $account->balance          = ($account->balance + $request->get('amount'));
            $account->save();
            return response()->json(['success' => true, 'data'=>[] ],200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'data'=>[] ],500);
        }

    }

    public function sslcommerzIndex(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.sslcommerz',compact('accounts'));
    }

    public function aamarpayIndex(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.aamarpay',compact('accounts'));
    }
}
