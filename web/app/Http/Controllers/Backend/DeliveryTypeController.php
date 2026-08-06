<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Http\Request;

class DeliveryTypeController extends Controller
{

    public function index(){
        return view('backend.delivery_type.index');
    }

    public function status(Request $request){
        $deliverytype            =  Config::where('key',$request->key)->first();
        if($deliverytype->value  == Status::ACTIVE){
            $deliverytype->value =  Status::INACTIVE;
        }else{
            $deliverytype->value =  Status::ACTIVE;
        }
        $deliverytype->save();
        return $deliverytype;
    }
}
