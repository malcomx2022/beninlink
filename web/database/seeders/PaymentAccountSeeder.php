<?php

namespace Database\Seeders;

use App\Models\Backend\Merchantpanel\PaymentAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $payment=new PaymentAccount();
        $payment->merchant_id    = 1;
        $payment->payment_method ='bank';
        $payment->bank_name      = 'Ecobank Bénin';
        $payment->holder_name    = 'Marchand';
        $payment->account_no     = 123456;
        $payment->branch_name    = 'Agence Ganhi, Cotonou';
        $payment->routing_no     = 123456;
        $payment->status         = 1;

        $payment->save();

        $company=['MTN MoMo','Moov Money']; // S92 — les deux opérateurs servis par FedaPay
         foreach ( $company as $key => $value) {
            $payments=new PaymentAccount();
            $payments->merchant_id    = 1;
            $payments->payment_method ='mobile';
            $payments->holder_name    = 'Marchand';
             $payments->mobile_company = $value;
             $payments->mobile_no      = '01300000000';
             $payments->account_type   = 'Personal';
             $payments->status         = 1;
             $payments->save();
         }
    }
}
