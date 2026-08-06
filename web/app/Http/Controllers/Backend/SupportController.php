<?php

namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreRequest;
use App\Models\Backend\SupportChat;
use App\Models\User;

use App\Repositories\Support\SupportInterface;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;
class SupportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $repo;

    public function __construct(SupportInterface $repo)
    {
        $this->repo = $repo;
    }
    public function index()
    {
   
        $supports = $this->repo->all();
        return view('backend.support.index',compact('supports'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $departments = $this->repo->departments();
        return view('backend.support.create', compact('departments'));
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
            Toastr::success(__('support.added_msg'),__('message.success'));
            return redirect()->route('support.index');
        }else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
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
        $departments   = $this->repo->departments();
        $singleSupport = $this->repo->get($id);
        return view('backend.support.edit',compact('departments','singleSupport'));
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

        if($this->repo->update($request->id,$request)){
            Toastr::success(__('support.update_msg'),__('message.success'));
            return redirect()->route('support.index');
        }else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

     public function view($id){
        $singleSupport = $this->repo->get($id);
        $chats = $this->repo->chats($id);
        return view('backend.support.view',compact('singleSupport','chats'));
     }

     public function supportReply(Request $request){
        $validator  = Validator::make($request->all(),[
            'message'   => 'required'
        ]);
        if($validator->fails()):
            return redirect()->back()->withErrors($validator)->withInput();
        endif;
        if($this->repo->reply($request)){
            Toastr::success(__('support.reply_msg'),__('message.success'));
            return redirect()->route('support.view',$request->support_id);
        }else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
        }

     }
    public function destroy($id)
    {
        if($this->repo->delete($id)):
            Toastr::success(__('support.delete_msg'),__('message.success'));
            return redirect()->route('support.index');
        else:
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }


    public function statusUpdate(Request $request,$id){
        if($this->repo->statusUpdate($id,$request)):
            Toastr::success('Status updated successfully.',__('message.success'));
            return redirect()->route('support.index');
        else:
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }
 

}
