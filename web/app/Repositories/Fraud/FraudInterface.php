<?php
namespace App\Repositories\Fraud;

interface FraudInterface {

    public function all();
    public function get($id);
    public function store($request);
    public function update($id, $request);
    public function delete($id);
}
