<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Models\Backend\CodCharge;
use App\Models\Backend\Merchant;
use App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    protected $deliveryCrarge;
    public function __construct(MerchantDeliveryChargeInterface $deliveryCrarge)
    {
        $this->deliveryCrarge=$deliveryCrarge;
    }

    public function CODcharges(){

        $cod_charge=Merchant::where('user_id',Auth::user()->id)->first();
        $cod_charges=$cod_charge;
        return view('backend.merchant_panel.settings.cod_charges',compact('cod_charges'));
    }

    public function deliveryCharges(){
        $merchant_id = Merchant::where('user_id',Auth::user()->id)->first();
        $delivery_charges = $this->deliveryCrarge->all($merchant_id->id);
        return view('backend.merchant_panel.settings.delivery_charge', compact('delivery_charges'));
    }
}
