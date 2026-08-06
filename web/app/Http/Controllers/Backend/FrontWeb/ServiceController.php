<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontWeb\Service\StoreRequest;
use App\Http\Requests\FrontWeb\Service\UpdateRequest;
use App\Repositories\FrontWeb\Service\ServiceInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    protected $repo;
    public function __construct(ServiceInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $services = $this->repo->get();
        return view('backend.front_web.service.index', compact('services'));
    }

    public function create()
    {
        return view('backend.front_web.service.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) :
            Toastr::success(__('levels.service_added'), __('message.success'));
            return redirect()->route('service.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }

    public function edit($id)
    {
        $service  = $this->repo->getFind($id);
        return view('backend.front_web.service.edit', compact('service'));
    }

    public function update(UpdateRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.service_updated'), __('message.success'));
            return redirect()->route('service.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }

    public function delete($id)
    {
        if(env('DEMO')):
            Toastr::error('Delete system is disable for the demo mode.',__('message.error'));
            return redirect()->back();
        endif;
        
        if ($this->repo->delete($id)) :
            Toastr::success(__('levels.service_deleted'), __('message.success'));
            return redirect()->back();
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        endif;
    }
}
