<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Packaging\StoreRequest;
use App\Http\Requests\Packaging\UpdateRequest;
use App\Repositories\Packaging\PackagingInterface;
use Brian2694\Toastr\Facades\Toastr;
class PackagingController extends Controller
{
    protected $repo;
    public function __construct(PackagingInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $packagings = $this->repo->all();
        return view('backend.packaging.index',compact('packagings'));
    }

    public function create()
    {
        return view('backend.packaging.create');
    }

    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success('Packaging successfully added.',__('message.success'));
            return redirect()->route('packaging.index');

        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $packaging = $this->repo->get($id);
        return view('backend.packaging.edit',compact('packaging'));
    }

    public function update(UpdateRequest $request)
    {

        if($this->repo->update($request)){
            Toastr::success('Packaging successfully updated.',__('message.success'));
            return redirect()->route('packaging.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }

    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Packaging successfully deleted.',__('message.success'));
        return back();
    }
}
