<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PaymentType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantOnlinePayment;
use App\Repositories\Account\AccountInterface;
use App\Traits\PaymentTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BkashController extends Controller
{

    use PaymentTrait;

    public function __construct(AccountInterface $accountRepo){
        $this->accountRepo = $accountRepo;
    }
    public function index(){
        $accounts         = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.bkash',compact('accounts'));
    }

    public function bkashRedirect(Request $request)
    {

        try {
            if (globalSettings('bkash_test_mode') == Status::ACTIVE)
            {
                $base_url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized';
            }
            else{
                $base_url = 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized';
            }

            $trx_id = uniqid();
            $amount = $request->amount;
            $client = new \GuzzleHttp\Client();

            $bkash_token = $this->bKashTokenGenerator($client);

            if ($bkash_token) {
                $auth = $bkash_token;
                session()->put('id_token', $auth);
                $requestbody = [
                    'mode' => '0011',
                    'amount' => round($amount, 2),
                    'currency' => 'BDT',
                    'intent' => 'sale',
                    'payerReference' => settings()->name,
                    'InvoiceNumber' => date('YmdHis'),
                    'callbackURL' => url("bkash/execute?trx_id=$trx_id"),
                    'account_id'  => $request->account_id
                ];


                $requestbodyJson = json_encode($requestbody);
                $response = $client->request('POST', "$base_url/checkout/create", [
                    'body' => $requestbodyJson,
                    'headers' => [
                        'accept'        => 'application/json',
                        'content-type'  => 'application/json',
                        'Authorization' => $auth,
                        'X-APP-Key'     => globalSettings('bkash_app_id'),
                    ],
                ]);

                $obj = json_decode($response->getBody()->getContents());
                return redirect($obj->bkashURL);

            }
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        } catch (\Exception $e) {
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        }
    }

    public function bkashExecute(Request $request)
    {
        try {
            $data      = $request->all();
            $trx_id    = $request->trx_id;
            $auth      = session()->get('id_token');
            $amount    = $request->amount;
            $db_amount = $amount;
            if (!$trx_id || $request->status != 'success' || !$auth) {
                Toastr::error(__('parcel.error_msg'), __('message.error'));
                return redirect()->route('online.payment.index');
            }
            $payment                  = new MerchantOnlinePayment();
            $payment->company_id      = settings()->id;
            $payment->payment_type    = PaymentType::BKASH;
            $payment->amount          = $amount;
            $payment->note            = 'Payment';
            $payment->transaction_id  = $trx_id;
            $payment->merchant_id     = Auth::user()->merchant->id;
            $payment->account_id      = $request->account_id;
            $payment->save();

            $merchant                   = Merchant::find(Auth::user()->merchant->id);
            $merchant->current_balance  =($merchant->current_balance - $request->amount);
            $merchant->save();

            $account                   = Account::find($request->account_id);
            $account->balance          = ($account->balance + $request->get('amount'));
            $account->save();
            Toastr::success('Payment successfully completed.', __('message.success'));
            return redirect()->route('online.payment.index');

        } catch (\Exception $e) {
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->route('online.payment.index');

        }
    }

}
