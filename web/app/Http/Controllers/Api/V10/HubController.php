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
            // S78 : le dépôt pagine (10) pour le back-office ; l'API le disait sans le dire.
            $page = $this->repo->all();
            return $this->responseWithPage(__('hub.title'), ['hubs'=>HubResource::collection($page)], $page);
        }catch (\Exception $exception){
            return $this->responseWithError(__('hub.title'), [], 500);

        }
    }

}
