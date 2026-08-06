<?php
namespace App\Repositories\Role;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Role;
use App\Models\Permission;
use App\Models\SuperAdminPermission;
use App\Repositories\Role\RoleInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class RoleRepository implements RoleInterface
{

    public function all()
    {
        return Role::where(function($query){ 
            $query->where('company_id',settings()->id); 
        })->orderByDesc('id')->paginate(10);
    }

    public function getRole(){
        return Role::where(function($query){ 
            $query->where('company_id',settings()->id);  
        })->where('status',Status::ACTIVE)->get();
    }
    public function get($id)
    {
        return Role::find($id);
    }

    public function store($request)
    {
        try {
            $role             = new Role();
            $role->name       = $request->name;  
            $role->company_id = settings()->id;  
            $role->permissions= $request->permissions;
            $role->status     = $request->status;
            $role->slug       = str_replace(' ', '-',  strtolower($request->name));
            $role->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $role             = Role::find($id);
            $role->name       = $request->name;
            $role->company_id = settings()->id;  
            $role->permissions= $request->permissions;
            $role->status     = $request->status;
            $role->slug       = str_replace(' ', '-',  strtolower($request->name));
            $role->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
        $role = Role::find($id);
        if($role && $role->company_id == settings()->id):
           return  Role::destroy($id);
        endif;
        return false;
    }

    public function status($id){
        $role         = Role::find($id);
        $role->status = !$role->status;
        $role->save();
        return $role->status;
    }

    public function permissions($role)
    {
        if(isSuperadmin()){
            // dd(array_values(Arr::collapse(SuperAdminPermission::pluck('keywords'))));
            return SuperAdminPermission::all();
        }elseif($role == 'admin' || $role == 'super-admin'){
            $ownerBlockedPermission[]['attribute'] = 'hub_payments_request';
            $ownerBlockedPermission[]['attribute'] = 'cash_received_from_delivery_man';
            return Permission::whereNotIn('attribute',$ownerBlockedPermission)->orderBy('id','asc')->get();

        }else {
            return Permission::orderBy('id','asc')->get();

        }
    }



    public function adminPermissionsModules(){
        $ownerBlockedPermission[]['attribute'] = 'hub_payments_request';
        $ownerBlockedPermission[]['attribute'] = 'cash_received_from_delivery_man';
        $modules = Permission::whereNotIn('attribute',$ownerBlockedPermission)->pluck('attribute');
        return $modules;
    }
}
