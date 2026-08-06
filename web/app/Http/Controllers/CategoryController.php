<?php

namespace App\Http\Controllers;


use App\Models\Categorys;
use Illuminate\Support\Str;
use DB;
Use Alert;
use Brian2694\Toastr\Facades\Toastr;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $categorys = Categorys::all();
        return view('backend.category.index',compact('categorys'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.category.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'   => 'required',

        ]);
        $category = new Categorys();

        $category->name        = $request->name;
        $category->description = $request->description;

        if ($request->slug != null) {
            $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
        }
        else {
            $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)).'-'.Str::random(5);
        }

        $category->save();
        if($category){
          Toastr::success('Success Title',__('message.success'));
            return redirect('category/index')->with('success','Catagory Insert Successfully!');
        }else{
            return redirect()->back()->with('danger','Oparation Failds!');
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
        $editCatagory = Categorys::find($id);
        return view('backend.category.edit',compact('editCatagory'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'name'   => 'required',
        ]);

        $update = Categorys::find($request->id);
        $update->name = $request->name;
        $update->description       = $request->description;

        if ($request->slug != null) {
            $update->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
        }
        else {
            $update->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)).'-'.Str::random(5);
        }
        $update->save();
        if($update){
            return redirect('category/index')->with('success','Catagory Update Successfully!');
        }else{
            return redirect()->back()->with('danger','Oparation Failds!');
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
        $delete = Categorys::destroy($id);
        if($delete){
            return redirect()->back()->with('success','Catagory Delete Successfully!');
        }else{
            return redirect()->back()->with('danger','Catagory Delete Faild!');
        }
    }
}
