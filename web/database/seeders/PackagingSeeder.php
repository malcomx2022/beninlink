<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Backend\Packaging;

class PackagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $packages=['Poly','Bubble Poly','Box','Box Poly'];
        $i=1;
        foreach ($packages as $value) { 
                  
            $packaging                           = new Packaging();
            $packaging->name                     = $value;
            $packaging->price                    = $i.'0';
            $packaging->position                 = $i;
            $packaging->status                   = 1;
            $packaging->save();
            $i++;
        }
    }
}
