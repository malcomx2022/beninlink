<?php
namespace App\Repositories\Fraud;
use App\Models\Backend\Fraud;
use App\Repositories\Fraud\FraudInterface;
use Illuminate\Support\Facades\Auth;

class FraudRepository implements FraudInterface{
    public function all(){
        return Fraud::companywise()->orderByDesc('id')->paginate(10);
    }

    public function get($id){
        return Fraud::find($id);
    }

    public function store($request){
        try {
            $fraud                = new Fraud();
            $fraud->company_id    = settings()->id;
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
        $fraud =  Fraud::find($id);
        if($fraud->company_id == settings()->id):
            return Fraud::destroy($id);
        endif;
        return false;
    }
}