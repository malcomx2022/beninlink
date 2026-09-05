<?php 
namespace App\Repositories\Wallet;

use App\Enums\PayoutSetup;
use App\Enums\UserType;
use App\Enums\Wallet\WalletPaymentMethod;
use App\Enums\Wallet\WalletStatus;
use App\Enums\Wallet\WalletType;
use App\Http\Services\SmsService;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\Wallet;
use App\Repositories\Wallet\WalletInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class WalletRepository implements WalletInterface{
    public function get($request=null){
        return Wallet::companywise()->where(function($query)use($request){
          
            if(Auth::user()->user_type == UserType::MERCHANT):
                $query->where('user_id',Auth::user()->id);
            endif;

            if(!empty($request->date)) {
                $date = explode('To', $request->date);
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                $query->whereBetween('updated_at',[$from,$to]);
            }

            if(!empty($request->merchant_id)):
                $query->where('merchant_id',$request->merchant_id);
            endif;
            if(!empty($request->status)):
                $query->where('status',$request->status);
            endif;
            if(!empty($request->search)):
                $query->where('transaction_id','like','%'.$request->search.'%');
                $query->orWhere(function($query)use($request){
                    $query->whereHas('merchant',function($query)use($request){
                        $query->where('business_name','like','%'.$request->search.'%'); 
                    });
                    $query->orWhereHas('user',function($query)use($request){
                        $query->where('name','like','%'.$request->search.'%'); 
                        $query->orWhere('email','like','%'.$request->search.'%'); 
                        $query->orWhere('mobile','like','%'.$request->search.'%'); 
                    });
                });
            endif; 
        })->orderByDesc('id')->paginate(10);
    }


    public function recharges($request=null){
        return Wallet::companywise()->where(function($query)use($request){
          
            $query->where('type',WalletType::INCOME);  
            if(Auth::user()->user_type == UserType::MERCHANT):
                $query->where('user_id',Auth::user()->id);
            endif;

            if(!empty($request->date)) {
                $date = explode('To', $request->date);
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                $query->whereBetween('updated_at',[$from,$to]);
            }

            if(!empty($request->merchant_id)):
                $query->where('merchant_id',$request->merchant_id);
            endif;
            if(!empty($request->status)):
                $query->where('status',$request->status);
            endif;
            if(!empty($request->search)):
                $query->where('transaction_id','like','%'.$request->search.'%');
                $query->orWhere(function($query)use($request){
                    $query->whereHas('merchant',function($query)use($request){
                        $query->where('business_name','like','%'.$request->search.'%'); 
                    });
                    $query->orWhereHas('user',function($query)use($request){
                        $query->where('name','like','%'.$request->search.'%'); 
                        $query->orWhere('email','like','%'.$request->search.'%'); 
                        $query->orWhere('mobile','like','%'.$request->search.'%'); 
                    });
                });
            endif; 
        })->orderByDesc('id')->paginate(10,'*','recharge_page');
    }
 
    public function getFind($id){
        return Wallet::find($id);
    }
    public function store($request){
        try {
            $wallet = new Wallet();
            $wallet->source         = 'Wallet Recharge'; 
            $wallet->company_id     = settings()->id;
            $wallet->user_id        = Auth::user()->id;
            $wallet->merchant_id    = Auth::user()->merchant->id; 
            $wallet->amount         = $request->amount;
            $wallet->payment_method = WalletPaymentMethod::OFFLINE;
            $wallet->type           = WalletType::INCOME;
            $wallet->status         = WalletStatus::PENDING;
            $wallet->save();
             if($wallet):
                return $wallet;
             else:
                return null;
             endif;
        } catch (\Throwable $th) {
            DB::rollBack();
            return null;
        }
    }
   
    public function paymentStatus($orderId,$transactionId,$status){
        try {
           $wallet                   = $this->getFind($orderId);
           $wallet->transaction_id   = $transactionId;
           $wallet->status           = $status;
           $wallet->save();
           return true;
        } catch (\Throwable $th) {
           return false; 
        }
    }
    /**
     * Approuver une demande de recharge : crediter le solde du marchand une fois.
     *
     * ⚠️ Cette methode etait « ni transactionnelle ni idempotente » : elle
     * creditait le solde a chaque appel, sans jamais regarder l'etat de la ligne.
     * Deux chemins y menent — le bouton « Approuver » de l'ecran Demandes de
     * recharge, et le webhook signe de FedaPay — et rien ne les reliait : une
     * recharge FedaPay en attente, indiscernable d'une recharge manuelle dans la
     * liste (elle s'y affiche « Hors ligne »), etait approuvee par
     * l'administrateur puis creditee une seconde fois par le webhook. Un simple
     * double-clic sur « Approuver » produisait le meme doublement.
     *
     * Deux garde-fous, tous deux necessaires :
     *   - **seule une ligne EN ATTENTE** est approuvee ; un second appel ressort
     *     sans rien ecrire ;
     *   - **verrou de ligne** (`lockForUpdate`) dans une transaction, sans quoi
     *     deux appels simultanes liraient tous deux « en attente ». C'est le
     *     meme garde-fou que `FedaPayController::approve()` pose sur la
     *     transaction FedaPay ; il manquait ici, du cote du portefeuille.
     *
     * Le SMS part **hors transaction** : un envoi lent ne doit pas tenir le
     * verrou, et son echec ne doit pas defaire un credit deja acquis.
     */
    public function approved($id){
        try {

            $credited = DB::transaction(function () use ($id) {
                $wallet = Wallet::whereKey($id)->lockForUpdate()->first();

                if (blank($wallet) || (int) $wallet->status !== WalletStatus::PENDING) {
                    return null; // deja traitee, rejetee, ou inexistante
                }

                $merchant = Merchant::find($wallet->merchant_id);
                if (blank($merchant)) {
                    return null;
                }

                $merchant->wallet_balance = ($merchant->wallet_balance + $wallet->amount);
                $merchant->save();

                $wallet->status           = WalletStatus::APPROVED;
                $wallet->save();

                return ['wallet' => $wallet, 'merchant' => $merchant];
            });

            if (blank($credited)) {
                return false;
            }

            $wallet   = $credited['wallet'];
            $merchant = $credited['merchant'];

            // F4 — la societe vient du PORTEFEUILLE, jamais de `settings()`.
            // Appelee depuis le webhook FedaPay, cette methode n'a ni session ni
            // sous-domaine : `settings()` y retombait sur la societe 1. Le
            // marchand recevait donc un SMS au nom commercial et a la devise
            // d'un autre locataire, emis avec les identifiants d'operateur SMS
            // de cette autre societe et factures a elle.
            $company  = GeneralSettings::find($wallet->company_id);
            $marque   = $company->name ?? '';
            $devise   = $company->currency ?? '';

            $msg = "Dear ".$merchant->business_name.", you are recharges ".$devise.$wallet->amount." to your ".$marque." wallet.";
            $response = app(SmsService::class)->forCompany($wallet->company_id)->sendSms($merchant->user->mobile, $msg); 

            return true; 
        } catch (\Throwable $th) {
        
           return false;
        }
    }
     
    public function rejected($id){
        try {
            $wallet                   = $this->getFind($id); 
            $wallet->status           = WalletStatus::REJECTED;
            $wallet->save(); 
            return true; 
        } catch (\Throwable $th) {
           return false;
        }
    }

    public function expense($request){
        $wallet                 = new Wallet();
        $wallet->company_id     = settings()->id; 
        $wallet->source         = 'Parcel delivery charge - #'.$request->tracking_id; 
        $wallet->user_id        = $request->user_id;
        $wallet->merchant_id    = $request->merchant_id; 
        $wallet->amount         = $request->amount;
        $wallet->payment_method = WalletPaymentMethod::WALLET;
        $wallet->type           = WalletType::EXPENSE;
        $wallet->status         = WalletStatus::APPROVED;
        $wallet->save();
    }



    //admin wallet
    public function adminstore($request){
        try {
            DB::beginTransaction();
            $merchant               = Merchant::find($request->merchant_id);
            $wallet                 = new Wallet();
            $wallet->company_id     = settings()->id; 
            $wallet->source         = 'Wallet Recharge'; 
            $wallet->user_id        = $merchant->user_id;
            $wallet->merchant_id    = $merchant->id; 
            $wallet->transaction_id = $request->transaction_id;
            $wallet->amount         = $request->amount;
            $wallet->payment_method = WalletPaymentMethod::OFFLINE;
            $wallet->type           = WalletType::INCOME;
            $wallet->status         = WalletStatus::APPROVED;
            $wallet->save();
            $merchant->wallet_balance  = ($merchant->wallet_balance + $request->amount);
            $merchant->save();

            $msg = "Dear ".$merchant->business_name.", you are recharges ".settings()->currency.$wallet->amount." to your ".settings()->name." wallet. Transaction id -".$wallet->transaction_id;
            $response = app(SmsService::class)->sendSms($merchant->user->mobile, $msg); 
            DB::commit();
            return true;
        } catch (\Throwable $th) {
         
            DB::rollBack();
            return false;
        }
    }

  public function delete($id){
        try {
            $wallet                     = Wallet::find($id);
            $merchant                   = Merchant::find($wallet->merchant_id);
            if($wallet->status == WalletStatus::APPROVED):
                $merchant->wallet_balance   = ($merchant->wallet_balance - $wallet->amount);
            endif;
            $merchant->save();
            $wallet->delete();
            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }

    }
} 