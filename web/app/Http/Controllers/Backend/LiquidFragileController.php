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
        // Décision métier 2026-09-05 : taux de TVA de la société, sur la même
        // page « frais » (0 à 100, entier ou décimal ; 0 = pas de TVA).
        if ($request->has('vat_rate')) {
            $request->validate(['vat_rate' => ['numeric', 'min:0', 'max:100']]);
            $vat = Config::companywise()->where('key', \App\Services\Parcel\VatRate::CONFIG_KEY)->first();
            if (blank($vat)) {
                $vat = new Config();
                $vat->company_id = settings()->id;
                $vat->key = \App\Services\Parcel\VatRate::CONFIG_KEY;
            }
            $vat->value = (float) $request->vat_rate;
            $vat->save();
        }
        if($liquid){
            Toastr::success(__('Liquid/Fragile updated successfully.'),__('message.success'));
            return redirect()->route('liquid-fragile.index');
        }else{
            Toastr::error(__('Something went wrong.'),__('message.error'));
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
