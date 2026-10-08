<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Deliverycategory\StoreRequest;
use App\Http\Requests\Deliverycategory\UpdateRequest;
use App\Repositories\DeliveryCategory\DeliveryCategoryInterface;
use Brian2694\Toastr\Facades\Toastr;
class DeliverycategoryController extends Controller
{
    protected $repo;
    public $notDeleteArray = [1];
    public function __construct(DeliveryCategoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $deliverycategorys = $this->repo->all();
        $notDeleteArray = $this->notDeleteArray;
        return view('backend.deliverycategory.index',compact('deliverycategorys','notDeleteArray'));
    }

    public function create()
    {
        return view('backend.deliverycategory.create');
    }

    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('Delivery category successfully added.'),__('message.success'));
            return redirect()->route('delivery-category.index');
        }else{
            Toastr::error(__('Something went wrong.'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $deliverycategory = $this->repo->get($id);
        return view('backend.deliverycategory.edit',compact('deliverycategory'));
    }

    public function update(UpdateRequest $request)
    {

        if($this->repo->update($request)){
            Toastr::success(__('Delivery category successfully updated.'),__('message.success'));
            return redirect()->route('delivery-category.index');
        }else{
            Toastr::error(__('Something went wrong.'),__('message.error'));
            return redirect()->back();
        }

    }

    public function destroy($id)
    {
        // S35 — succes annonce sans regarder ce que le depot avait fait.
        abort_unless($this->repo->delete($id), 404);

        Toastr::success(__('Delivery category successfully deleted.'),__('message.success'));
        return back();
    }
}
