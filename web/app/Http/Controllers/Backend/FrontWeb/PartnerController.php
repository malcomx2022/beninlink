<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Repositories\FrontWeb\Partner\PartnerInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\Http\Requests\FrontWeb\Partner\StoreRequest;
use App\Http\Requests\FrontWeb\Partner\UpdateRequest;

class PartnerController extends Controller
{
    protected $repo;
    public function __construct(PartnerInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $partners = $this->repo->get();
        return view('backend.front_web.partner.index', compact('partners'));
    }

    public function create()
    {
        return view('backend.front_web.partner.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) :
            Toastr::success(__('levels.partner_added'), __('message.success'));
            return redirect()->route('partner.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }

    public function edit($id)
    {
        $partner  = $this->repo->getFind($id);
        return view('backend.front_web.partner.edit', compact('partner'));
    }

    public function update(UpdateRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.partner_updated'), __('message.success'));
            return redirect()->route('partner.index');
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
            Toastr::success(__('levels.partner_deleted'), __('message.success'));
            return redirect()->back();
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        endif;
    }
}
