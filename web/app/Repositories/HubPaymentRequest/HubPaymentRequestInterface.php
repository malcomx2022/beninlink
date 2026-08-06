<?php
namespace App\Repositories\HubPaymentRequest;

interface HubPaymentRequestInterface {
    public function all();
    public function get($id);
    public function store($request);
    public function edit($id);
    public function update($id,$request);
    public function delete($id);
}
