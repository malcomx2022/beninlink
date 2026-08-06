<?php
namespace App\Repositories\MerchantProfile;

interface MerchantProfileInterface {

    public function get($id);
    public function update($id, $request);
    public function updatePassword($id, $request);
    public function file($image_id, $data);
}
