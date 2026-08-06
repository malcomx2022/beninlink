<?php
namespace App\Repositories\DeliveryCharge;

interface DeliveryChargeInterface {

    public function all();
    public function allGet();
    public function getAllCharge();
    public function categories();
    public function get($id);
    public function filter($request);
    public function store($request);
    public function update($request);
    public function delete($id);
}
