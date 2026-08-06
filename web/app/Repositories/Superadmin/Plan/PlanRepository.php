<?php
namespace  App\Repositories\Superadmin\Plan;

use App\Enums\Status;
use App\Models\Backend\Superadmin\Plan;
use App\Repositories\Role\RoleInterface;
use Illuminate\Support\Facades\DB;  
use App\Repositories\Superadmin\Plan\PlanInterface;
class PlanRepository implements PlanInterface{
    protected $planModel,$roleRepo;
    public function __construct(Plan $planModel,RoleInterface $roleRepo){
        $this->planModel = $planModel;
        $this->roleRepo = $roleRepo;
    }
    public function get(){
        return $this->planModel::orderBy('position','asc')->paginate(10);
    }
    public function getActive(){
        return $this->planModel::where('status',Status::ACTIVE)->orderBy('position','asc')->get();
    }
    public function getFind($id){
        return $this->planModel::find($id);
    }
    public function store($request){
        try { 
  
            $plan             = new $this->planModel(); 
            $plan->name       = $request->name;
            $plan->parcel_count = $request->parcel_count;
            $plan->deliveryman_count = $request->deliveryman_count;
            $plan->days_count = $request->days_count;
            $plan->price      = $request->price;
            $plan->description= $request->description; 
            $plan->position   = $request->position;
            $plan->modules    = $request->modules?? [];
            $plan->status     = $request->status;
            $plan->save();

           return true;
        } catch (\Throwable $th) {
 
           return false;
        }
    }
    public function update($id,$request){
        try {
            $plan              = $this->planModel::find($id); 
            $plan->name       = $request->name;
            $plan->parcel_count = $request->parcel_count;
            $plan->deliveryman_count = $request->deliveryman_count;
            $plan->days_count = $request->days_count;
            $plan->price      = $request->price;
            $plan->description= $request->description; 
            $plan->position   = $request->position;
            $plan->modules    = $request->modules?? [];
            $plan->status     = $request->status;
            $plan->save(); 
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function delete($id){
        return $this->planModel::destroy($id);
    }
 
   

    

}