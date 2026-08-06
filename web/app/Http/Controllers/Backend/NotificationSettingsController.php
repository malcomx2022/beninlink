<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationSetting\StoreRequest;
use Illuminate\Http\Request;
use App\Repositories\NotificationSettings\NotificationSettingsInterface;
use Brian2694\Toastr\Facades\Toastr;
class NotificationSettingsController extends Controller
{
    protected $repo;
    public function __construct(NotificationSettingsInterface $repo)
    {
        $this->repo    = $repo;
    }

    public function index()
    {
        $settings = $this->repo->all();
        return view('backend.setting.notification-setting.index',compact('settings'));
    }

    public function update(StoreRequest $request){
        $settings = $this->repo->update($request);
        Toastr::success(__('settings.save_change'),__('message.success'));
        return view('backend.setting.notification-setting.index',compact('settings'));
    }
}
