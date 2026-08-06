<?php

namespace Database\Seeders\Backend\FrontWeb;

use App\Models\Backend\FrontWeb\Partner;
use App\Models\Backend\Upload;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker  = Faker::create('en_US');
        $data = [
            '1.png',
            'atom.png',
            'digg.png',
            '2.png',
            'huawei.png',
            'ups.png',
            '1.png',
            'atom.png',
            'digg.png',
            '2.png',
            'huawei.png',
            'ups.png'
        ];
        foreach ($data as $key => $value) {
            $upload           = new Upload();
            $upload->original = "frontend/images/partner/" . $value;
            $upload->save();
            $partner           = new Partner();
            $partner->company_id     = 1;
            $partner->name     = $faker->unique()->company();
            $partner->image_id = $upload->id;
            $partner->link     = '#';
            $partner->position = ($key + 1);
            $partner->save();
        }
    }
}
