<?php

namespace Database\Seeders;

use App\Models\MerchantShops;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MerchantshopsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
            for ($i=1; $i <6 ; $i++) {
                if($i == 1){
                    $shop=new MerchantShops();
                    $shop->merchant_id=1;
                    $shop->name='Shop '.$i;
                    $shop->contact_no='+88013000000';
                    $shop->address='Wemaxdevs,Dhaka';
                    $shop->status=1;
                    $shop->default_shop=1;
                    $shop->save();
                }else {
                    $shop=new MerchantShops();
                    $shop->merchant_id=1;
                    $shop->name='Shop '.$i;
                    $shop->contact_no='+88013000000';
                    $shop->address='Wemaxdevs,Dhaka';
                    $shop->status=1;
                    $shop->default_shop=0;
                    $shop->save();
                }

            }
    }
}
