<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $account                       = new Account();
        $account->type                 = 1;
        $account->user_id              = 2;
        $account->gateway              = 2;
        $account->account_holder_name  = "User";
        $account->account_no           = 123654789;
        $account->bank                 = 1;
        $account->branch_name          = "Dhaka";
        $account->balance              = 00;
        $account->opening_balance      = 00;
        $account->save();

        $account                       = new Account();
        $account->type                 = 1;
        $account->user_id              = 3;
        $account->gateway              = 2;
        $account->account_holder_name  = "User2";
        $account->account_no           = 987456321;
        $account->bank                 = 2;
        $account->branch_name          = "Mirpur";
        $account->balance              = 00;
        $account->opening_balance      = 00;
        $account->save();
    }
}
