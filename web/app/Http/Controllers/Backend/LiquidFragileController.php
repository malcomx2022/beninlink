<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
class LiquidFragileController extends Controller
{
    public function index(){

       return view('backend.liquid_fragile.index');
    }
    public function edit(){

       $editliquid='edit';
       return view('backend.liquid_fragile.index',compact('editliquid'));
    }

    public function update(Request $request){
        $liquid         = Config::companywise()->where('key','fragile_liquid_charge')->first();
        $liquid->value  = $request->charge;
        $liquid->save();
        if($liquid){
            Toastr::success('Liquid/Fragile updated successfully.',__('message.success'));
            return redirect()->route('liquid-fragile.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function status(Request $request){

        $liquid            =  Config::companywise()->where('key','fragile_liquid_status')->first();
        if($liquid->value  == Status::ACTIVE){
            $liquid->value =  Status::INACTIVE;
        }else{
            $liquid->value =  Status::ACTIVE;
        }
        $liquid->save();
        return $liquid;

    }
}
