<?php
namespace App\Repositories\MerchantPanel\Shops;

interface ShopsInterface {

    public function all($id);
    public function get($id);
    public function store($id, $request);
    public function update($id, $request);
    public function delete($id);
    public function getMerchant($id);
}
