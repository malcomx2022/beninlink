<?php

namespace Database\Seeders;

use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\MerchantDeliveryCharge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Backend\Merchant;
use App\Models\User;
use App\Enums\Status;
use App\Enums\UserType;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $merchantUser                  = new User();
        $merchantUser->company_id      = 2;
        $merchantUser->name            = "Merchant";
        $merchantUser->mobile          = "01912938003";
        $merchantUser->email           = "merchant@wemaxdevs.com";
        $merchantUser->address         = "Mirpur-2,Dhaka";
        $merchantUser->password        = Hash::make('12345678');
        $merchantUser->user_type       = UserType::MERCHANT;
        $merchantUser->hub_id          = 4;
        $merchantUser->image_id        = 2;
        $merchantUser->unique_id       = 2024;
        $merchantUser->save();

        $merchant                      = new Merchant();
        $merchant->user_id             = $merchantUser->id;
        $merchant->business_name       = "WemaxDevs";
        $merchant->company_id          = 2;
        $merchant->merchant_unique_id  = 2024;
        $merchant->current_balance     = 00;
        $merchant->opening_balance     = 00;
        // $merchant->vat                 = 10;
        $merchant->cod_charges         = array(
            'inside_city'    => "1",
            'sub_city'       => "2",
            'outside_city'   => "3",
        );
        $merchant->nid_id              = 4;
        $merchant->trade_license       = 5;
        $merchant->address             = "Dhaka";
        $merchant->save();

        $deliveryCharges =  DeliveryCharge::with('category')->orderBy('position')->get();

        if(!blank($deliveryCharges)){
            foreach ($deliveryCharges as $delivery){
                $deliveryCharge                      = new MerchantDeliveryCharge();
                $deliveryCharge->merchant_id         = $merchant->id;
                $deliveryCharge->delivery_charge_id  = $delivery->id;
                $deliveryCharge->category_id         = $delivery->category_id;
                $deliveryCharge->weight              = $delivery->weight;
                $deliveryCharge->same_day            = $delivery->same_day;
                $deliveryCharge->next_day            = $delivery->next_day;
                $deliveryCharge->sub_city            = $delivery->sub_city;
                $deliveryCharge->outside_city        = $delivery->outside_city;
                $deliveryCharge->status              = Status::ACTIVE;
                $deliveryCharge->save();
            }
        }
    }
}
