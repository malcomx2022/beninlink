<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PaymentType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantOnlinePaymentReceived;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Account\AccountInterface;
class AdminBkashController extends Controller
{

    public function __construct(AccountInterface $accountRepo)
    {
        $this->accountRepo = $accountRepo;
    }

    public function index(Request $request){
        $merchant_id      = $request->get('merchant_id');
        $accounts         = $this->accountRepo->getAll();
        return view('backend.payout.bkash',compact('merchant_id', 'accounts'));
    }

    private function bKashTokenGenerator($client,$merchant_id)
    {
        if (MerchantSearchSettings($merchant_id,'bkash_test_mode') == Status::ACTIVE)
        {
            $base_url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized';
        }
        else{
            $base_url = 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized';
        }

        $request_data = [
            'app_key'    => MerchantSearchSettings($merchant_id,'bkash_app_id'),
            'app_secret' =>MerchantSearchSettings($merchant_id,'bkash_app_secret')
        ];
        $request_data_json = json_encode($request_data);

        $response = $client->request('POST', "$base_url/checkout/token/grant", [
            'body' => $request_data_json,
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'password' => MerchantSearchSettings($merchant_id,'bkash_password'),
                'username' => MerchantSearchSettings($merchant_id,'bkash_username'),
            ],
        ]);
        $decoded_data = json_decode($response->getBody()->getContents());
        return $decoded_data->id_token;
    }

    public function bkashRedirect(Request $request)
    {
        try {
            if (MerchantSearchSettings($request->merchant_id,'bkash_test_mode') == Status::ACTIVE)
            {
                $base_url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized';
            }
            else{
                $base_url = 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized';
            }

            $trx_id = uniqid();
            $amount = $request->amount;
            $client = new \GuzzleHttp\Client();

            $bkash_token = $this->bKashTokenGenerator($client,$request->merchant_id);

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
                    'callbackURL' => url("payout/bkash/execute?trx_id=$trx_id"),
                    'merchant_id' => $request->merchant_id,
                    'account_id'  => $request->account_id
                ];

                $requestbodyJson = json_encode($requestbody);
                $response = $client->request('POST', "$base_url/checkout/create", [
                    'body' => $requestbodyJson,
                    'headers' => [
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                        'Authorization' => $auth,
                        'X-APP-Key' => MerchantSearchSettings($request->merchant_id,'bkash_app_id'),
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
            $payment  = new MerchantOnlinePaymentReceived();
            $payment->company_id      = settings()->id;
            $payment->payment_type    = PaymentType::BKASH;
            $payment->amount          = $amount;
            $payment->note            = 'Payment';
            $payment->transaction_id  = $trx_id;
            $payment->merchant_id     = $request->merchant_id;
            $payment->account_id      = $request->account_id;
            $payment->created_at      = Date('Y-m-d H:i:s');
            $payment->updated_at      = Date('Y-m-d H:i:s');
            $payment->save();

            $merchant                   = Merchant::find($request->merchant_id);
            $merchant->current_balance  = ($merchant->current_balance - $request->get('amount'));
            $merchant->save();
            $account                   = Account::find($request->account_id);
            $account->balance          = ($account->balance - $request->get('amount'));
            $account->save();
            Toastr::success('Payment successfully completed.', __('message.success'));
            return redirect()->route('payout.index');
        } catch (\Exception $e) {
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->route('payout.index');
        }
    }
}
