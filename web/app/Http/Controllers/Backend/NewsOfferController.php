<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\NewsOffer\StoreNewsOfferRequest;
use App\Http\Requests\NewsOffer\UpdateNewsOfferRequest;
use App\Repositories\NewsOffer\NewsOfferInterface;
use Brian2694\Toastr\Facades\Toastr;
class NewsOfferController extends Controller
{
    protected $repo;
    public function __construct(NewsOfferInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $news_offers = $this->repo->all();
        return view('backend.news_offer.index',compact('news_offers'));
    }

    public function create()
    {
        return view('backend.news_offer.create');
    }

    public function store(StoreNewsOfferRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('news_offer.added_msg'),__('message.success'));
            return redirect()->route('news-offer.index');
        }else{
            Toastr::error(__('news_offer.error_msg'),__('message.success'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $news_offer = $this->repo->get($id);
        return view('backend.news_offer.edit',compact('news_offer'));
    }

    public function update($id,UpdateNewsOfferRequest $request)
    {

        if($this->repo->update($id, $request)){
            Toastr::success(__('news_offer.update_msg'),__('message.success'));
            return redirect()->route('news-offer.index');
        }else{
            Toastr::error(__('news_offer.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        if($this->repo->delete($id)){
            Toastr::success(__('news_offer.delete_msg'),__('message.success'));
            return redirect()->back();
        }
        else{
            Toastr::error(__('news_offer.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

}
