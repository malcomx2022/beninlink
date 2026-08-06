<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Department\StoreRequest;
use App\Http\Requests\Department\UpdateRequest;
use App\Repositories\Department\DepartmentInterface;
use Brian2694\Toastr\Facades\Toastr;
class DepartmentController extends Controller
{
    protected $repo;
    public function __construct(DepartmentInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $departments = $this->repo->all();
        return view('backend.department.index',compact('departments'));
    }

    public function create()
    {
        return view('backend.department.create');
    }

    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success('Department successfully added.',__('message.success'));
            return redirect()->route('departments.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $department = $this->repo->get($id);
        return view('backend.department.edit',compact('department'));
    }

    public function update(UpdateRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success('Department successfully updated.',__('message.success'));
            return redirect()->route('departments.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Department successfully deleted.',__('message.success'));
        return back();
    }
}
