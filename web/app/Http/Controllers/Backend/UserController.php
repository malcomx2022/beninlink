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
       
        if($this->repo->delete($id) == 'delete'){
            Toastr::success('User successfully deleted.',__('message.success'));
            return back();
        }
        elseif($this->repo->delete($id) == 0){
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
        $user        = User::where('id',$id)->first();
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
