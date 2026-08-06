<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\ShopResource;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantPanel\Shops\StoreRequest;
use App\Http\Requests\MerchantPanel\Shops\UpdateRequest;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShopsController extends Controller
{
    use ApiReturnFormatTrait;
    protected $repo;
    public function __construct(ShopsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(){

        try {
            $singleMerchant = $this->repo->getMerchant(auth()->user()->id);
            $merchant_shops = ShopResource::collection($this->repo->all($singleMerchant->id));
            return $this->responseWithSuccess(__('merchantshops.title'), ['shops'=>$merchant_shops], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('merchantshops.title'), [], 500);

        }
    }


    public function store(Request $request){
        $validator = new StoreRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('merchantshops.create_shops'), ['message' => $validator->errors()], 422);
        }

        if($this->repo->store(auth()->user()->merchant->id, $request)){
            return $this->responseWithSuccess(__('merchantshops.added_msg'), [], 200);
        }else{
            return $this->responseWithError(__('merchantshops.error_msg'), [], 500);
        }
    }
    public function edit($id){
        $shop = $this->repo->get($id);
        return $this->responseWithSuccess(__('merchantshops.title'), ['shop'=> new ShopResource($shop)], 200);
    }

    public function update($id, Request $request){

        $validator = new UpdateRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('merchantshops.update_shops'), ['message' => $validator->errors()], 422);
        }

        if($this->repo->update($id, $request)){
            return $this->responseWithSuccess(__('merchantshops.update_msg'), [], 200);
        }else{
            return $this->responseWithError(__('merchantshops.error_msg'), [], 500);
        }
    }
    public function delete($id){

        try {
            $this->repo->delete($id);
            return $this->responseWithSuccess(__('merchantshops.delete_msg'), [], 200);
        }catch (\Exception $exception) {
            return $this->responseWithError(__('merchantshops.error_msg'), [], 500);

        }
    }

}
