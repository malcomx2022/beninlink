<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\Deliverycategory;
use App\Enums\Status;

class DeliverycategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $deliverycategorys = [
            'KG',
            'Mobile',
            'Laptop',
            'Tabs',
            'Gaming Kybord',
            'Cosmetices',];
        $i = 0;
        for($n = 0; $n < sizeof($deliverycategorys); $n++)
        {
            $dep           = new Deliverycategory;
            $dep->title    = $deliverycategorys[$n];
            $dep->position = ++$i;
            $dep->status   = Status::ACTIVE;
            $dep->save();
        }
    }
}
