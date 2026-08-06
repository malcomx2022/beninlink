<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\PaymentAccountResource;
use App\Repositories\MerchantPanel\PaymentAccount\PaymentAccountInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantPanel\PaymentAccount\StoreRequest;
use App\Http\Requests\MerchantPanel\PaymentAccount\UpdateRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class PaymentAccountController extends Controller
{
    use ApiReturnFormatTrait;
    protected $repo;
    public function __construct(PaymentAccountInterface $repo)
    {
        $this->repo = $repo;
    }
    public function index(){

        try {
            $accounts = PaymentAccountResource::collection($this->repo->all());
            return $this->responseWithSuccess(__('account.title'), ['accounts'=>$accounts], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('account.title'), [], 500);

        }
    }

    public function store(Request $request){
        if(isset($request->payment_method)){
            $validator = new StoreRequest();
            $validator = Validator::make($request->all(), $validator->rules());

            if ($validator->fails()) {
                return $this->responseWithError(__('account.title'), ['message' => $validator->errors()], 422);
            }

            if($this->repo->store($request)){
                return $this->responseWithSuccess(__('account.added_msg'), [], 200);
            }else{
                return $this->responseWithError(__('account.error_msg'), [], 500);
            }
        } else {
            return $this->responseWithError(__('account.error_msg'), [], 500);
        }


    }

    public function edit($id){
        return $this->responseWithSuccess(__('account.title'), ['account'=> new PaymentAccountResource($this->repo->edit($id))], 200);
    }


    public function update(Request $request){

        if(isset($request->payment_method)) {

            $validator = new UpdateRequest();
            $validator = Validator::make($request->all(), $validator->rules());

            if ($validator->fails()) {
                return $this->responseWithError(__('account.update_account'), ['message' => $validator->errors()], 422);
            }

            if ($this->repo->update($request)) {
                return $this->responseWithSuccess(__('account.update_msg'), [], 200);
            } else {
                return $this->responseWithError(__('account.error_msg'), [], 500);
            }
        }

    }

    public function delete($id){
        try {
            $this->repo->delete($id);
            return $this->responseWithSuccess(__('account.delete_msg'), [], 200);
        }catch (\Exception $exception) {
            return $this->responseWithError(__('account.error_msg'), [], 500);

        }

    }

}
