<?php

use App\Enums\AccountHeads;
use App\Enums\AccountType;
use App\Enums\ApprovalStatus;

return [

    'cod_charges' => [
        'inside_city'    => 1,
        'sub_city'       => 2,
        'outside_city'   => 3,
    ],


    'account_type' =>[
        'admin'       => AccountType::ADMIN,
        'user'        => AccountType::USER
    ],
    'delivery_types'=>[
        'same_day'    =>  'same_day',
        'next_day'    =>  'next_day',
        'sub_city'    =>  'sub_city',
        'outside_City'=>  'outside_City'
    ],

    'account_heads_type' =>[
        'income'      => AccountHeads::INCOME,
        'expense'     => AccountHeads::EXPENSE
    ],

    'approval_status' =>[
        'Reject'      => ApprovalStatus::REJECT   ,
        'Approved'    => ApprovalStatus::APPROVED ,
        'Pending'     => ApprovalStatus::PENDING  ,
        'Processed'   => ApprovalStatus::PROCESSED,
    ],

    'delivery_weights' => [1,2,3,4,5,6,7,8,9,10], // in kg
    'delivery_charges' => [
        'inside_city_same_day'    => [
            '1' => 40, // kg
            '2' => 100,
            '3' => 150,
            '4' => 200,
            '5' => 250,
            '6' => 300,
            '7' => 350,
            '8' => 400,
            '9' => 450,
            '10' => 500,
        ],
        'inside_city_next_day'    => [
            '1' => 30,
            '2' => 60,
            '3' => 90,
            '4' => 120,
            '5' => 150,
            '6' => 180,
            '7' => 210,
            '8' => 240,
            '9' => 270,
            '10' => 300,
        ],
        'sub_city' => [
            '1' => 40,
            '2' => 80,
            '3' => 120,
            '4' => 160,
            '5' => 200,
            '6' => 240,
            '7' => 280,
            '8' => 320,
            '9' => 360,
            '10' => 400,
        ],
        'outside_city' => [
            '1' => 60,
            '2' => 120,
            '3' => 180,
            '4' => 240,
            '5' => 250,
            '6' => 300,
            '7' => 350,
            '8' => 400,
            '9' => 450,
            '10' => 500,
        ],
    ],
    'api_key' => '123456rx-ecourier123456'

];
