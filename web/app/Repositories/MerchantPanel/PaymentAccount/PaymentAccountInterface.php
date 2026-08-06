<?php
namespace App\Repositories\MerchantPanel\PaymentAccount;

interface PaymentAccountInterface {
    public function all();
    public function get($id);
    public function store($request);
    public function edit($id);
    public function update($request);
    public function delete($id);
}
