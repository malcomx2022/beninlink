<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontWeb\WhyCourier\StoreRequest;
use App\Http\Requests\FrontWeb\WhyCourier\UpdateRequest;
use App\Repositories\FrontWeb\WhyCourier\WhyCourierInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class WhyCourierController extends Controller
{
    protected $repo;
    public function __construct(WhyCourierInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $whycouriers = $this->repo->get();
        return view('backend.front_web.why_courier.index', compact('whycouriers'));
    }

    public function create()
    {
        return view('backend.front_web.why_courier.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) :
            Toastr::success(__('levels.why_courier_added'), __('message.success'));
            return redirect()->route('why.courier.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput($request->all());
        endif;
    }

    public function edit($id)
    {
        $whycourier  = $this->repo->getFind($id);
        return view('backend.front_web.why_courier.edit', compact('whycourier'));
    }

    public function update(UpdateRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.why_courier_updated'), __('message.success'));
            return redirect()->route('why.courier.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput($request->all());
        endif;
    }

    public function delete($id)
    {
        if(env('DEMO')):
            Toastr::error('Delete system is disable for the demo mode.',__('message.error'));
            return redirect()->back();
        endif;
        
        if ($this->repo->delete($id)) :
            Toastr::success(__('levels.why_courier_deleted'), __('message.success'));
            return redirect()->back();
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        endif;
    }
}
