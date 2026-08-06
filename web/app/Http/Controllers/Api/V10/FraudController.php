<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\FraudResource;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantPanel\Fraud\StoreRequest;
use App\Http\Requests\MerchantPanel\Fraud\UpdateRequest;
use App\Repositories\MerchantPanel\Fraud\FraudInterface;
use Illuminate\Support\Facades\Validator;

class FraudController extends Controller
{
    use ApiReturnFormatTrait;
    protected $repo;
    public function __construct(FraudInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {

        try {
            $frauds = FraudResource::collection($this->repo->all());
            return $this->responseWithSuccess(__('fraud.title'), ['frauds'=>$frauds], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('fraud.title'), [], 500);

        }
    }


    public function check(Request $request)
    {
        $frauds = $this->repo->check($request);
        try {
            $frauds = FraudResource::collection($this->repo->check($request));
            return $this->responseWithSuccess(__('fraud.title'), ['frauds'=>$frauds], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('fraud.title'), [], 500);

        }
    }


    public function store(Request $request)
    {
        $validator = new StoreRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('fraud.title'), ['message' => $validator->errors()], 422);
        }
        if($this->repo->store($request)){
            return $this->responseWithSuccess(__('fraud.added_msg'), [], 200);
        }else{
            return $this->responseWithError(__('fraud.error_msg'), [], 500);
        }
    }

    public function edit($id)
    {
        return $this->responseWithSuccess(__('fraud.edit_fraud'), ['fraud'=> new FraudResource($this->repo->get($id))], 200);

    }

    public function update(Request $request, $id)
    {
        $validator = new UpdateRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('fraud.title'), ['message' => $validator->errors()], 422);
        }

        if ($this->repo->update($id, $request)) {
            return $this->responseWithSuccess(__('fraud.update_msg'), [], 200);
        } else {
            return $this->responseWithError(__('fraud.error_msg'), [], 500);
        }

    }

    public function destroy($id)
    {

        try {
            $this->repo->delete($id);
            return $this->responseWithSuccess(__('fraud.delete_msg'), [], 200);
        }catch (\Exception $exception) {
            return $this->responseWithError(__('fraud.error_msg'), [], 500);
        }
    }
}
