<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\SmsSendSetting;
use App\Repositories\SmsSendSetting\SmsSendSettingInterface;
use Illuminate\Http\Request;

class SmsSendSettingsController extends Controller
{
    protected $repo;
    public function __construct(SmsSendSettingInterface $repo)
    {
        $this->repo  = $repo;
    }
    public function index(){
        $smsSendSettings = $this->repo->all();
        return view('backend.setting.sms-send.index',compact('smsSendSettings'));
    }
    public function status(Request $request){
        $smsSendSetting             =  SmsSendSetting::companywise()->where(['id'=>$request->id])->first();
        // S52 — le perimetre etait bon : hors societe, la requete rend `null` et
        // aucune bascule n'a lieu. Mais l'affectation juste en dessous
        // dereferencait ce `null` et rendait **500** au lieu d'un refus propre.
        // Meme famille que S15 et S30 : un refus se dit, il ne plante pas.
        abort_if(blank($smsSendSetting), 404);
        if(Status::ACTIVE == $request->status){
            $smsSendSetting->status      =  Status::INACTIVE;
        }else {
            $smsSendSetting->status      =  Status::ACTIVE;
        }
        $smsSendSetting->save();
        return $smsSendSetting;
    }
}
