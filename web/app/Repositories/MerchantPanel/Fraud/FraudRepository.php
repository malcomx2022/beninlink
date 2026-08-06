<?php
namespace App\Repositories\MerchantPanel\Fraud;
use App\Models\Backend\Fraud;
use App\Repositories\MerchantPanel\Fraud\FraudInterface;
use Illuminate\Support\Facades\Auth;

class FraudRepository implements FraudInterface{
    public function all(){
        return Fraud::orderByDesc('id')->paginate(10);
    }

    public function filter(){
        return Fraud::where('created_by', Auth::user()->id)->orderByDesc('id')->paginate(10);
    }

    public function check($request){
        return Fraud::where('phone','LIKE','%'. $request->phone .'%')->orderByDesc('id')->paginate(10);
    }

    public function get($id){
        return Fraud::find($id);
    }

    public function store($request){
        try {
            $fraud                = new Fraud();
            $fraud->created_by    = Auth::user()->id;
            $fraud->phone         = $request->phone;
            $fraud->name          = $request->name;
            $fraud->details       = $request->details;
            $fraud->tracking_id   = $request->tracking_id;
            $fraud->save();
            return true;
        } 
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $fraud                = Fraud::find($id);
            $fraud->created_by    = Auth::user()->id;
            $fraud->phone         = $request->phone;
            $fraud->name          = $request->name;
            $fraud->details       = $request->details;
            $fraud->tracking_id   = $request->tracking_id;
            $fraud->save();
            return true;
        } 
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
        return Fraud::destroy($id);
    }
}