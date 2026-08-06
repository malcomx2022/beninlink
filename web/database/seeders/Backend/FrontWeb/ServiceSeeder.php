<?php

namespace Database\Seeders\Backend\FrontWeb;

use App\Models\Backend\FrontWeb\Service;
use App\Models\Backend\Upload;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $services = [
            'E-Commerce delivery'=>'truck.png',
            'Pick & Drop'=>'pick-drop.png',
            'Packageing' =>'packageing.png',
            'Warehousing'=>'warehouse.png',
        ];
        $i = 0;
        foreach ($services as  $key=>$serviceT) {
            $i++;

            $upload           = new Upload();
            $upload->original = "frontend/images/services/".$serviceT;
            $upload->save();

            $service              = new Service();
            $service->company_id  = 1;
            $service->title       = $key;
            $service->image_id    = $upload->id;
            $service->description = $faker->sentence(50);
            $service->position    = $i;
            $service->save();

        }
    }
}
