<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleMapSetting\StoreRequest;
use App\Repositories\GoogleMapSettings\GoogleMapSettingsInterface;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
class GoogleMapSettingsController extends Controller
{
    protected $repo;
    public function __construct(GoogleMapSettingsInterface $repo)
    {
        $this->repo    = $repo;
    }

    public function index()
    {
        $settings = $this->repo->all();
        return view('backend.setting.googlemap-setting.index',compact('settings'));
    }

    public function update(StoreRequest $request){
        $settings = $this->repo->update($request);
        Toastr::success(__('settings.save_change'),__('message.success'));
        return view('backend.setting.googlemap-setting.index',compact('settings'));
    }
}
