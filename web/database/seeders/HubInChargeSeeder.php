<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\HubInCharge;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Backend\DeliveryMan;

class HubInChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $inCharge                             = new HubInCharge();
        $inCharge->user_id                    = 2;
        $inCharge->hub_id                     = 1;
        $inCharge->status                     = Status::ACTIVE;
        $inCharge->save();
    }
}
