<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Fraud\StoreRequest;
use App\Http\Requests\Fraud\UpdateRequest;
use App\Repositories\Fraud\FraudInterface;
use Brian2694\Toastr\Facades\Toastr;
class FraudController extends Controller
{
    protected $repo;
    public function __construct(FraudInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $frauds = $this->repo->all();
        return view('backend.fraud.index',compact('frauds'));
    }

    public function create()
    {
        return view('backend.fraud.create');
    }

    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('fraud.added_msg'),__('message.success'));
            return redirect()->route('fraud.index');
        }else{
            Toastr::error(__('fraud.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $fraud = $this->repo->get($id);
        return view('backend.fraud.edit',compact('fraud'));
    }

    public function update(UpdateRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success(__('fraud.update_msg'),__('message.success'));
            return redirect()->route('fraud.index');
        }else{
            Toastr::error(__('fraud.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('fraud.delete_msg'),__('message.success'));
        return back();
    }
}
