<?php
namespace App\Repositories\SocialLoginSettings;

use App\Enums\Status;
use App\Models\Backend\Setting;
use App\Repositories\SocialLoginSettings\SocialLoginSettingsInterface;


class SocialLoginSettingsRepository implements SocialLoginSettingsInterface
{
    public function update($request,$social){
        try {

            if($social == 'google'):
                $onlyInput  = [
                    'google_client_id',
                    'google_client_secret',
                    'google_status'
                ];
                $request['google_status'] = $request->google_status == 'on'? Status::ACTIVE:Status::INACTIVE;

            elseif($social == 'facebook'):
                $onlyInput  = [
                    'facebook_client_id',
                    'facebook_client_secret',
                    'facebook_status'
                ];
                $request['facebook_status'] = $request->facebook_status == 'on'? Status::ACTIVE:Status::INACTIVE;
            endif;

            foreach ($request->only($onlyInput) as $key => $value) {
                $setting               = Setting::companywise()->where('key',$key)->first(); 
                $setting->value        = $value;
                $setting->save();
            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
