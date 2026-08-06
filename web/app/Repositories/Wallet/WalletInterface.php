<?php 
namespace App\Repositories\Wallet;
interface WalletInterface{
    public function get($request=null);
    public function recharges($request=null);
    public function getFind($id);
    public function store($request);
    public function delete($id);
    public function paymentStatus($orderId,$transactionId,$status);
    public function approved($id);
    public function rejected($id);
    public function expense($request); 
    public function adminstore($request);
}