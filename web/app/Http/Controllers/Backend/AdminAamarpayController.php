<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PaymentType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantOnlinePaymentReceived;
use App\Repositories\Account\AccountInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAamarpayController extends Controller
{

        public function __construct(AccountInterface $accountRepo)
        {
            $this->accountRepo = $accountRepo;
        }

        public function aamarpayIndex(Request $request){

            $merchant_id      = $request->get('merchant_id');
            $accounts         = $this->accountRepo->getAll();
            return view('backend.payout.aamarpay',compact('merchant_id','accounts'));
        }

        public function payment(Request $request){

            if(MerchantSearchSettings($request->merchant_id,'aamarpay_sendbox_mode')  == Status::ACTIVE):
                $url = 'https://sandbox.aamarpay.com/request.php'; // live url https://secure.aamarpay.com/request.php
            else:
                $url = 'https://secure.aamarpay.com/request.php'; // Sendbox url https://sandbox.aamarpay.com/request.php
            endif;

            if(MerchantSearchSettings($request->merchant_id,'aamarpay_store_id') == null):
                Toastr::error('Invalid Store id', __('message.error'));
                return redirect()->back()->withInput();
            endif;

            $merchant = Merchant::find($request->merchant_id);
            $fields = [
                        'store_id' => MerchantSearchSettings($request->merchant_id,'aamarpay_store_id'), //store id will be aamarpay,  contact integration@aamarpay.com for test/live id
                        'amount' => $request->amount, //transaction amount
                        'payment_type' => 'VISA', //no need to change
                        'currency' => 'BDT',  //currenct will be USD/BDT
                        'tran_id' => rand(1111111,9999999), //transaction id must be unique from your end
                        'cus_name' => $merchant->business_name,  //customer name
                        'cus_email' =>$merchant->user->email, //customer email address
                        'cus_add1' => $merchant->address,  //customer address
                        'cus_phone' => $merchant->user->mobile, //customer phone number
                        'success_url' => route('payout.aamarpay.payment.success'), //your success route
                        'fail_url'    => route('payout.aamarpay.payment.fail'), //your fail route
                        'cancel_url'  => route('payout.aamarpay.payment.fail'), //your cancel url
                        'signature_key' => MerchantSearchSettings($request->merchant_id,'aamarpay_signature_key')//signature key will be aamarpay
                    ]; //signature key will provided aamarpay, contact integration@aamarpay.com for test/live signature key

                $fields_string = http_build_query($fields);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $url_forward = str_replace('"', '', stripslashes(curl_exec($ch)));
            curl_close($ch);

            $payment                  = new MerchantOnlinePaymentReceived();
            $payment->company_id      = settings()->id;
            $payment->payment_type    = PaymentType::AAMARPAY;
            $payment->amount          = $request->amount;
            $payment->note            = 'Payment';
            $payment->transaction_id  = $fields['tran_id'];
            $payment->account_id      = $request->account_id;
            $payment->merchant_id     = $request->merchant_id;
            $payment->created_at      = Date('Y-m-d H:i:s');
            $payment->updated_at      = Date('Y-m-d H:i:s');
            $payment->save();

            $merchant                   = Merchant::find($request->merchant_id);
            $merchant->current_balance  = ($merchant->current_balance - $request->get('amount'));
            $merchant->save();

            $account                   = Account::find($request->account_id);
            $account->balance          = ($account->balance - $request->get('amount'));
            $account->save();
            $this->redirect_to_merchant($url_forward,$request);
    }

    function redirect_to_merchant($url,$request) {

        ?>
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head><script type="text/javascript">
            function closethisasap() { document.forms["redirectpost"].submit(); }
        </script></head>
        <body onLoad="closethisasap();">

            <form name="redirectpost" method="post" action="<?php if(MerchantSearchSettings($request->merchant_id,'aamarpay_sendbox_mode') == Status::ACTIVE): echo 'https://sandbox.aamarpay.com/'.$url; else: echo 'https://secure.aamarpay.com/'.$url; endif;
            ?>"></form>
            <!-- for live url https://secure.aamarpay.com -->
        </body>
        </html>
        <?php
        exit;
    }

    public function success(Request $request){
        Toastr::success('Payment successfully completed', __('message.success'));
        return redirect()->route('payout.index');
        // return $request;
    }

    public function fail(Request $request){
        Toastr::error(__('parcel.error_msg'),__('message.error'));
        return redirect()->route('payout.index');
        // return $request;
    }

}
