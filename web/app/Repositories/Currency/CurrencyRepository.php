<?php
namespace App\Repositories\Currency;

use App\Enums\Status;
use App\Models\Backend\Currency;
use App\Repositories\Currency\CurrencyInterface;
use Illuminate\Support\Facades\DB;

class CurrencyRepository implements CurrencyInterface{
    public function get(){
        return Currency::orderBy('position','asc')->paginate(10);
    }
    public function getActive(){
        return Currency::where('status',Status::ACTIVE)->orderBy('position','asc')->get();
    }
    public function getFind($id){
        return Currency::find($id);
    }
    public function store($request){
        DB::beginTransaction();
        try {
            $currency                = new Currency();
            $currency->name          = $request->name;
            $currency->symbol        = $request->symbol;
            $currency->exchange_rate = $request->exchange_rate;
            $currency->position      = $request->position;
            $currency->status        = $request->status;
            $currency->save();
            DB::commit();
            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }
    public function update($request){
        DB::beginTransaction();
        try {
            $currency                = Currency::find($request->id);
            $currency->name          = $request->name;
            $currency->symbol        = $request->symbol;
            $currency->exchange_rate = $request->exchange_rate;
            $currency->position      = $request->position;
            $currency->status        = $request->status;
            $currency->save();
            DB::commit();
            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }
    public function delete($id){
        return Currency::destroy($id);
    }
}
