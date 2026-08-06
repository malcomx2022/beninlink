<?php
namespace App\Repositories\HubManage\HubPayment;

interface HubPaymentInterface {
    public function all();
    public function get($id);
    public function store($request);
    public function edit($id);
    public function update($id,$request);
    public function delete($id);
    public function getSingleHubPayments($hub_id);
    public function reject($id);
    public function cancelReject($id);
    public function processed($request);
    public function cancelProcess($id);
}
