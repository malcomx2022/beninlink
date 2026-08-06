<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Backend\Parcel;
use App\Enums\DeliveryTime;
use App\Models\Backend\ParcelEvent;
use Faker\Factory as Faker;
class ParcelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();


        for ($i=0; $i < 20; $i++) {

            $parcel                         = new Parcel();

            $parcel->merchant_id            = 1;
            $parcel->merchant_shop_id       = 1;
            $parcel->pickup_address         = $faker->unique()->address;
            $parcel->pickup_phone           = "01478523698";
            $parcel->customer_name          = $faker->name;
            $parcel->customer_phone         = "01478523655";
            $parcel->customer_address       = $faker->unique()->address;
            $parcel->invoice_no             = rand(100000,999999);
            $parcel->category_id            = 1;
            $parcel->weight                 = 5;
            $parcel->delivery_type_id       = 1;
            $parcel->packaging_id           = 3;
            $parcel->cash_collection        = 500;
            $parcel->selling_price          = 500;
            $parcel->liquid_fragile_amount  = 20;
            $parcel->packaging_amount       = 30;
            $parcel->delivery_charge        = 50;
            $parcel->cod_charge             = 1;
            $parcel->priority_type_id       = 2;
            $parcel->cod_amount             = 5;
            $parcel->vat                    = 10;
            $parcel->vat_amount             = 5.5;
            $parcel->total_delivery_amount  = 110.5;
            $parcel->current_payable        = 389.5;
            $parcel->tracking_id            = 'WE'.substr(strtotime(date('H:i:s')),1).'C1'. $i;
            $parcel->note                   = $faker->realText(20);

            // Pickup & Delivery Time
            if(date('H') < DeliveryTime::LAST_TIME){
                $parcel->pickup_date      = date('Y-m-d');
                $parcel->delivery_date    = date('Y-m-d');
            }
            else{
                $parcel->pickup_date      = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                $parcel->delivery_date    = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));;
            }

            $parcel->save();
        }


        foreach (trans('parcelStatusSeed') as $key => $value) {
            $event  = new ParcelEvent();
            $event->parcel_id       = 1;

            $event->delivery_man_id = 1;

            if($key == 2 || $key || 3 || $key == 4):
                $event->pickup_man_id   = 1;
            endif;
            if($key == 6 || $key || 19):
                $event->hub_id          = 1;
            endif;
            $event->transfer_delivery_man_id= 1;
            $event->note                    = $value;
            $event->parcel_status   =$key;
            $event->created_by      = 1;
            $event->save();
        }

        foreach (trans('parcelStatusSeed') as $key => $value) {
            $event  = new ParcelEvent();
            $event->parcel_id       = 2;
            $event->delivery_man_id = 1;
            $event->pickup_man_id   = 1;
            $event->hub_id          = 1;
            $event->transfer_delivery_man_id= 1;
            $event->note                    = $value;
            $event->parcel_status   = $key;
            $event->created_by      = 1;
            $event->save();
        }

        foreach (trans('parcelStatusSeed') as $key => $value) {
            $event  = new ParcelEvent();
            $event->parcel_id       = 3;
            $event->delivery_man_id = 1;
            $event->pickup_man_id   = 1;
            $event->hub_id          = 1;
            $event->transfer_delivery_man_id= 1;
            $event->note                    = $value;
            $event->parcel_status   = $key;
            $event->created_by      = 1;
            $event->save();
        }

    }
}
