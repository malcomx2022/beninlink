<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\GeneralSettings; 
use App\Models\Backend\Upload;

class GeneralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //main
        $user           = new Upload();
        $user->original = "uploads/users/user8.png";
        $user->save(); 
        $user           = new Upload();
        $user->original = "uploads/users/user9.png";
        $user->save(); 

        $row               = new GeneralSettings();
        $row->name         = "We Courier";
        $row->phone        = "20022002";
        $row->email        = "info@wecourier.com";
        $row->address      = "Mirpur 10, Dhaka, Bangladesh";
        $row->currency     = "$";
        $row->copyright    = "Copyright © All rights reserved. Development by WemaxDevs.";
        $row->logo         = 8;
        $row->favicon      = 9;
        $row->par_track_prefix     = 'we';
        $row->invoice_prefix       = 'we';
        $row->current_version      = '1';
        $row->primary_color        = '#7e0095';
        $row->text_color           = '#ffffff';
        $row->save();


        //company
 
        $row               = new GeneralSettings();
        $row->name         = "Company";
        $row->phone        = "20022002";
        $row->email        = "info@company.com";
        $row->address      = "Mirpur 10, Dhaka, Bangladesh";
        $row->currency     = "$";
        $row->copyright    = "Copyright © All rights reserved. Development by Company Name.";
        $row->logo         = 8;
        $row->favicon      = 9;
        $row->par_track_prefix     = 'co';
        $row->invoice_prefix       = 'co';
        $row->current_version      = '1';
        $row->primary_color        = '#7e0095';
        $row->text_color           = '#ffffff'; 
        $row->subscription_id      = 1;
        $row->plan_id              = 1;
        $row->save();
 
    }
}
