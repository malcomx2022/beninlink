<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\Http\Requests\FrontWeb\SocialLink\StoreRequest;
use App\Repositories\FrontWeb\SocialLink\SocialLinkInterface;

class SocialLinkController extends Controller
{
    protected $repo;
    public function __construct(SocialLinkInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $socialLinks = $this->repo->get();
        return view('backend.front_web.social_link.index', compact('socialLinks'));
    }

    public function create()
    {
        return view('backend.front_web.social_link.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) :
            Toastr::success(__('levels.social_link_added'), __('message.success'));
            return redirect()->route('social.link.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }

    public function edit($id)
    {
        $socialLink  = $this->repo->getFind($id);
        return view('backend.front_web.social_link.edit', compact('socialLink'));
    }

    public function update(StoreRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.social_link_updated'), __('message.success'));
            return redirect()->route('social.link.index');
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
            Toastr::success(__('levels.social_link_deleted'), __('message.success'));
            return redirect()->back();
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        endif;
    }
}
