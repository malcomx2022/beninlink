<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontWeb\Blog\StoreRequest;
use App\Http\Requests\FrontWeb\Blog\UpdateRequest;
use App\Repositories\FrontWeb\Blogs\BlogsInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class BlogController extends Controller
{

    protected $repo;
    public function __construct(BlogsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $blogs = $this->repo->get();
        return view('backend.front_web.blogs.index', compact('blogs'));
    }

    public function create()
    {
        return view('backend.front_web.blogs.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) :
            Toastr::success(__('levels.blog_added'), __('message.success'));
            return redirect()->route('blogs.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }

    public function edit($id)
    {
        $blog  = $this->repo->getFind($id);
        return view('backend.front_web.blogs.edit', compact('blog'));
    }

    public function update(UpdateRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.blog_updated'), __('message.success'));
            return redirect()->route('blogs.index');
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
            Toastr::success(__('levels.blog_deleted'), __('message.success'));
            return redirect()->back();
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        endif;
    }
}
