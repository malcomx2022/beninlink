<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\NotificationSettings;

class NotificationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $notification                  = new NotificationSettings();
        $notification->company_id      = 1;
        $notification->fcm_secret_key  = "";
        $notification->fcm_topic       = "";
        $notification->save();
    }
}
