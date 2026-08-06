<?php
namespace App\Repositories\HubInCharge;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\HubInCharge;
use App\Models\Backend\Hub;
use App\Models\Backend\Upload;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\HubInCharge\HubInChargeInterface;


class HubInChargeRepository implements HubInChargeInterface {

    public function all($hubID){
        return HubInCharge::where('hub_id',$hubID)->with('user','hub')->orderByDesc('id')->get();

    }
    public function get($hubID,$id) {
        return HubInCharge::where(['id'=>$id,'hub_id'=>$hubID])->first();
    }

    public function store($hubID,$request) {
       try {

           $inCharge                             = new HubInCharge();
           $inCharge->user_id                    = $request->user_id;
           $inCharge->hub_id                     = $hubID;
           $inCharge->status                     = $request->status;
           $inCharge->save();
           if($request->status == Status::ACTIVE){
               $this->assignedHub($hubID,$inCharge);
           }
           return true;
       } catch (\Exception $e) {
            return false;
        }
    }

    public function update($hubID,$id, $request) {
        try {
            $inCharge                             = HubInCharge::where(['id'=>$id,'hub_id'=>$hubID])->first();
            $inCharge->status                     = $request->status;
            $inCharge->user_id                    = $request->user_id;
            $inCharge->hub_id                     = $hubID;
            $inCharge->save();
            if($request->status == Status::ACTIVE) {
                $this->assignedHub($hubID,$inCharge);
            }
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id) {

        return HubInCharge::destroy($id);
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
                $upload           = new Upload();
            }else{
                $upload           = Upload::find($image_id);
                unlink($upload->original);
            }

            $upload->original     = $image_name;
            $upload->save();
            return $upload->id;

        }
        catch (\Exception $e) {
            return false;
        }
    }

    public  function assignedHub($hubID, $inCharge)
    {

        try {
            $inChargesStatus = HubInCharge::whereNotIn('user_id',[$inCharge->user->id])->whereNotIn('id',[$inCharge->id])->where(['hub_id'=>$hubID,'status'=>Status::ACTIVE])->get();
            if(!blank($inChargesStatus)){
                foreach ($inChargesStatus as $incChargeStatus){
                    $incChargeStatus->status = Status::INACTIVE;
                    $incChargeStatus->save();
                }
            }

            $inCharge->status = Status::ACTIVE;
            $inCharge->save();

            $user                                 = User::find($inCharge->user_id);
            $user->hub_id                         = $hubID;
            $user->save();

            return true;
        }
        catch (\Exception $e) {
             return false;
        }
    }
    // get all rows in User model
    public function users(){
        return User::where('user_type',UserType::ADMIN)->orderBy('id')->get();
    }

    // get all rows in Hub model
    public function hub($hubID){
        return Hub::findOrFail($hubID);
    }

}
