<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantShop\StoreRequest;
use App\Http\Requests\MerchantShop\UpdateRequest;
use App\Repositories\MerchantShops\ShopsInterface;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Brian2694\Toastr\Facades\Toastr;
class MerchantShopsController extends Controller
{
    protected $repo;
    protected $repoMerchant;
    public function __construct(ShopsInterface $repo, MerchantInterface $repoMerchant )
    {

        $this->repo=$repo;
        $this->repoMerchant=$repoMerchant;
    }

    public function index($id){
        $singleMerchant = $this->repoMerchant->get($id);
        $merchant_shops = $this->repo->merchant_shops_get($id);
        if(blank($singleMerchant)){
            abort(404);
        }

        return view('backend.merchant.shops.index',compact('merchant_shops','singleMerchant'));
    }

    //merchant shops create page
    public function create($id){
        $merchant_id    = $id;
        $singleMerchant = $this->repoMerchant->get($id);
        if(blank($singleMerchant)){
            abort(404);
        }
        return view('backend.merchant.shops.create',compact('merchant_id','singleMerchant'));
    }

    //merchant shops store
    public function store(StoreRequest $request){

            if($this->repo->store($request)){
                Toastr::success(__('merchantshops.added_msg'));
                return redirect()->route('merchant.shops.index',$request->merchant_id);
            }else{
                Toastr::error(__('merchantshops.error_msg'),__('message.error'));
                return Redirect::back()->withInput();
            }
    }

    public function edit($id){

        $edit_shop      = $this->repo->get($id);
        $merchant_id    = $edit_shop->merchant_id;
        $singleMerchant = $this->repoMerchant->get($merchant_id);
        if(blank($singleMerchant) || blank($edit_shop)){
            abort(404);
        }
        return view('backend.merchant.shops.edit', compact('edit_shop','merchant_id','singleMerchant'));
    }

    public function update(UpdateRequest $request){

        if($this->repo->update($request)){
            Toastr::success(__('merchantshops.update_msg'),__('message.success'));
            return redirect()->route('merchant.shops.index',$request->merchant_id);
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

    public function defaultShop($merchant_id,$id)
    {
        $this->repo->defaultShop($merchant_id,$id);
        Toastr::success(__('merchantshops.update_msg'),__('message.success'));
        return back();
    }
}
