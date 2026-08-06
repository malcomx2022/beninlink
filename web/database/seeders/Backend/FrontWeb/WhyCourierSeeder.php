<?php

namespace Database\Seeders\Backend\FrontWeb;

use App\Models\Backend\FrontWeb\WhyCourier;
use App\Models\Backend\Upload;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WhyCourierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $lists = [
            'Timely Delivery '      => 'timly-delivery.png',
            'Limitless Pickup'      => 'limitless-pickup.png',
            'Cash on delivery (COD)' => 'cash-on-delivery.png',
            'Get Payment Any Time ' => 'payment.png',
            'Secure Handling '      => 'handling.png',
            'Live Tracking Update'  => 'live-tracking.png',
        ];

        $i = 0;
        foreach ($lists as  $key => $item) {
            $i++;
            $upload           = new Upload();
            $upload->original = "frontend/images/whycourier/" . $item;
            $upload->save();

            $whycourier             = new WhyCourier();
            $whycourier->company_id     = 1;
            $whycourier->title      = $key;
            $whycourier->image_id   = $upload->id;
            $whycourier->position   = $i;
            $whycourier->save();
        }
    }
}
