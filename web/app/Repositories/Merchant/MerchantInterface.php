<?php
namespace App\Repositories\Merchant;

interface MerchantInterface {

    public function all();
    public function merchantIdlist();
    public function all_hubs();
    public function get($id);
    public function store($request);
    public function signUpStore($request);
    public function otpVerification($request);
    public function resendOTP($request);
    public function update($id,$request);
    public function delete($id);
    public function merchant_shops_get($id);
    public function socialSignupStore($request,$social);

}
