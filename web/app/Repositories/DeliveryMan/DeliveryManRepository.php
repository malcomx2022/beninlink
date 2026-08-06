<?php
namespace App\Repositories\DeliveryMan;

use App\Enums\StatementType;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Expense;
use App\Models\Backend\Hub;
use App\Models\Backend\Income;
use App\Models\Backend\Upload;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use Illuminate\Support\Facades\Auth;

class DeliveryManRepository implements DeliveryManInterface {

    public function all(){
        return DeliveryMan::where('company_id',settings()->id)->with('uploadLicense','user','hub')->orderByDesc('id')->paginate(10);
    }

    public function filter($request){
        return DeliveryMan::where('company_id',settings()->id)->with('uploadLicense','user','hub')->where(function($query)use($request){
            $query->whereHas('user', function ($queryUser)use($request) {
                if($request->name){
                    $queryUser->where('name', 'like', '%' . $request->name . '%');
                }
                if($request->email){
                    $queryUser->where('email', 'like', '%' . $request->email . '%');
                }
                if($request->phone):
                    $queryUser->where('mobile', 'like', '%' . $request->phone . '%');
                endif;
            });


        })->orderByDesc('id')->paginate(10);
    }

    public function get($id) {
        return DeliveryMan::where('company_id',settings()->id)->where('id',$id)->first();
    }

    public function generateUniqueID() {
        do {
            $login_id = random_int(100000, 999999);
        } while (User::where("unique_id", "=", $login_id)->first());

        return $login_id;
    }

