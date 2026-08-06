<?php
namespace App\Repositories\MerchantPayment;

interface PaymentInterface {

    public function all();
    public function get($id);
    public function edit($id);
    public function bankstore($request);
    public function mobilestore($request);
    public function update($id,$request);
    public function bankUpdate($request);
    public function mobileUpdate($request);
    public function delete($id);

}
