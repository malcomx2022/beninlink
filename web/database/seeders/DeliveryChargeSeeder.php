<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\DeliveryCharge;
use App\Enums\Status;

class DeliveryChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $delivery_charge               = new DeliveryCharge();
        $delivery_charge->company_id  = 2;
        $delivery_charge->category_id  = 1;
        $delivery_charge->weight       = 1;
        $delivery_charge->same_day     = 50;
        $delivery_charge->next_day     = 60;
        $delivery_charge->sub_city     = 70;
        $delivery_charge->outside_city = 80;
        $delivery_charge->position     = 1;
        $delivery_charge->status       = Status::ACTIVE;
        $delivery_charge->save();
        
        $delivery_charge               = new DeliveryCharge();
        $delivery_charge->company_id  = 2;
        $delivery_charge->category_id  = 1;
        $delivery_charge->weight       = 2;
        $delivery_charge->same_day     = 90;
        $delivery_charge->next_day     = 100;
        $delivery_charge->sub_city     = 110;
        $delivery_charge->outside_city = 120;
        $delivery_charge->position     = 2;
        $delivery_charge->status       = Status::ACTIVE;
        $delivery_charge->save();
        
        $delivery_charge               = new DeliveryCharge();
        $delivery_charge->company_id  = 2;
        $delivery_charge->category_id  = 1;
        $delivery_charge->weight       = 3;
        $delivery_charge->same_day     = 130;
        $delivery_charge->next_day     = 140;
        $delivery_charge->sub_city     = 150;
        $delivery_charge->outside_city = 160;
        $delivery_charge->position     = 3;
        $delivery_charge->status       = Status::ACTIVE;
        $delivery_charge->save();
        
        $delivery_charge               = new DeliveryCharge();
        $delivery_charge->company_id  = 2;
        $delivery_charge->category_id  = 1;
        $delivery_charge->weight       = 4;
        $delivery_charge->same_day     = 170;
        $delivery_charge->next_day     = 180;
        $delivery_charge->sub_city     = 190;
        $delivery_charge->outside_city = 200;
        $delivery_charge->position     = 4;
        $delivery_charge->status       = Status::ACTIVE;
        $delivery_charge->save();
        
        $delivery_charge               = new DeliveryCharge();
        $delivery_charge->company_id  = 2;
        $delivery_charge->category_id  = 1;
        $delivery_charge->weight       = 5;
        $delivery_charge->same_day     = 210;
        $delivery_charge->next_day     = 220;
        $delivery_charge->sub_city     = 230;
        $delivery_charge->outside_city = 240;
        $delivery_charge->position     = 5;
        $delivery_charge->status       = Status::ACTIVE;
        $delivery_charge->save();
        
        $delivery_charge               = new DeliveryCharge();
        $delivery_charge->company_id  = 2;
        $delivery_charge->category_id  = 1;
        $delivery_charge->weight       = 6;
        $delivery_charge->same_day     = 250;
        $delivery_charge->next_day     = 260;
        $delivery_charge->sub_city     = 270;
        $delivery_charge->outside_city = 280;
        $delivery_charge->position     = 6;
        $delivery_charge->status       = Status::ACTIVE;
        $delivery_charge->save();
        
        $delivery_charge               = new DeliveryCharge();
        $delivery_charge->company_id  = 2;
        $delivery_charge->category_id  = 1;
        $delivery_charge->weight       = 7;
        $delivery_charge->same_day     = 290;
        $delivery_charge->next_day     = 300;
        $delivery_charge->sub_city     = 310;
        $delivery_charge->outside_city = 320;
        $delivery_charge->position     = 7;
        $delivery_charge->status       = Status::ACTIVE;
        $delivery_charge->save();
        
        $delivery_charge               = new DeliveryCharge();
        $delivery_charge->company_id  = 2;
        $delivery_charge->category_id  = 1;
        $delivery_charge->weight       = 8;
        $delivery_charge->same_day     = 340;
        $delivery_charge->next_day     = 350;
        $delivery_charge->sub_city     = 360;
        $delivery_charge->outside_city = 370;
        $delivery_charge->position     = 8;
        $delivery_charge->status       = Status::ACTIVE;
        $delivery_charge->save();
        
        $delivery_charge               = new DeliveryCharge();
        $delivery_charge->company_id  = 2;
        $delivery_charge->category_id  = 1;
        $delivery_charge->weight       = 9;
        $delivery_charge->same_day     = 380;
        $delivery_charge->next_day     = 390;
        $delivery_charge->sub_city     = 400;
        $delivery_charge->outside_city = 410;
        $delivery_charge->position     = 9;
        $delivery_charge->status       = Status::ACTIVE;
        $delivery_charge->save();
        
        $delivery_charge               = new DeliveryCharge();
        $delivery_charge->company_id  = 2;
        $delivery_charge->category_id  = 1;
        $delivery_charge->weight       = 10;
        $delivery_charge->same_day     = 420;
        $delivery_charge->next_day     = 430;
        $delivery_charge->sub_city     = 440;
        $delivery_charge->outside_city = 450;
        $delivery_charge->position     = 10;
        $delivery_charge->status       = Status::ACTIVE;
        $delivery_charge->save();

    }
}
