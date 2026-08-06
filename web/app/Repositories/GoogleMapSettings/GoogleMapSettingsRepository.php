<?php
namespace App\Repositories\GoogleMapSettings;
use App\Models\Backend\GoogleMapSetting;


class GoogleMapSettingsRepository implements GoogleMapSettingsInterface {

    public function all(){
        return GoogleMapSetting::companywise()->first();
    }

    public function update($request){
        $map_key = str_replace(' ', '', $request->map_key);
        $settings                = GoogleMapSetting::companywise()->first();
        if($settings) {
            $settings->map_key   = $map_key;
            $settings->save();
        }else {
            $settings               = new GoogleMapSetting;
            $settings->company_id   = settings()->id;
            $settings->map_key      = $map_key;
            $settings->save();
        }

        return $settings;
    }

}