    public function store($request) {
       try {

           $uniqueID =  $this->generateUniqueID();

           DB::beginTransaction();
           $deliveryUser                       = new User();
           $deliveryUser->unique_id            = $uniqueID;
           $deliveryUser->company_id           = settings()->id;
           $deliveryUser->name                 = $request->name;
           $deliveryUser->mobile               = $request->mobile;
           $deliveryUser->email                = $request->email;
           $deliveryUser->password             = Hash::make($request->password);
           $deliveryUser->address              = $request->address;
           $deliveryUser->hub_id               = $request->hub_id;
           $deliveryUser->status               = $request->status;
           $deliveryUser->user_type            = UserType::DELIVERYMAN;
            if($request->salary !==""):
                $deliveryUser->salary          = $request->salary;
            endif;
            if(isset($request->image_id) &&$request->image_id != null) {
               $deliveryUser->image_id = $this->user_image($deliveryUser->image_id, $request->image_id);
            }
           $deliveryUser->save();
           $deliveryMan                                 = new DeliveryMan();
           $deliveryMan->company_id                     = settings()->id;
           $deliveryMan->user_id                        = $deliveryUser->id;
           $deliveryMan->delivery_lat                   = $request->lat;
           $deliveryMan->delivery_long                  = $request->long;
           if($request->delivery_charge !==""):
               $deliveryMan->delivery_charge            = $request->delivery_charge;
           endif;
           if($request->pickup_charge !==""):
               $deliveryMan->pickup_charge              = $request->pickup_charge;
           endif;
           if($request->return_charge !==""):
                $deliveryMan->return_charge             = $request->return_charge;
           endif;
           if($request->opening_balance !==""):
                $deliveryMan->current_balance           = $request->opening_balance;
                $deliveryMan->opening_balance           = $request->opening_balance;
           endif;
           if(isset($request->driving_license_image_id) &&$request->driving_license_image_id != null) {
               $deliveryMan->driving_license_image_id = $this->driving_license_image($deliveryMan->driving_license_image_id, $request->driving_license_image_id);
           }
           $deliveryMan->save();
            DB::commit();
            return true;
       } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function update($id, $request) {
        try {
            DB::beginTransaction();
            $deliveryMan                                 = DeliveryMan::findOrFail($id);
            $deliveryMan->company_id                    = settings()->id;
            $deliveryMan->delivery_lat                   = $request->lat;
            $deliveryMan->delivery_long                  = $request->long;
            if($request->delivery_charge !==""):
                $deliveryMan->delivery_charge            = $request->delivery_charge;
            endif;
            if($request->pickup_charge !==""):
                $deliveryMan->pickup_charge              = $request->pickup_charge;
            endif;
            if($request->return_charge !==""):
                 $deliveryMan->return_charge             = $request->return_charge;
            endif;
            if($request->opening_balance !==""):
                 $deliveryMan->current_balance           = $request->opening_balance;
                 $deliveryMan->opening_balance           = $request->opening_balance;
            endif;

            if(isset($request->driving_license_image_id) &&$request->driving_license_image_id != null) {
                $deliveryMan->driving_license_image_id = $this->driving_license_image($deliveryMan->driving_license_image_id, $request->driving_license_image_id);
            }
            $deliveryMan->save();
            $deliveryUser                       = User::findOrFail($deliveryMan->user_id);
            $deliveryUser->company_id           = settings()->id;
            $deliveryUser->status               = $request->status;
            $deliveryUser->name                 = $request->name;
            $deliveryUser->mobile               = $request->mobile;
            $deliveryUser->email                = $request->email;
            $deliveryUser->address              = $request->address;
            $deliveryUser->hub_id               = $request->hub_id;
            if($request->salary !==""):
                $deliveryUser->salary               = $request->salary;
            endif;
            if($request->password != null)
            {
                $deliveryUser->password = Hash::make($request->password);
            }

            if(isset($request->image_id) &&$request->image_id != null) {
                $deliveryUser->image_id = $this->user_image($deliveryUser->image_id, $request->image_id);
            }
            $deliveryUser->save();
            DB::commit();
            return true;
        }
        catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function delete($id) {
        $deliveryman = DeliveryMan::find($id);
        if($deliveryman->company_id == settings()->id):
           return  User::destroy($deliveryman->user_id);
        endif;
        return false;
    }

    public function user_image($image_id = '', $image)
    {
        try {
            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/users');
                $profileImage          = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/users/'.$profileImage;
            }
            if(blank($image_id)){
                $upload                = new Upload();
            }else{
                $upload                = Upload::find($image_id);
                unlink($upload->original);
            }
            $upload->original          = $image_name;
            $upload->save();
            return $upload->id;

        }
        catch (\Exception $e) {
            return false;
        }
    }

    // for deliveryMan image upload
    public function driving_license_image($image_id = '', $image) {
        try {
            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/deliveryMan/image');
                $deliveryManImage      = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $deliveryManImage);
                $image_name            = 'uploads/deliveryMan/image/'.$deliveryManImage;
            }
            if(blank($image_id)){
                $upload                = new Upload();
            }else{
                $upload                = Upload::find($image_id);
                unlink($upload->original);
            }
            $upload->original          = $image_name;
            $upload->save();
            return $upload->id;

        }
        catch (\Exception $e) {
            return false;
        }
    }

    // get all rows in Hub model
    public function hubs(){
        return Hub::where('company_id',settings()->id)->orderBy('name')->get();
    }

    public function deliverymanEarn($type){
        return DeliverymanStatement::where('company_id',settings()->id)->where('type', $type)->where('delivery_man_id',Auth::user()->deliveryman->id)->get();
    }

    public function totalCOD($type){
        return DeliverymanStatement::where('company_id',settings()->id)->where('type', $type)->where('delivery_man_id',Auth::user()->deliveryman->id)->where('cash_collection',1)->get();
    }
    public function paymentLogs(){
        $data  = [];
        $income    = Income::where('company_id',settings()->id)->where('account_head_id',2)->where('delivery_man_id',Auth::user()->deliveryman->id)->get();
        $expense   = Expense::where('company_id',settings()->id)->where('account_head_id',5)->where('delivery_man_id',Auth::user()->deliveryman->id)->get();
        $data['income']  = $income;
        $data['expense'] = $expense;
        return $data;
    }
    public function parcelPaymentLogs(){
        return DeliverymanStatement::where('company_id',settings()->id)->orderByDesc('id')->where('delivery_man_id',Auth::user()->deliveryman->id)->where('type',StatementType::EXPENSE)->where('cash_collection',1)->select(['id','type','amount','date','note','created_at','updated_at'])->get();
    }

}
