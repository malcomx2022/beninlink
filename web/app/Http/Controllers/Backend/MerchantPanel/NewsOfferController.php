<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Backend\NewsOffer;
use App\Enums\Status;

class NewsOfferController extends Controller
{
    public function index(){
        $news_offers = NewsOffer::companywise()->where('status',Status::ACTIVE)->with('upload')->orderByDesc('id')->paginate(10);
        return view('backend.merchant_panel.news_offer.index',compact('news_offers'));
    }
}
