<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPanel\PaymentRequest\StoreRequest;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use App\Repositories\MerchantManage\Payment\PaymentInterface;
use App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\ApprovalStatus;
use Brian2694\Toastr\Facades\Toastr;
class PaymentRequestController extends Controller
{
    protected $repo;
    protected $merchantPayments;
    public function __construct(PaymentInterface $merchantPayments,PaymentRequestInterface $repo)
    {
        $this->merchantPayments=$merchantPayments;
        $this->repo=$repo;
    }
    public function index(){
        $payments=$this->merchantPayments->getSingleMerchantPayments(Auth::user()->merchant->id);
        return view('backend.merchant_panel.payment_request.index',compact('payments'));
    }

    public function create(){
        $merchant        = Merchant::find(Auth::user()->merchant->id);
        $merchantaccounts=MerchantPayment::where('merchant_id',$merchant->id)->get();
        return view('backend.merchant_panel.payment_request.create',compact('merchantaccounts','merchant'));
    }

    public function store(StoreRequest $request){
        $account = Auth::user()->merchant;
        $balance = (double) $account->current_balance;
        if((double) $request->amount > $balance){
            Toastr::warning(__('merchantmanage.not_enough_balance'),__('message.warning'));
            return back();
        }

        if($this->repo->store($request)){
            Toastr::success(__('paymentrequest.added_msg'),__('message.success'));
            return redirect()->route('merchant-panel.payment-request.index');
        }else{
            Toastr::error(__('paymentrequest.error_msg'),__('message.error'));
           return back();
        }
    }

    public function edit($id){
            $singlePayment=$this->repo->get($id);
            abort_if(blank($singlePayment), 404); // S7
            $merchantaccounts=MerchantPayment::where('merchant_id',Auth::user()->merchant->id)->get();
            return view('backend.merchant_panel.payment_request.edit',compact('singlePayment','merchantaccounts'));
    }

    public function update(StoreRequest $request){

        // S83 — le socle relisait la demande par l'identifiant du MARCHAND
        // (`get(Auth::user()->merchant->id)`), pas par celui de la demande que
        // le formulaire envoie en champ cache `id`. Le depot cherchait donc,
        // parmi les demandes du marchand, celle dont l'identifiant vaut le sien :
        // le plus souvent rien (et `$payment->status` dereferencait un `null`,
        // page d'erreur) ; par coincidence, une AUTRE de ses demandes, dont le
        // STATUT decidait alors si la modification passait — une demande deja
        // traitee redevenait modifiable. Le depot `update()` lisait deja
        // `$request->id` et le gardait (S7, S81) : l'ecriture visait la bonne
        // ligne, c'est la decision qui se prenait sur la mauvaise. Meme forme
        // que `delete()` ci-dessous et que le jumeau de l'API.
        $payment = $this->repo->get($request->id);
        abort_if(blank($payment), 404); // S7
        if($payment->status == ApprovalStatus::PENDING){

            $account=Auth::user()->merchant;
            $balance=(double) $account->current_balance;
            if((double) $request->amount > $balance){
                Toastr::warning(__('merchantmanage.not_enough_balance'),__('message.warning'));
                return back();
            }

            if($this->repo->update($request)){
                Toastr::success(__('paymentrequest.update_msg'),__('message.success'));
                return redirect()->route('merchant-panel.payment-request.index');
            }else{
                Toastr::error(__('paymentrequest.error_msg'),__('message.error'));
            return back();
            }

        }
        else{
            Toastr::error(__('paymentrequest.error_msg'),__('message.error'));
        }
        return back();
    }

    public function delete($id){
        $payment = $this->repo->get($id);
        abort_if(blank($payment), 404); // S7
        if($payment->status == ApprovalStatus::PENDING){
            $this->repo->delete($id);
            Toastr::success(__('paymentrequest.deleted_msg'),__('message.success'));
        }
        else{
            Toastr::error(__('paymentrequest.error_msg'),__('message.error'));
        }
        return back();
    }

}
