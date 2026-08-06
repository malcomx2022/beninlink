<?php
namespace App\Repositories\MerchantPanel\MerchantParcel;

interface MerchantParcelInterface {

    public function all($merchant_id);
    public function parcelAll($merchant_id);
    public function parcelBank($merchant_id);
    public function filter($merchant_id,$request);
    public function parcelEvents($id);
    public function get($id);
    public function details($id);
    public function statusUpdate($id, $status_id);
    public function store($request,$merchant_id);
    public function duplicateStore($request,$merchant_id);
    public function update($id, $request,$merchant_id);
    public function delete($id,$merchant_id);
    public function getMerchant($id);
    public function getShop($id);
    public function getShops($id);
    public function deliveryCharges();
    public function deliveryTypes();
    public function deliveryCategories();
    public function packaging();
    public function parcelTrack($track_id);
    public function subscribe($request);
    public function parcelExport($request);
    public function statusWiseParcelList($status);

}
