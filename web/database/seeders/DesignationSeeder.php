<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\Designation;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $designations = [
            'Chief Executive Officer (CEO)',
            'Chief Operating Officer (COO)',
            'Chief Financial Officer (CFO)',
            'Chief Technology Officer (CTO)',
            'Chief Legal Officer (CLO)',
            'Chief Marketing Officer (CMO)'];

        for($n = 0; $n < sizeof($designations); $n++)
        {
            $desig             = new Designation;
            $desig->company_id = 1;
            $desig->title      = $designations[$n];
            $desig->save();
        }
    }
}
