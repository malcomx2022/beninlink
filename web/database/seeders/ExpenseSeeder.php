<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\Expense;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Account user
        $user                        = new Expense();
        $user->from                  = 1;
        $user->merchant_id           = 1;
        $user->parcel_id             = 1;
        $user->account_id            = 1;
        $user->amount                = 00;
        $user->date                  = "2022-05-17";
        $user->receipt               = null;
        $user->note                  = "Lorem ipsum dolor sit amet consectetur adipisicing elit. Nesciunt, provident!";
        $user->save();
    }
}
