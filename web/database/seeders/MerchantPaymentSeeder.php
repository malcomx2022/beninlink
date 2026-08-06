<?php

namespace Database\Seeders;

use App\Models\MerchantPayment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MerchantPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $payment=new MerchantPayment();
        $payment->merchant_id    =1;
        $payment->payment_method ='bank';
        $payment->bank_name      = 'NRB Commercial Bank Ltd.';
        $payment->holder_name    = 'Marchant';
        $payment->account_no     = 123456;
        $payment->branch_name    = 'Dhaka branch';
        $payment->routing_no     = 123456;
        $payment->status         = 1;

        $payment->save();

        $company=['Bkash','Nagad','Rocket'];
         foreach ( $company as $key => $value) {
            $payments=new MerchantPayment();
            $payments->merchant_id    =1;
            $payments->payment_method ='mobile';
            $payments->holder_name    = 'Marchant';
             $payments->mobile_company = $value;
             $payments->mobile_no      = '01300000000';
             $payments->account_type   = 'Personal';
             $payments->status         = 1;
             $payments->save();
         }
    }
}
