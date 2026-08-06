<?php
namespace App\Repositories\User;
use App\Models\User;
use App\Models\Backend\Hub;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Models\Backend\Upload;
use Illuminate\Support\Facades\Hash;
use App\Repositories\User\UserInterface;
use App\Enums\UserType;
use App\Models\Backend\Role;
use Illuminate\Support\Facades\Auth;

class UserRepository implements UserInterface{

    // get all rows in User model with Upload & Hub model row same as foreign key.
    public function all(){
        return User::where(function($query){ 
             if(isSuperadmin()):
                $query->where('user_type', UserType::SUPER_ADMIN);
            else:
                $query->where('company_id',settings()->id);
                $query->where('user_type', UserType::ADMIN);
            endif;
        })->with('upload','hub')->orderByDesc('id')->paginate(10);
    }

    public function filter($request){
        return User::where(function($query){
            if(isSuperadmin()):
                $query->where('user_type', UserType::SUPER_ADMIN);
            else:
                $query->where('company_id',settings()->id);
                $query->where('user_type', UserType::ADMIN);
            endif;
        })->where(function($query)use($request){
            if($request->name){
                $query->where('name', 'like', '%' . $request->name . '%');
            }
            if($request->email){
                $query->where('email', 'like', '%' . $request->email . '%');
            }
            if($request->phone):
                $query->where('mobile', 'like', '%' . $request->phone . '%');
            endif;

        })->orderByDesc('id')->paginate(10);
    }

    // get all rows in Hub model
    public function hubs(){
        return Hub::where('company_id',settings()->id)->orderBy('name')->get();
    }

    // get all rows in Department model
    public function departments(){
        return Department::where('company_id',settings()->id)->active()->orderBy('title')->get();
    }

    // get all rows in Designation model
    public function designations(){
        return Designation::where('company_id',settings()->id)->active()->orderBy('title')->get();
    }

    // get single row in User model with Upload model row same as foreign key.
    public function get($id){
        return User::where(function($query){
            if(Auth::user()->user_type == UserType::SUPER_ADMIN):
                $query->where('user_type', UserType::SUPER_ADMIN);
            else:
                $query->where('company_id',settings()->id);
                $query->where('user_type', UserType::ADMIN);
            endif;
        })->with('upload','role')->find($id);
    }

    // All request data store in User tabel.
    public function store($request)
    {

        try {
            $role                   = Role::where('id',$request->role_id)->first();
            $user                   = new User();
            $user->name             = $request->name;
            $user->email            = $request->email;
            $user->password         = Hash::make($request->password);
            $user->mobile           = $request->mobile;
             
            $user->company_id   = settings()->id;
            if(isSuperadmin()):
                $user->user_type    = UserType::SUPER_ADMIN;
            endif;
       
            $user->nid_number       = $request->nid_number;
            $user->designation_id   = $request->designation_id;
            $user->department_id    = $request->department_id;
            if($request->hub_id && !blank($request->hub_id)):
                $user->hub_id           = $request->hub_id ? $request->hub_id :null;
            endif;
            $user->image_id         = $this->file('', $request->image);
            $user->joining_date     = $request->joining_date;
            $user->address          = $request->address;
            $user->role_id          = $request->role_id;
            $user->salary           = $request->salary !== ""? $request->salary: 0;
            if($request->hub_id){
             $user->permissions     = $this->hubPermissions();
            }else{
                if($role->permissions !== null){
                    $user->permissions  = $role->permissions;
                }
            }
            $user->status           = $request->status;
            $user->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    // All request data update in User tabel.
    public function update($id, $request)
    {
        try {
            $role=Role::where('id',$request->role_id)->first();

            $user                       = User::find($id);
            $user->name                 = $request->name;
            $user->email                = $request->email;
            $user->mobile               = $request->mobile; 
            $user->company_id           = settings()->id; 
            if(isSuperadmin()):
                $user->user_type    = UserType::SUPER_ADMIN;
            endif;
            $user->nid_number           = $request->nid_number;
            if($id != 1){
                $user->hub_id           = $request->hub_id ? $request->hub_id:null;
                $user->designation_id   = $request->designation_id;
                $user->department_id    = $request->department_id;
                $user->status           = $request->status;
            }
            $user->joining_date         = $request->joining_date;
            $user->address              = $request->address;
            if($request->password != null)
            {
                $user->password = Hash::make($request->password);
            }
            if(isset($request->image) && $request->image != null)
            {
                $user->image_id = $this->file($user->image_id, $request->image);
            }
            $user->role_id              = $request->role_id;
            $user->salary               = $request->salary !== ""? $request->salary :0;
            if($request->hub_id){
                $user->permissions     = $this->hubPermissions();
            }elseif($role){
                if($role->permissions !== null){
                    $user->permissions  = $role->permissions;
                }
            }
            $user->save();
            return true;

        } catch (\Exception $e) { 
            return false;
        }
    }

    private function hubPermissions(){
        return [
            'dashboard_read',
            'hub_payment_read',
            'parcel_read',
            'cash_received_from_delivery_man_read',
            'cash_received_from_delivery_man_create',
            'cash_received_from_delivery_man_update',
            'cash_received_from_delivery_man_delete',
            'hub_payment_request_read',
            'hub_payment_request_create',
            'hub_payment_request_delete',
        ];
    }

    // Delete single row in User Model with Delete single row in Upload model and delete image in public/upload/user folder..
    public function delete($id){
        try {
            if($id != 1){
                $user = User::with('upload')->find($id); 
              
                
                if($user->upload && !empty($user->upload->original) && file_exists(public_path($user->upload->original))):
                    unlink(public_path($user->upload->original));
                    Upload::destroy($user->upload->id);
                endif; 
                $user->delete();
                return 'delete'; 
            }
            else{ 
                return 0;
            }
        }
        catch (\Exception $e) {
            return false;
        }
    }

    // Request image Store in Upload Model and image copy file attach in public/upload/user folder.
    public function file($image_id = '', $image)
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
                if(file_exists(public_path($upload->original)))
                {
                    unlink(public_path($upload->original));
                }
            }
            $upload->original     = $image_name;
            $upload->save();
            return $upload->id;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function permissionUpdate($id,$request){

        try {
            $user=User::where('id',$id)->first();
            if($request->permissions !==null){
                $user->permissions =$request->permissions;
            }else{
                $user->permissions =[];
            }
            $user->save();
            return true;

        } catch (\Throwable $th) {
          return false;
        }
    }




}
