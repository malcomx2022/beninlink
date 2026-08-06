<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SalaryGenerateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
            $users=User::whereNot('user_type',UserType::MERCHANT)->get();

            foreach ($users as  $user) {
                $salary = new SalaryGenerate();
                $salary->user_id  = $user->id;
                $salary->month    = date('Y-m');
                $salary->amount   = $user->salary;
                $salary->status   = 0;
                $salary->save();
            }
    }
}
