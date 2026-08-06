<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantOnlinePaymentReceived;
use App\Models\User;
use App\Repositories\Account\AccountInterface;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantPayment\PaymentInterface;
use Brian2694\Toastr\Facades\Toastr;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Stripe\PaymentIntent;
use Stripe;
use Obydul\LaraSkrill\SkrillClient;
use Obydul\LaraSkrill\SkrillRequest;
use Redirect;
use App\Models\Backend\MerchantOnlinePayment;
class PayoutController extends Controller
{
    protected $MOPRModel,$merchantAccountRepo;
    public function __construct(MerchantOnlinePaymentReceived $MOPRModel,AccountInterface $accountRepo){
        $this->MOPRModel           = $MOPRModel;
        $this->accountRepo         = $accountRepo;

    }

    //start stripe payment gateway
    public function index(){
        $oPayments        = $this->MOPRModel->orderByDesc('id')->paginate(10);

        return view('backend.payout.payment_list',compact('oPayments'));
    }

    public function merchantPayout(Request $request){
        $merchant_id = $request->get('merchant_id');
        $oPayments   = $this->MOPRModel->where('merchant_id',$merchant_id)->orderByDesc('id')->paginate(10);
      return view('backend.payout.payment_list',compact('merchant_id','oPayments'));
    }
    public function stripe(Request $request){
        $accounts = $this->accountRepo->getAll();
        $merchant_id      = $request->get('merchant_id');
        return view('backend.payout.stripe',compact('accounts','merchant_id'));
    }

    public function stripePost(Request $request){

        \Config([
            'services.stripe.secret'        => MerchantSearchSettings($request->merchantId,'stripe_secret_key'),
        ]);

        $stripe = Stripe::charges()->create([
            'source' => $request->get('tokenId'),
            'currency' => 'BDT',
            'amount' => $request->get('amount')
        ]);

        $paymnet = DB::table('merchant_online_payment_receiveds')
        ->updateOrInsert([
            'company_id'     => settings()->id,
            'payment_type'   => PaymentType::STRIPE,
            'amount'         => $request->get('amount'),
            'note'           => 'Payment',
            'transaction_id' => $request->get('tokenId'),
            'account_id'    => $request->accountId,
            'merchant_id'    => $request->merchantId,
            'created_at'     => Date('Y-m-d H:i:s'),
            'updated_at'     => Date('Y-m-d H:i:s'),
        ]);

        $merchant                   = Merchant::find($request->merchantId);
        $merchant->current_balance  =($merchant->current_balance - $request->get('amount'));
        $merchant->save();
        $account = Account::find($request->accountId);
        $account->balance   = ($account->balance - $request->get('amount'));
        $account->save();

        return response()->json(['success'=>true],200);

    }

    public function razorpay(Request $request){
        $accounts = $this->accountRepo->getAll();
        $merchant_id      = $request->get('merchant_id');
        $merchant = User::find($merchant_id);
        $amount = null;
        return view('backend.payout.razorpay',compact('accounts','merchant_id','amount','merchant'));
    }

    public function razorpayPost(Request $request){

        try {
            $paymnet = DB::table('merchant_online_payment_receiveds')
                ->updateOrInsert([
                    'company_id'    => settings()->id,
                    'payment_type'  => PaymentType::RAZORPAY,
                    'amount'        => $request->get('amount'),
                    'note'          => 'Payment',
                    'transaction_id'=> $request->get('payment_id'),
                    'account_id'    => $request->get('account_id'),
                    'merchant_id'   => $request->get('merchantId'),
                    'created_at'    => Date('Y-m-d H:i:s'),
                    'updated_at'    => Date('Y-m-d H:i:s'),
                ]);

            $merchant = Merchant::find($request->merchantId);
            $merchant->current_balance = ($merchant->current_balance - $request->get('amount'));
            $merchant->save();
            $account = Account::find($request->accountId);
            $account->balance = ($account->balance - $request->get('amount'));
            $account->save();

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false], 200);
        }
    }

    //Start Paypal Payment Gateway ==================
        public function paypalIndex(Request $request){
            $merchant_id = $request->get('merchant_id');
            $accounts = $this->accountRepo->getAll();
            return view('backend.payout.paypal',compact('merchant_id','accounts'));
        }
        public function paypalpayment(Request $request)
        {
            try {

                $payment = DB::table('merchant_online_payment_receiveds')
                ->updateOrInsert([
                    'company_id'     => settings()->id,
                    'payment_type'   => PaymentType::PAYPAL,
                    'amount'         => $request->get('amount'),
                    'note'           => 'Payment',
                    'transaction_id' => $request->get('orderID'),
                    'account_id'     => $request->accountId,
                    'merchant_id'    => $request->merchantId,
                    'created_at'     => Date('Y-m-d H:i:s'),
                    'updated_at'     => Date('Y-m-d H:i:s'),
                ]);

                $merchant                   = Merchant::find($request->merchantId);
                $merchant->current_balance  =($merchant->current_balance - $request->get('amount'));
                $merchant->save();

                $account = Account::find($request->accountId);
                $account->balance   = ($account->balance - $request->get('amount'));
                $account->save();

                return response()->json(['success' => true, 'data'=>[] ],200);
            } catch (\Throwable $th) {
                return response()->json(['success' => false, 'data'=>[] ],500);
            }
        }

}
