<?php


namespace App\Http\Services;

use App\Models\Backend\GeneralSettings;
use Illuminate\Support\Facades\Http;

class PurchaseVerify
{
    public function purchaseVerify(){
		return true;
    }

 
    public function PurchaseVerification($code) { 
		return 200;
    } 

}