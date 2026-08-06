<?php
namespace App\Repositories\FundTransfer;

interface FundTransferInterface {

    public function all();
    public function get($id);
    public function accounts();
    public function store($request);
    public function update($id, $request);
    public function delete($id);
    public function fundTransferSearch($request);
    public function fundTransferFilter($request);
}
