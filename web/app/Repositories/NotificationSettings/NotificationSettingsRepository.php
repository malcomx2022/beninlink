<?php
namespace App\Repositories\NotificationSettings;
use App\Libraries\MyString;
use App\Models\Backend\NotificationSettings;
use App\Models\Backend\Upload;
use App\Repositories\NotificationSettings\NotificationSettingsInterface;
use Illuminate\Support\Facades\Artisan;

class NotificationSettingsRepository implements NotificationSettingsInterface{

    public function all(){
        return NotificationSettings::companywise()->first();
    }

    public function update($request){
        $fcm_secret_key = str_replace(' ', '', $request->fcm_secret_key);
        $fcm_topic      = str_replace(' ', '_', $request->fcm_topic);
        $notification                   = NotificationSettings::companywise()->first();
        $notification->fcm_secret_key   = $fcm_secret_key;
        $notification->fcm_topic        = $fcm_topic;
        $notification->save();
        // setEnv('FCM_SECRET_KEY', $fcm_secret_key);
        // setEnv('FCM_TOPIC', $fcm_topic);
        // Artisan::call('optimize:clear');
        return $notification;

    }

}
