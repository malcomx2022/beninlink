<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PaymentType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;

class SslCommerzPaymentController extends Controller
{

    public function exampleEasyCheckout()
    {
        return view('backend.merchant_panel.sslecommerz.exampleEasycheckout');
    }

    public function exampleHostedCheckout()
    {
        return view('backend.merchant_panel.sslecommerz.exampleHosted');
    }

    public function payViaAjax(Request $request)
    {

        \config([
            'app.url'                                   => url('/'),
            'sslcommerz.apiCredentials.store_id'        => globalSettings('sslcommerz_store_id'),
            'sslcommerz.apiCredentials.store_password'  => globalSettings('sslcommerz_store_password')
        ]);

        if(globalSettings('sslcommerz_testmode') == Status::ACTIVE):
            \config([
                'sslcommerz.apiDomain'  => "https://sandbox.sslcommerz.com"
            ]);
        endif;
        # Here you have to receive all the order data to initate the payment.
        # Lets your oder trnsaction informations are saving in a table called "orders"
        # In orders table order uniq identity is "transaction_id","status" field contain status of the transaction, "amount" is the order amount to be paid and "currency" is for storing Site Currency which will be checked with paid currency.
        $requestData = json_decode($request['cart_json']);

        $post_data = array();
        $post_data['total_amount']     = $requestData->amount; # You cant not pay less than 10
        $post_data['currency']         = "BDT";
        $post_data['tran_id']          = uniqid(); // tran_id must be unique
        $post_data['merchant_id']      = Auth::user()->merchant->id; // tran_id must be unique
        # CUSTOMER INFORMATION
        $post_data['cus_name']  = Auth::user()->merchant->business_name;
        $post_data['cus_email'] = Auth::user()->email;
        $post_data['cus_add1']  = Auth::user()->address;
        $post_data['cus_add2']  = "";
        $post_data['cus_city']  = "";
        $post_data['cus_state'] = "";
        $post_data['cus_postcode'] = "";
        $post_data['cus_country'] = "Bangladesh";
        $post_data['cus_phone'] = '8801XXXXXXXXX';
        $post_data['cus_fax'] = "";

        # SHIPMENT INFORMATION
        $post_data['ship_name'] = "Store Test";
        $post_data['ship_add1'] = "Dhaka";
        $post_data['ship_add2'] = "Dhaka";
        $post_data['ship_city'] = "Dhaka";
        $post_data['ship_state'] = "Dhaka";
        $post_data['ship_postcode'] = "1000";
        $post_data['ship_phone'] = "";
        $post_data['ship_country'] = "Bangladesh";
        $post_data['shipping_method'] = "NO";
        $post_data['product_name'] = "Computer";
        $post_data['product_category'] = "Goods";
        $post_data['product_profile'] = "physical-goods";

        #Before  going to initiate the payment order status need to update as Pending.
          DB::table('merchant_online_payments')
            ->where('transaction_id', $post_data['tran_id'])
            ->updateOrInsert([
                'payment_type'   => PaymentType::SSL_COMMERZ,
                'amount'         => $requestData->amount,
                'note'           => 'Payment',
                'status'         => 'Pending',
                'merchant_id'    => $post_data['merchant_id'],
                'transaction_id' => $post_data['tran_id'],
                'account_id'     => $requestData->account_id
            ]);

        $sslc = new SslCommerzNotification();
        # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )

        $payment_options = $sslc->makePayment($post_data, 'checkout', 'json');
        $merchant                   = Merchant::find(Auth::user()->merchant->id);
        $merchant->current_balance  =($merchant->current_balance - $requestData->amount);
        $merchant->save();
        $account                   = Account::find($requestData->account_id);
        $account->balance          = ($account->balance + $requestData->amount);
        $account->save();
        if (!is_array($payment_options)) {
            print_r($payment_options);
            $payment_options = array();
        }
    }

    public function success(Request $request)
    {

        Toastr::success('Payment successfully completed.',__('message.success'));
        return redirect()->route('dashboard.index');
    }

    public function fail(Request $request)
    {
        Toastr::error(__('parcel.error_msg'),__('message.error'));
        return redirect()->route('dashboard.index');

    }

    public function cancel(Request $request)
    {

        Toastr::error('Payment cenceled.',__('message.error'));
        return redirect()->route('dashboard.index');
    }

    public function ipn(Request $request)
    {
        #Received all the payement information from the gateway
        if ($request->input('tran_id')) #Check transation id is posted or not.
        {
            $tran_id = $request->input('tran_id');
            #Check order status in order tabel against the transaction id or order id.
            $order_details = DB::table('merchant_online_payments')
                ->where('transaction_id', $tran_id)
                ->select('transaction_id', 'status', 'note',  'amount')->first();
            if ($order_details->status == 'Pending') {
                $sslc = new SslCommerzNotification();
                $validation = $sslc->orderValidate($request->all(), $tran_id, $order_details->amount, $order_details->currency);
                if ($validation == TRUE) {
                    /*
                    That means IPN worked. Here you need to update order status
                    in order table as Processing or Complete.
                    Here you can also sent sms or email for successful transaction to customer
                    */
                    $update_product = DB::table('merchant_online_payments')
                        ->where('transaction_id', $tran_id)
                        ->update(['status' => 'Processing']);

                    echo "Transaction is successfully Completed";
                } else {
                    /*
                    That means IPN worked, but Transation validation failed.
                    Here you need to update order status as Failed in order table.
                    */
                    $update_product = DB::table('merchant_online_payments')
                        ->where('transaction_id', $tran_id)
                        ->update(['status' => 'Failed']);

                    echo "validation Fail";
                }

            } else if ($order_details->status == 'Processing' || $order_details->status == 'Complete') {

                #That means Order status already updated. No need to udate database.

                echo "Transaction is already successfully Completed";
            } else {
                #That means something wrong happened. You can redirect customer to your product page.
                echo "Invalid Transaction";
            }
        } else {
            echo "Invalid Data";
        }
    }

}
