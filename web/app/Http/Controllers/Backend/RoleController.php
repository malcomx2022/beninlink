<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\SuperAdminPermission;
use App\Repositories\Role\RoleInterface;
use Brian2694\Toastr\Facades\Toastr;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Arr;

class RoleController extends Controller
{
    protected $repo;

    public function __construct(RoleInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $roles = $this->repo->all();
        return view('backend.role.index', compact('roles'));
    }

    public function create()
    {
        $permissions            = $this->repo->permissions(null);
      
        return view('backend.role.create',compact('permissions'));
    }

    public function store(StoreRoleRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success('Role successfully added.',__('message.success'));
            return redirect()->route('roles.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $role = $this->repo->get($id);
        $permissions=$this->repo->permissions($role->slug);
        return view('backend.role.edit',compact('role','permissions'));
    }

    public function update(UpdateRoleRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success('Role successfully updated.',__('message.success'));
            return redirect()->route('roles.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        if(env('DEMO')):
            Toastr::error('Delete system is disable for the demo mode.',__('message.error'));
            return redirect()->back();
        endif;

        if($this->repo->delete($id)):
            Toastr::success('Role successfully deleted.',__('message.success'));
            return back();
        else:
            Toastr::error('Something went wrong!',__('message.error'));
            return back();
        endif; 
    }

    public function changeStatus(Request $request)
    {
        $result = $this->repo->status($request->id);
        return response()->json(['success'=>$result]);
    }
}
