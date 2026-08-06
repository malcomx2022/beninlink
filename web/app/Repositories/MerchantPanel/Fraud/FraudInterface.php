<?php
namespace App\Repositories\MerchantPanel\Fraud;

interface FraudInterface {

    public function all();
    public function filter();
    public function get($id);
    public function store($request);
    public function check($request);
    public function update($id, $request);
    public function delete($id);
}
