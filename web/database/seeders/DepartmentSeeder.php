<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $departments = [
            'General Management',
            'Marketing',
            'Operations',
            'Finance',
            'Sales',
            'Human Resource',
            'Purchase'];

        for($n = 0; $n < sizeof($departments); $n++)
        {
            $dep        = new Department;
            $dep->title = $departments[$n];
            $dep->company_id = 1;
            $dep->save();
        }
    }
}
