<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Backend\SmsSendSetting;
use Illuminate\Database\Seeder;

class SmsSendSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (trans('SmsSendStatus') as $key => $status) {
            $smsSendSetting                       = new SmsSendSetting();
            $smsSendSetting->company_id           = 2;
            $smsSendSetting->sms_send_status      = $key;
            $smsSendSetting->status               = Status::INACTIVE;
            $smsSendSetting->save();
        }

    }
}
