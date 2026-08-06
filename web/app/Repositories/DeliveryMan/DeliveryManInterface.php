<?php
namespace App\Repositories\DeliveryMan;

interface DeliveryManInterface {

    public function all();
    public function hubs();
    public function get($id);
    public function filter($request);
    public function store($request);
    public function update($id, $request);
    public function delete($id);
    public function deliverymanEarn($type);
    public function totalCOD($type);
    public function paymentLogs();
    public function parcelPaymentLogs();
}
