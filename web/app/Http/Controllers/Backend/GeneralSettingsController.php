<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\Currency\CurrencyInterface;
use Illuminate\Http\Request;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use Brian2694\Toastr\Facades\Toastr;
class GeneralSettingsController extends Controller
{
    protected $repo,$currency;
    public function __construct(GeneralSettingsInterface $repo, CurrencyInterface $currency)
    {
        $this->repo     = $repo;
        $this->currency = $currency;
    }

    public function index()
    {
        $settings = $this->repo->all();
        $currencies = $this->currency->getActive();
        return view('backend.general_settings.index',compact('settings','currencies'));
    }

    public function update(Request $request){
        if(settings()->id == 1):
            if(env('DEMO')):
                Toastr::error('Update system is disable for the demo mode.',__('message.error'));
                return redirect()->back();
            endif;
        endif;
        $settings = $this->repo->update($request);
        Toastr::success(__('settings.save_change'),__('message.success'));
        return redirect()->route('general-settings.index');
    }
}
