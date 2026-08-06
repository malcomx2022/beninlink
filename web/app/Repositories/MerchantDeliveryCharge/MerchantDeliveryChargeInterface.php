<?php
namespace App\Repositories\MerchantDeliveryCharge;

interface MerchantDeliveryChargeInterface {

    public function all($id);
    public function getAll($id);
    public function get($merchant_id,$id);
    public function store($request,$merchant_id);
    public function update($request,$id,$merchant_id);
    public function delete($id,$merchant_id);
    public function delivery_charges_get();
}
