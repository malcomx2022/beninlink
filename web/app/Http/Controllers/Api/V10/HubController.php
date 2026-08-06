<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\HubResource;
use App\Repositories\Hub\HubInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

class HubController extends Controller
{
    use ApiReturnFormatTrait;
    protected $repo;
    public function __construct(HubInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {

        try {
            $hubs = HubResource::collection($this->repo->all());
            return $this->responseWithSuccess(__('hub.title'), ['hubs'=>$hubs], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('hub.title'), [], 500);

        }
    }

}
