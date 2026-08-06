<?php
namespace App\Repositories\MerchantPanel\PaymentRequest;

interface PaymentRequestInterface {
    public function all();
    public function get($id);
    public function store($request);
    public function edit($id);
    public function update($request);
    public function delete($id);
}
