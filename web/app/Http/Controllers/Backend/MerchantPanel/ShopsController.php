<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantPanel\Shops\StoreRequest;
use App\Http\Requests\MerchantPanel\Shops\UpdateRequest;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use Illuminate\Support\Facades\Auth;
use Brian2694\Toastr\Facades\Toastr;
class ShopsController extends Controller
{
    protected $repo;
    public function __construct(ShopsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(){
        $singleMerchant = $this->repo->getMerchant(Auth::user()->id);
        $merchant_shops = $this->repo->all($singleMerchant->id);
        return view('backend.merchant_panel.shops.index',compact('merchant_shops','singleMerchant'));
    }

    //merchant shops create page
    public function create(){
        return view('backend.merchant_panel.shops.create');
    }

    //merchant shops store
    public function store(StoreRequest $request){
        if($this->repo->store(Auth::user()->merchant->id, $request)){
            Toastr::success(__('merchantshops.added_msg'),__('message.success'));
            return redirect()->route('merchant-panel.shops.index');
        }else{
            Toastr::error(__('merchantshops.error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }

    public function edit($id){ // shop id
        $shop = $this->repo->get($id);
        return view('backend.merchant_panel.shops.edit', compact('shop'));
    }

    public function update($id, UpdateRequest $request){
        if($this->repo->update($id, $request)){
            Toastr::success(__('merchantshops.update_msg'),__('message.success'));
            return redirect()->route('merchant-panel.shops.index');
        }else{
            Toastr::error(__('merchantshops.update_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }
    public function delete($id){
        $this->repo->delete($id);
        Toastr::success(__('merchantshops.delete_msg'),__('message.success'));
        return back();
    }
}
