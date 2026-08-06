<?php
namespace App\Repositories\PayoutSetup;

use App\Enums\PayoutSetup;
use App\Enums\Status;
use App\Models\Backend\Setting;
use App\Repositories\PayoutSetup\PayoutSetupInterface;
class PayoutSetupRepository implements PayoutSetupInterface{

    public function update($payment_method,$request){
        try {


            switch ($payment_method) {
                case PayoutSetup::STRIPE:
                    $request['stripe_status'] = $request->stripe_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::SSL_COMMERZ:
                    $request['sslcommerz_testmode'] = $request->sslcommerz_testmode == 'on'? Status::ACTIVE:Status::INACTIVE;
                    $request['sslcommerz_status']   = $request->sslcommerz_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::PAYPAL:
                    $request['paypal_status']       = $request->paypal_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::SKRILL:
                    $request['skrill_status']      = $request->skrill_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::BKASH:
                    $request['bkash_test_mode']   = $request->bkash_test_mode == 'on'? Status::ACTIVE:Status::INACTIVE;
                    $request['bkash_status']      = $request->bkash_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::AAMARPAY:
                    $request['aamarpay_sendbox_mode']= $request->aamarpay_sendbox_mode == 'on'? Status::ACTIVE:Status::INACTIVE;
                    $request['aamarpay_status']      = $request->aamarpay_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::RAZORPAY:
                    $request['razorpay_status']      = $request->razorpay_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                default:

                    break;
            }

            $requestData = $request->except(['_method','_token']);
            foreach ($requestData as $key => $value) {
                $setting          = Setting::where('key',$key)->first();
                if($setting){
                    $setting->value   = $value;
                    $setting->save();
                }else {
                    Setting::create(['company_id'=>settings()->id,'key' => $key,'value' => $value]);
                }

            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
