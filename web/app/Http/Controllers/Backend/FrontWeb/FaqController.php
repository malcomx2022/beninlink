<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontWeb\Faq\StoreRequest;
use App\Http\Requests\FrontWeb\Faq\UpdateRequest;
use App\Repositories\FrontWeb\Faq\FaqInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    protected $repo;
    public function __construct(FaqInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $faqs = $this->repo->get();
        return view('backend.front_web.faq.index', compact('faqs'));
    }

    public function create()
    {
        return view('backend.front_web.faq.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) :
            Toastr::success(__('levels.faq_added'), __('message.success'));
            return redirect()->route('faq.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput($request->all());
        endif;
    }

    public function edit($id)
    {
        $faq  = $this->repo->getFind($id);
        return view('backend.front_web.faq.edit', compact('faq'));
    }

    public function update(UpdateRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.faq_updated'), __('message.success'));
            return redirect()->route('faq.index');
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
            Toastr::success(__('levels.faq_deleted'), __('message.success'));
            return redirect()->back();
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        endif;
    }
}
