<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\SmsSetting\StoreRequest;
use App\Models\Backend\SmsSetting;
use App\Models\Config;
use App\Repositories\SmsSetting\SmsSettingInterface;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
class SmsSettingsController extends Controller
{
    protected $repo;
    public function __construct(SmsSettingInterface $repo)
    {
        $this->repo  = $repo;
    }
    public function index(){
        return view('backend.setting.sms.index');
    }

    public function update(StoreRequest $request,$smsMethod){
        if($this->repo->update($smsMethod,$request)):
            Toastr::success(__('smsSettings.update_msg'),__('message.success'));
            return redirect()->route('sms-settings.index');
        else:
            Toastr::error(__('smsSettings.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }



}
