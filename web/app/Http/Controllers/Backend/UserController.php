<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Repositories\Role\RoleInterface;
use App\Repositories\User\UserInterface;
use Brian2694\Toastr\Facades\Toastr;
class UserController extends Controller
{
    protected $repo;
    public function __construct(UserInterface $repo,RoleInterface $role)
    {
        $this->repo = $repo;
        $this->role =$role;
    }

    public function index(Request $request)
    {
        $users = $this->repo->all();
        return view('backend.user.index',compact('users','request'));
    }
    public function filter(Request $request)
    {
        $users = $this->repo->filter($request);
        return view('backend.user.index',compact('users','request'));
    }

    public function create()
    {
        $hubs         = $this->repo->hubs();
        $departments  = $this->repo->departments();
        $designations = $this->repo->designations();
        $roles        = $this->role->getRole();
        return view('backend.user.create',compact('hubs','departments','designations','roles'));
    }

    public function store(StoreUserRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success('User successfully added.',__('message.success'));
            return redirect()->route('users.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $user         = $this->repo->get($id);
        // S35 — le depot etait scope, mais rien ne verifiait son resultat : la vue
        // dereferencait `null` hors perimetre (famille S15).
        abort_if(blank($user), 404);

        $hubs         = $this->repo->hubs();
        $departments  = $this->repo->departments();
        $designations = $this->repo->designations();
        $roles        = $this->role->getRole();
        return view('backend.user.edit',compact('user','hubs','departments','designations','roles'));
    }

    public function update(UpdateUserRequest $request)
    {

        if($this->repo->update($request->id, $request)){
            Toastr::success('User successfully updated.',__('message.success'));
            return redirect()->route('users.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        // S35 — le socle appelait `delete()` une SECONDE fois dans le `elseif` :
        // une suppression qui echouait au premier tour etait donc retentee, image
        // du disque comprise. On appelle une fois et on lit le resultat.
        $resultat = $this->repo->delete($id);

        if($resultat === 'delete'){
            Toastr::success('User successfully deleted.',__('message.success'));
            return back();
        }
        elseif($resultat === 0){
            Toastr::warning('Super admin cannot be deleted!',__('message.warning'));
            return back();
        }
        else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }
    //user permissions
    public function permission($id){
        // S35 — `User::where('id',$id)->first()` : ni societe, ni type. Cet ecran
        // montrait le jeu de permissions de l'administrateur d'un autre
        // transporteur — et meme d'un marchand ou d'un livreur, que le depot scope
        // ecarte par son filtre sur `user_type`.
        $user        = $this->repo->get($id);
        abort_if(blank($user) || blank($user->role), 404);

        $permissions = $this->role->permissions($user->role->slug);

        return view('backend.user.permissions',compact('user','permissions'));
    }
    public function permissionsUpdate(Request $request){
        if($this->repo->permissionUpdate($request->id,$request)){
            Toastr::success('Permissions successfully updated.',__('message.success'));
            return redirect()->route('users.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }


}
