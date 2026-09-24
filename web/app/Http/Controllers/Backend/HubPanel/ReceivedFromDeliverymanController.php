<?php

namespace App\Http\Controllers\Backend\HubPanel;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashReceivedFromDeliveryman\StoreRequest;
use App\Http\Requests\CashReceivedFromDeliveryman\UpdateRequest;
use App\Models\Backend\DeliveryMan;
use App\Models\CashReceivedFromDeliveryman;
use App\Repositories\Account\AccountInterface;
use App\Repositories\CashReceivedFromDeliveryman\ReceivedInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Brian2694\Toastr\Facades\Toastr;
class ReceivedFromDeliverymanController extends Controller
{

    protected $account;
    protected $repo;
    public function __construct(ReceivedInterface $repo, AccountInterface $account)
    {
        $this->account  = $account;
        $this->repo     = $repo;
    }
    public function index(){
        if(Auth::user()->hub_id):
        $hubStatements   = $this->repo->all();
        return view('backend.hub_panel.cash_received_from_deliveryman.index',compact('hubStatements'));
        else:
            Toastr::error(__('account.error_msg'),__('message.success'));
            return redirect()->back();
        endif;
    }

    public function create(){
        if(Auth::user()->hub_id):
            $accounts     = $this->account->useraccount(Auth::user()->id);

            return view('backend.hub_panel.cash_received_from_deliveryman.create',compact('accounts'));
        else:
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }

    public function store(StoreRequest $request){
        // S64 — meme forme qu'en S51 et S59 : la lecture PRECEDE le depot, donc
        // elle renseignait avant que la garde de S48 ne refuse l'ecriture. Les
        // deux lignes suivantes lisent `current_balance` et rendent la reponse
        // differente selon lui : un ORACLE sur le solde d'un livreur d'une AUTRE
        // societe. Et hors perimetre, `find()` rend `null` (famille S15).
        $deliveryman   = DeliveryMan::companywise()->find($request->delivery_man_id);

        if(blank($deliveryman)):
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back()->withInput();
        endif;

        if($deliveryman->current_balance > -$request->amount || $deliveryman->current_balance == 0):
            Toastr::warning(__('account.not_enough_balance'),__('message.warning'));
            return redirect()->back()->withInput();
        endif;

        if($this->repo->store($request)):
            Toastr::success(__('account.deliveryman_added_msg'),__('message.success'));
            return redirect()->route('cash.received.deliveryman.index');
        else:
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }

    public function edit($id){
        if(Auth::user()->hub_id):
            $accounts          = $this->account->useraccount(Auth::user()->id);
            $singleCashAmount  = $this->repo->get($id);
            return view('backend.hub_panel.cash_received_from_deliveryman.edit',compact('accounts','singleCashAmount'));
        else:
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }

    public function update(UpdateRequest $request){

        // S65 — la REPRISE portait le meme oracle que le depot (corrige en S64),
        // et une seconde lecture nue : la remise elle-meme. Deux identifiants,
        // deux portees ; mon correctif de S64 n'avait attrape que `store()`.
        $deliveryman   = DeliveryMan::companywise()->find($request->delivery_man_id);
        $cashReceived  = CashReceivedFromDeliveryman::companywise()->find($request->id);

        if(blank($deliveryman) || blank($cashReceived)):
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back()->withInput();
        endif;

        $cashReceivedAmount = $deliveryman->current_balance - $cashReceived->amount;

        if($cashReceivedAmount > -$request->amount || $cashReceivedAmount > 0):
            Toastr::warning(__('account.not_enough_balance'),__('message.warning'));
            return redirect()->back()->withInput();
        endif;


        if($this->repo->update($request)):
            Toastr::success(__('account.deliveryman_update_msg'),__('message.success'));
            return redirect()->route('cash.received.deliveryman.index');
        else:
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }

    public function delete($id){
        if(Auth::user()->hub_id):
            if($this->repo->delete($id)):
                Toastr::success(__('account.deliveryman_delete_msg'),__('message.success'));
                return redirect()->back();
            else:
                Toastr::error(__('account.error_msg'),__('message.error'));
                return redirect()->back();
            endif;
        else:
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }
}
