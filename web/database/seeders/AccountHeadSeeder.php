<?php

namespace Database\Seeders;

use App\Enums\AccountHeads;
use App\Enums\Status;
use App\Models\Backend\AccountHead;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountHeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        //income
        $account_heads         = new AccountHead();
        $account_heads->type   = AccountHeads::INCOME;
        $account_heads->name   = 'Payment received from Merchant';
        $account_heads->status = Status::ACTIVE;
        $account_heads->save();

        $account_heads         = new AccountHead();
        $account_heads->type   = AccountHeads::INCOME;
        $account_heads->name   = 'Cash received from delivery man';
        $account_heads->status = Status::ACTIVE;
        $account_heads->save();

        $account_heads         = new AccountHead();
        $account_heads->type   = AccountHeads::INCOME;
        $account_heads->name   = 'Others';
        $account_heads->status = Status::ACTIVE;
        $account_heads->save();


        //expense
        $account_heads         = new AccountHead();
        $account_heads->type   = AccountHeads::EXPENSE;
        $account_heads->name   = 'Payment paid to merchant';
        $account_heads->status = Status::INACTIVE;
        $account_heads->save();

        $account_heads         = new AccountHead();
        $account_heads->type   = AccountHeads::EXPENSE;
        $account_heads->name   = 'Commission paid to delivery man';
        $account_heads->status = Status::ACTIVE;
        $account_heads->save();

        $account_heads         = new AccountHead();
        $account_heads->type   = AccountHeads::EXPENSE;
        $account_heads->name   = 'Others';
        $account_heads->status = Status::ACTIVE;
        $account_heads->save();

        $account_heads         = new AccountHead();
        $account_heads->type   = AccountHeads::INCOME;
        $account_heads->name   = 'Payment receive from hub';
        $account_heads->status = Status::ACTIVE;
        $account_heads->save();


    }
}
