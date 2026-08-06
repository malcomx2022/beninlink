<?php
namespace App\Repositories\Hub;
use App\Models\Backend\Hub;
use App\Models\Backend\Parcel;
use App\Repositories\Hub\HubInterface;
use Carbon\Carbon;

class HubRepository implements HubInterface{
    public function all(){
        return Hub::where('company_id',settings()->id)->orderByDesc('id')->paginate(10);
    }
    public function filter($request){
        return Hub::where('company_id',settings()->id)->where(function($query)use($request){
            if($request->name){
                $query->where('name', 'like', '%' . $request->name . '%');
            }
            if($request->phone):
                $query->where('phone', 'like', '%' . $request->phone . '%');
            endif;

        })->orderByDesc('id')->paginate(10);
    }
    public function hubs(){
        return Hub::companywise()->get();
    }
    public function get($id){
        return Hub::find($id);
    }

    public function store($request){
        try {
            $hub           = new Hub();
            $hub->company_id = settings()->id;
            $hub->name     = $request->name;
            $hub->phone    = $request->phone;
            $hub->address  = $request->address;
            $hub->hub_lat  = $request->lat;
            $hub->hub_long = $request->long;
            $hub->status   = $request->status;
            $hub->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }
    public function update($id, $request)
    {
        try {
            $hub           = Hub::find($id);
            $hub->company_id = settings()->id;
            $hub->name     = $request->name;
            $hub->phone    = $request->phone;
            $hub->address  = $request->address;
            $hub->hub_lat  = $request->lat;
            $hub->hub_long = $request->long;
            $hub->status   = $request->status;
            $hub->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
        $hub = Hub::find($id);
        if($hub->company_id == settings()->id):
          return Hub::destroy($id);
        endif;
        return false;
    }


    public function parcelFilter($request,$id){
        $hub_id = $id;
       return  Parcel::companywise()->where(['hub_id' => $hub_id])->orderByDesc('id')->where(function( $query ) use ( $request ) {
            if($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
        });
    }

}
