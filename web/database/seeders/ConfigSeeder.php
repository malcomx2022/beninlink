<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Config;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $config = [
        'fragile_liquid_status'         => Status::ACTIVE,
        'fragile_liquid_charge'         => 20,
        'same_day'                      => 1,
        'next_day'                      => 1,
        'sub_city'                      => 1,
        'outside_City'                  => 1
        ];
        foreach ($config as $key => $value) {
             $confg           = new Config();
             $confg->company_id   = 2;
             $confg->key      = $key;
             $confg->value    = $value;
             $confg->save();
        }
    }
}
