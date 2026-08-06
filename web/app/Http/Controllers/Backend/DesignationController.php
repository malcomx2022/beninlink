<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Designation\StoreRequest;
use App\Http\Requests\Designation\UpdateRequest;
use App\Repositories\Designation\DesignationInterface;
use Brian2694\Toastr\Facades\Toastr;
class DesignationController extends Controller
{
    protected $repo;
    public function __construct(DesignationInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $designations = $this->repo->all();
        return view('backend.designation.index',compact('designations'));
    }

    public function create()
    {
        return view('backend.designation.create');
    }

    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success('Designation successfully added.',__('message.success'));
            return redirect()->route('designations.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $designation = $this->repo->get($id);
        return view('backend.designation.edit',compact('designation'));
    }

    public function update(UpdateRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success('Designation successfully updated.',__('message.success'));
            return redirect()->route('designations.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Designation successfully deleted.',__('message.success'));
        return back();
    }

}
