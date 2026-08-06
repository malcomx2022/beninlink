<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DeliveryCharge\StoreRequest;
use App\Http\Requests\DeliveryCharge\UpdateRequest;
use App\Repositories\DeliveryCharge\DeliveryChargeInterface;
use Brian2694\Toastr\Facades\Toastr;
class DeliveryChargeController extends Controller
{
    protected $repo;
    public function __construct(DeliveryChargeInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $delivery_charges = $this->repo->all();
        $categories = $this->repo->categories();
        return view('backend.delivery_charge.index',compact('delivery_charges','categories','request'));
    }
    public function filter(Request $request)
        {
            $delivery_charges = $this->repo->filter($request);
            $categories = $this->repo->categories();
            return view('backend.delivery_charge.index',compact('delivery_charges','categories','request'));
        }

    public function create()
    {
        $categories = $this->repo->categories();
        return view('backend.delivery_charge.create',compact('categories'));
    }

    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('delivery_charge.added_msg'),__('message.success'));
            return redirect()->route('delivery-charge.index');
        }else{
            Toastr::error(__('delivery_charge.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $categories = $this->repo->categories();
        $delivery_charge = $this->repo->get($id);
        return view('backend.delivery_charge.edit',compact('delivery_charge', 'categories'));
    }

    public function update(UpdateRequest $request)
    {

        if($this->repo->update($request)){
            Toastr::success(__('delivery_charge.update_msg'),__('message.success'));
            return redirect()->route('delivery-charge.index');
        }else{
            Toastr::error(__('delivery_charge.error_msg'),__('message.error'));
            return redirect()->back();
        }

    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('delivery_charge.delete_msg'),__('message.success'));
        return back();
    }
}
