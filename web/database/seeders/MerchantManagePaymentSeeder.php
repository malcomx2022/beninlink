<?php

namespace Database\Seeders;

use App\Models\Backend\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
class MerchantManagePaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i=0; $i <5 ; $i++) {
                $payment=new Payment();
                $payment->merchant_id     =1;
                $payment->amount          =$i.'000';
                $payment->merchant_account=1;
                $payment->transaction_id  =Str::random(8);
                $payment->from_account    =1;
                $payment->reference_file  =1;
                if($i > 2){
                    $payment->created_by  = 2;
                    $payment->status      = 2;
                }else{
                    $payment->created_by  = 1;
                    $payment->status      = 3;
                }
                $payment->save();
        }

    }
}
