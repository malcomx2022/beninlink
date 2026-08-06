<?php

namespace App\Http\Controllers\Api\V10;


use App\Http\Controllers\Controller;
use App\Http\Resources\v10\SupportResource;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use App\Repositories\MerchantPanel\Support\SupportInterface;
use App\Http\Requests\Support\StoreRequest;
use Illuminate\Support\Facades\Validator;

class SupportController extends Controller
{
    use ApiReturnFormatTrait;
    protected $repo;
    public function __construct(SupportInterface $repo)
    {
        $this->repo = $repo;
    }
    public function index()
    {
        try {
            $supports = SupportResource::collection($this->repo->all());
            return $this->responseWithSuccess(__('support.supprot_list'), ['supports'=>$supports], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('support.supprot_list'), [], 500);

        }
    }


    public function create()
    {
        try {
            $departments = $this->repo->departments();
            return $this->responseWithSuccess(__('support.supprot_add'), ['departments'=>$departments], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('support.supprot_add'), [], 500);

        }
    }

    public function store(Request $request)
    {

        $validator = new StoreRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('support.title'), ['message' => $validator->errors()], 422);
        }
        if($this->repo->store($request)){
            return $this->responseWithSuccess(__('support.added_msg'), [], 200);
        }else{
            return $this->responseWithError(__('support.error_msg'), [], 500);
        }
    }

    public function edit($id)
    {
        $departments   = $this->repo->departments();
        return $this->responseWithSuccess(__('support.title'), ['support'=> new SupportResource($this->repo->get($id)),'departments'=>$departments], 200);

    }


    public function update(Request $request,$id)
    {

        $validator = new StoreRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('support.title'), ['message' => $validator->errors()], 422);
        }
        if($this->repo->update($id,$request)){
            return $this->responseWithSuccess(__('support.update_msg'), [], 200);
        }else{
            return $this->responseWithError(__('support.error_msg'), [], 500);
        }
    }


    public function destroy($id)
    {

        try {
            $this->repo->delete($id);
            return $this->responseWithSuccess(__('support.delete_msg'), [], 200);
        }catch (\Exception $exception) {
            return $this->responseWithError(__('support.error_msg'), [], 500);
        }
    }

    public function view($id){
        $chats         = $this->repo->chats($id);
        return $this->responseWithSuccess(__('support.title'), ['support'=> new SupportResource($this->repo->get($id)),'chats'=>$chats], 200);
    }

    public function supportReply(Request $request){
        $validator  = Validator::make($request->all(),[
            'message'   => 'required'
        ]);


        if ($validator->fails()) {
            return $this->responseWithError(__('support.reply_msg'), ['message' => $validator->errors()], 422);
        }
        if($this->repo->reply($request)){
            return $this->responseWithSuccess(__('support.reply_msg'), [], 200);
        }else{
            return $this->responseWithError(__('support.error_msg'), [], 500);
        }

    }
}
