<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Repositories\FrontWeb\Section\SectionInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    protected $repo;
    public function __construct(SectionInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $sections = $this->repo->all();
        return view('backend.front_web.section.index', compact('sections'));
    }

    public function edit($type)
    {
        $section     = $this->repo->getFind($type);
        $section_type = $this->repo->sectionType($type);
        return view('backend.front_web.section.edit', compact('type', 'section', 'section_type'));
    }

    public function update(Request $request, $type)
    {

        if ($this->repo->update($type, $request)) :
            Toastr::success(__('levels.section_updated'), __('message.success'));
            return redirect()->route('section.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }
}
