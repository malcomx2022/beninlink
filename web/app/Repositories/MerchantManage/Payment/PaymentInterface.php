<?php
namespace App\Repositories\MerchantManage\Payment;

interface PaymentInterface {
    public function all();
    public function get($id);
    public function store($request);
    public function edit($id);
    public function update($request);
    public function delete($id);
    public function getSingleMerchantPayments($merchant_id);
    public function reject($id);
    public function cancelReject($id);
    public function processed($request);
    public function cancelProcess($id);
    public function filter($request);

}
