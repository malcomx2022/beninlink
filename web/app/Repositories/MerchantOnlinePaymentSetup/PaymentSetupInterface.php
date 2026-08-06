<?php
namespace App\Repositories\MerchantOnlinePaymentSetup;

interface PaymentSetupInterface {
    public function update($payment_method,$request);
}
