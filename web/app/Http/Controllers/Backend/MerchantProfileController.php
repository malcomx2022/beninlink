<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantProfile\UpdateRequest;
use App\Http\Requests\MerchantProfile\UpdatePasswordRequest;
use App\Repositories\MerchantProfile\MerchantProfileInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;

class MerchantProfileController extends Controller
{
    protected $repo;
    public function __construct(MerchantProfileInterface $repo)
    {
        $this->repo = $repo;
    }

    public function view($id) // auth id
    {
         
        if(Auth::user()->id != $id):
            abort(500);
        endif;
        $merchat = $this->repo->get(auth()->user()->id);
        return view('backend.merchant_profile.index',compact('merchat'));
    }

    public function create($id) // user id
    {
        $merchat = $this->repo->get(auth()->user()->id);
        return view('backend.merchant_profile.update',compact('merchat'));
    }

    public function changePassword($id)
    {
        if(Auth::user()->facebook_id !== null || Auth::user()->google_id !== null):
            return redirect()->back();
        endif;
        $merchat = $this->repo->get(auth()->user()->id);
        return view('backend.merchant_profile.change_password',compact('merchat'));
    }

    public function update($id, UpdateRequest $request)
    {
        if($this->repo->update(auth()->user()->id, $request)){
            Toastr::success('Merchant Profile updated successfully.',__('message.success'));
            return redirect()->route('merchant-profile.index',$id);
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function updatePassword($id, UpdatePasswordRequest $request)
    {
        $result = $this->repo->updatePassword(auth()->user()->id, $request);
        if($result == 1){
            Toastr::success('Password updated successfully',__('message.success'));
            return redirect()->route('merchant-profile.index',$id);
        }
        elseif($result == 0){
            Toastr::warning('Old password not match!',__('message.warning'));
            return redirect()->back()->withInput();
        }
        else
        {
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }
}
