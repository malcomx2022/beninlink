<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Repositories\MerchantPanel\Support\SupportInterface;
use App\Http\Requests\Support\StoreRequest;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;
class SupportController extends Controller
{
    protected $repo;

    public function __construct(SupportInterface $repo)
    {
        $this->repo = $repo;
    }
    public function index()
    {
        $supports = $this->repo->all();
        return view('backend.merchant_panel.support.index',compact('supports'));
    }

    public function create()
    {
        $departments = $this->repo->departments(); 
        return view('backend.merchant_panel.support.create', compact('departments'));
    }
 
    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('support.added_msg'),__('message.success'));
            return redirect()->route('merchant-panel.support.index');
        }
        else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
        }
    }

    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        $departments   = $this->repo->departments();
        $singleSupport = $this->repo->get($id);
        return view('backend.merchant_panel.support.edit',compact('departments','singleSupport'));
    }


    public function update(StoreRequest $request)
    {
        if($this->repo->update($request->id,$request)){
            Toastr::success(__('support.update_msg'),__('message.success'));
            return redirect()->route('merchant-panel.support.index');
        }
        else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
        }
    }

    public function destroy($id)
    {
        if($this->repo->delete($id)){
            Toastr::success(__('support.delete_msg'),__('message.success'));
            return redirect()->route('merchant-panel.support.index');
        }
        else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function view($id){
        $singleSupport = $this->repo->get($id);
        $chats         = $this->repo->chats($id);
        return view('backend.merchant_panel.support.view',compact('singleSupport','chats'));
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
            return redirect()->route('merchant-panel.support.view',$request->support_id);
        }else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
        }
    }
}
