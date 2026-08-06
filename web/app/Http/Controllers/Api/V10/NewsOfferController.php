<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\NewsOffersResource;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use App\Models\Backend\NewsOffer;
use App\Enums\Status;

class NewsOfferController extends Controller
{
    use ApiReturnFormatTrait;
    public function index(){

        try {
            $news_offers = NewsOffer::where('status',Status::ACTIVE)->with('upload')->orderByDesc('id')->get();
            return $this->responseWithSuccess(__('news_offer.title'), ['newsOffers'=> NewsOffersResource::collection($news_offers)], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('news_offer.title'), [], 500);

        }
    }
}
