<?php
namespace App\Repositories\PayoutSetup;
interface PayoutSetupInterface {
    public function update($payment_method,$request);
}
