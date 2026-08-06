<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontWeb\Pages\UpdateRequest;
use App\Repositories\FrontWeb\Pages\PagesInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class PageController extends Controller
{
    protected $repo;
    public function __construct(PagesInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $pages = $this->repo->all();
        return view('backend.front_web.pages.index', compact('pages'));
    }

    public function edit($id)
    {
        $page  = $this->repo->getFind($id);
        return view('backend.front_web.pages.edit', compact('page'));
    }

    public function update(UpdateRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.page_updated'), __('message.success'));
            return redirect()->route('pages.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }
}
