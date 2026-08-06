<?php
namespace App\Repositories\SmsSetting;
use App\Enums\SmsSetup;
use App\Enums\Status;
use App\Repositories\SmsSetting\SmsSettingInterface;
use App\Models\Backend\SmsSetting;


class SmsSettingRepository implements SmsSettingInterface {


    public function update($sms_method,$request){
        try {


            switch ($sms_method) {
                case SmsSetup::REVE:
                    $request['reve_status'] = $request->reve_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case SmsSetup::TWILIO:
                    $request['twilio_status']   = $request->twilio_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case SmsSetup::NEXMO:
                    $request['nexmo_status']   = $request->nexmo_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                default:
                    break;
            }

            $requestData = $request->except(['_method','_token','smsMethod']);
            foreach ($requestData as $key => $value) {
                $setting          = SmsSetting::companywise()->where('key',$key)->first();
                if($setting):
                    $setting->value   = $value;
                    $setting->save();
                else:
                    $setting             = new SmsSetting();
                    $setting->key        = $key;
                    $setting->company_id = settings()->id;
                    $setting->value      = $value;
                    $setting->save();
                endif;
            }
            return true;
        } catch (\Throwable $th) { 
            return false;
        }
    }


}
