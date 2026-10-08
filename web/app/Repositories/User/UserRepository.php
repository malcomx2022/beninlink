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

    /**
     * S35 — le perimetre des comptes, exactement celui de `get()`.
     *
     * `get()` etait scope ; `update()`, `delete()` et `permissionUpdate()` ne
     * l'etaient pas. Ils passent desormais tous par ici, pour qu'un ecart entre
     * la lecture et l'ecriture ne puisse plus reapparaitre.
     *
     * La regle du socle est double : un super-administrateur ne voit que les
     * super-administrateurs ; un administrateur ne voit que les ADMIN de SA
     * societe. Un marchand ou un livreur n'est donc pas joignable par ces
     * ecrans — ce qui compte, parce que `permission()` les atteignait.
     */
    private function utilisateurDeLaSociete($id){
        return User::where(function($query){
            if(Auth::user()->user_type == UserType::SUPER_ADMIN):
                $query->where('user_type', UserType::SUPER_ADMIN);
            else:
                $query->where('company_id',settings()->id);
                $query->where('user_type', UserType::ADMIN);
            endif;
        })->find($id);
    }

    /** Le role designe, s'il est bien de la societe. */
    private function roleDeLaSociete($roleId){
        return blank($roleId) ? null : Role::companywise()->find($roleId);
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
            // S35 — `Role::where('id', …)` nu : le nouveau compte recevait le jeu de
            // permissions d'un role d'une AUTRE societe. L'identifiant vient du
            // formulaire, donc cette route est hors du champ du filet.
            $role                   = $this->roleDeLaSociete($request->role_id);
            if(blank($role)){
                return false;
            }
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
            // S35 — `User::find($id)` NU, suivi de `company_id = settings()->id` :
            // la forme de REPRISE DE LIGNE, appliquee a un compte utilisateur. Cet
            // ecran reecrit l'e-mail, le MOT DE PASSE, le role et les permissions :
            // c'etait une reprise de compte complete sur l'administrateur d'un
            // autre transporteur. Son identifiant voyage dans le CORPS
            // (`PUT admin/users/update`), donc hors du champ du filet.
            $role = $this->roleDeLaSociete($request->role_id);
            $user = $this->utilisateurDeLaSociete($id);

            if(blank($user) || blank($role)){
                return false;
            }
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
                // S35 — `find($id)` nu : on supprimait le compte de n'importe quelle
                // societe, et son image du disque avec. Le `$id != 1` du socle ne
                // protegeait que le compte numero 1.
                $user = $this->utilisateurDeLaSociete($id);

                if(blank($user)){
                    return false;
                }
                $user->load('upload');
                
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
                $profileImage          = date('YmdHis') . "." . safeUploadExtension($image);
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
            // S35 — la lecture la plus nue du lot : ni societe, ni type. Cet ecran
            // ECRIT le jeu de permissions, et son identifiant vient du corps
            // (`PUT admin/users/permissions/update`) : on reecrivait les droits de
            // n'importe quel compte de n'importe quelle societe.
            $user = $this->utilisateurDeLaSociete($id);

            if(blank($user)){
                return false;
            }
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
