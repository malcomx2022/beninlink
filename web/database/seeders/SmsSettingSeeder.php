<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Backend\SmsSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SmsSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        //SMS settings
        //REVE SMS
        SmsSetting::create(['company_id'=>1,'key' => 'reve_api_key',      'value' => 'api key']);
        SmsSetting::create(['company_id'=>1,'key' => 'reve_secret_key',   'value' => 'secret key']);
        SmsSetting::create(['company_id'=>1,'key' => 'reve_api_url',      'value' => 'api url']);
        SmsSetting::create(['company_id'=>1,'key' => 'reve_username',     'value' => '']);
        SmsSetting::create(['company_id'=>1,'key' => 'reve_user_password', 'value' => '']);
        SmsSetting::create(['company_id'=>1,'key' => 'reve_status',        'value' => Status::INACTIVE]);
        //Twilio SMS
        SmsSetting::create(['company_id'=>1,'key' => 'twilio_sid',    'value' => '']);
        SmsSetting::create(['company_id'=>1,'key' => 'twilio_token',  'value' => '']);
        SmsSetting::create(['company_id'=>1,'key' => 'twilio_from',   'value' => '']);
        SmsSetting::create(['company_id'=>1,'key' => 'twilio_status', 'value' => Status::INACTIVE]);

        //NEXMO SMS
        SmsSetting::create(['company_id'=>1,'key' => 'nexmo_key',        'value' => '']);
        SmsSetting::create(['company_id'=>1,'key' => 'nexmo_secret_key', 'value' => '']);
        SmsSetting::create(['company_id'=>1,'key' => 'nexmo_status',     'value' => Status::INACTIVE]);
 
    }
}
