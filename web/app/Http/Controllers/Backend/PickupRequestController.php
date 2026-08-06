<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\MerchantPanel\PickupRequest\PickupRequestInterface;
use Illuminate\Http\Request;

class PickupRequestController extends Controller
{
    protected $repo;
    public function __construct(PickupRequestInterface $repo){
        $this->repo = $repo;
    }
    public function regular(){
        $regulars = $this->repo->getRegular();
        return view('backend.pickup_request.regular',compact('regulars'));
    }
    public function express(){
        $expresses  = $this->repo->getExpress();
        return view('backend.pickup_request.express',compact('expresses'));
    }
}
