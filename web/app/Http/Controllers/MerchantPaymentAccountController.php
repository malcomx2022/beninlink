<?php

namespace App\Http\Controllers;

use App\Http\Requests\Merchantpayment\StoreBankRequest;
use App\Http\Requests\Merchantpayment\StoreMobileRequest;
use Illuminate\Http\Request;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantPayment\PaymentInterface;
use Illuminate\Support\Facades\Redirect;
use Brian2694\Toastr\Facades\Toastr;
class MerchantPaymentAccountController extends Controller
{
    protected $repo;
    protected $payRepo;
    public function __construct(MerchantInterface $repo,PaymentInterface $payRepo)
    {
        $this->repo    = $repo;
        $this->payRepo = $payRepo;
    }
    public function index($id){
        $singleMerchant = $this->repo->get($id);
        // S34 — aucun garde ici : hors perimetre l'ecran s'affichait avec un
        // marchand `null` et, avant le correctif du depot, la fiche de versement
        // d'un autre transporteur dans le tableau.
        abort_if(blank($singleMerchant), 404);

        $payments       = $this->payRepo->get($id);

        return view('backend.merchant.payment.index',compact('singleMerchant','payments'));
    }
    public function paymentAdd($id){
        $singleMerchant = $this->repo->get($id);
        // S34 — sans garde, le formulaire s'ouvrait avec le `merchant_id` d'une
        // autre societe pre-rempli dans son champ cache.
        abort_if(blank($singleMerchant), 404);

        $merchant_id    = $id;
        return view('backend.merchant.payment.add_payment',compact('singleMerchant','merchant_id' ));
    }
    public function paymentEdit($mid,$id){
        $singleMerchant = $this->repo->get($mid);
        $paymentInfo    = $this->payRepo->edit($id);
        // S34 — les DEUX doivent etre a nous : le marchand de l'URL et la ligne
        // de versement. Le socle n'en verifiait aucun.
        abort_if(blank($singleMerchant) || blank($paymentInfo), 404);

        $merchant_id    = $mid;
        return view('backend.merchant.payment.edit_payment',compact('singleMerchant','merchant_id','paymentInfo'));
    }

    public function paymentChange(Request $request){
        $payment_method = $request->payment_method;
        // S34 — `->id` sur `null` rendait 500 hors perimetre.
        $merchant       = $this->repo->get($request->merchant_id);
        abort_if(blank($merchant), 404);
        $merchant_id    = $merchant->id;
        $editid         = $request->editid;
        if($request->payment_method == 'bank'){
            return view('backend.merchant.payment.bank',compact('payment_method','merchant_id' ,'editid'));
        }elseif($request->payment_method == 'mobile'){
            return view('backend.merchant.payment.mobile',compact('payment_method','merchant_id','editid'));
        }elseif($request->payment_method == 'cash'){
            return view('backend.merchant.payment.cash',compact('payment_method','merchant_id','editid'));
        }
    }

    // bank payment information store
    public function bankStore(StoreBankRequest $request){
        if($this->payRepo->bankstore($request)){
            if($request->editid !==null){
                Toastr::success(__('merchant.payment_update_msg'),__('message.success'));
            }else{
                Toastr::success(__('merchant.payment_added_msg'),__('message.success'));
            }
            return redirect()->route('merchant.paymentaccount.index',$request->merchant_id);
        }else{
            Toastr::error(__('merchant.payment_error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }



    //mobile payment information store
    public function mobileStore(StoreMobileRequest $request){
        if($this->payRepo->mobilestore($request)){
            if($request->editid !==null){
                Toastr::success(__('merchant.payment_update_msg'),__('message.success'));
            }else{
                Toastr::success(__('merchant.payment_added_msg'),__('message.success'));
            }
            return redirect()->route('merchant.paymentaccount.index',$request->merchant_id);
        }else{
            Toastr::error(__('merchant.payment_error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }


    //update

    public function bankUpdate(StoreBankRequest $request){
        if($this->payRepo->bankupdate($request)){
            Toastr::success(__('merchant.payment_update_msg'),__('message.success'));
            return redirect()->route('merchant.paymentaccount.index',$request->merchant_id);
        }else{
            Toastr::error(__('merchant.payment_error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }
    public function mobileUpdate(StoreMobileRequest $request){
        if($this->payRepo->mobileupdate($request)){
            Toastr::success(__('merchant.payment_update_msg'),__('message.success'));
            return redirect()->route('merchant.paymentaccount.index',$request->merchant_id);
        }else{
            Toastr::error(__('merchant.payment_error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }
    public function destroy($id){
        // S34 — le depot refuse la ligne d'une autre societe ; l'ecran annoncait
        // quand meme la suppression. On repond 404.
        abort_unless($this->payRepo->delete($id), 404);

        Toastr::success(__('merchant.payment_account_delete_msg'),__('message.success'));
        return back();
    }
}
