<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\Hub;

class HubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hubs = [
            [
                'name'            =>'Mirpur-10',
                'phone'           =>'01000000001',
                'address'         =>'Dhaka, Bangladesh',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Uttara',
                'phone'           =>'01000000002',
                'address'         =>'Dhaka, Bangladesh',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Dhanmundi',
                'phone'           =>'01000000003',
                'address'         =>'Dhaka, Bangladesh',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Old Dhaka',
                'phone'           =>'01000000004',
                'address'         =>'Dhaka, Bangladesh',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Jatrabari',
                'phone'           =>'01000000005',
                'address'         =>'Dhaka, Bangladesh',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Badda',
                'phone'           =>'01000000006',
                'address'         =>'Dhaka, Bangladesh',
                'current_balance' => '00'
            ],
        ];

        for($n = 0; $n < sizeof($hubs); $n++)
        {
            $hub                  = new Hub();
            $hub->company_id      = 2;
            $hub->name            = $hubs[$n]['name'];
            $hub->phone           = $hubs[$n]['phone'];
            $hub->address         = $hubs[$n]['address'];
            $hub->current_balance = $hubs[$n]['current_balance'];
            $hub->save();
        }
    }
}
