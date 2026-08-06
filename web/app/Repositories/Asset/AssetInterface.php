<?php
namespace App\Repositories\Asset;

interface AssetInterface {

    public function all();
    public function get($id);
    public function store($request);
    public function update($request);
    public function delete($id);
    public function hubs();
    public function assetcategorys();

}
