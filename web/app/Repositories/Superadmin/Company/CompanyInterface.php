<?php
namespace App\Repositories\Superadmin\Company;
interface CompanyInterface {
    public function get(); 
    public function getFind($id);
    public function store($request);
    public function update($id,$request);
    public function delete($id); 
    public function switchPlan($request);
    public function signUpStore($request);
    public function resendOTP($request);
    public function otpVerification($request);
}