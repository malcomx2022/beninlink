<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Backend\DeliveryMan;

class DeliveryManSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        $deliveryUser                           = new User();
        $deliveryUser->name                     = "Delivery Man";
        $deliveryUser->company_id               = 2;
        $deliveryUser->mobile                   = "01912938004";
        $deliveryUser->email                    = "deliveryman@wemaxit.com";
        $deliveryUser->address                  = "Mirpur-2,Dhaka";
        $deliveryUser->hub_id                   = 1;
        $deliveryUser->password                 = Hash::make('12345678');
        $deliveryUser->user_type                = UserType::DELIVERYMAN;
        $deliveryUser->salary                   = 7000;
        $deliveryUser->image_id                 = 3;
        $deliveryUser->unique_id                = 20241;
        $deliveryUser->save();

        $deliveryMan                             = new DeliveryMan();
        $deliveryMan->company_id                = 2;
        $deliveryMan->user_id                    = $deliveryUser->id;
        $deliveryMan->status                     = Status::ACTIVE;
        $deliveryMan->delivery_charge           = 30;
        $deliveryMan->pickup_charge             = 20;
        $deliveryMan->return_charge             = 10;
        $deliveryMan->current_balance           = 00;
        $deliveryMan->opening_balance           = 00;
        $deliveryMan->driving_license_image_id  = 1;
        $deliveryMan->save();
    }



}
