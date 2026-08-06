<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\StoreRequest;
use App\Repositories\Currency\CurrencyInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{

    protected $repo;
    public function __construct(CurrencyInterface $repo)
    {
        $this->repo = $repo;
    }
    public function index(){
        $currencies = $this->repo->get();
        return view('backend.setting.currency.index',compact('currencies'));
    }

    public function create(){
        return view('backend.setting.currency.create');
    }
    public function store(StoreRequest $request){
        if($this->repo->store($request)):
            Toastr::success(__('settings.currency_added'),__('message.success'));
            return redirect()->route('currency.index');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back()->withInput();
        endif;
    }
    public function edit($id){
        $currency  = $this->repo->getFind($id);
        return view('backend.setting.currency.edit',compact('currency'));
    }
    public function update(StoreRequest $request){
        if($this->repo->update($request)):
            Toastr::success(__('settings.currency_updated'),__('message.success'));
            return redirect()->route('currency.index');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back()->withInput();
        endif;
    }
    public function delete($id){
        if($this->repo->delete($id)):
            Toastr::success(__('settings.currency_deleted'),__('message.success'));
            return redirect()->back();
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }

}
