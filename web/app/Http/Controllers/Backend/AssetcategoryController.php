<?php

namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetCategory\StoreRequest;
use App\Repositories\AssetCategory\AssetCategoryInterface;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class AssetcategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $repo;

    public function __construct(AssetcategoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $assetcategorys = $this->repo->all();
        return view('backend.assetcategory.index',compact('assetcategorys'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.assetcategory.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success('Assetcategory successfully added.',__('message.success'));
            return redirect()->route('asset-category.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $assetcategory = $this->repo->get($id);
        return view('backend.assetcategory.edit',compact('assetcategory'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreRequest $request)
    {
        if($this->repo->update($request)){
            Toastr::success('Assetcategory successfully updated.',__('message.success'));
            return redirect()->route('asset-category.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Assetcategory successfully deleted.',__('message.success'));
        return back();
    }
}
