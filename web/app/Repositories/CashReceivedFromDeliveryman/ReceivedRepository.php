<?php
namespace App\Repositories\CashReceivedFromDeliveryman;

use App\Enums\AccountHeads;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Hub;
use App\Models\Backend\HubStatement;
use App\Models\Backend\Upload;
use App\Models\CashReceivedFromDeliveryman;
use App\Repositories\CashReceivedFromDeliveryman\ReceivedInterface;
use Database\Seeders\HubSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceivedRepository implements ReceivedInterface {

    public function all(){
        return CashReceivedFromDeliveryman::companywise()->where('hub_id',Auth::user()->hub_id)->orderByDesc('id')->get();
    }

    public function get($id){
        // S30 — lecture nue : la remise d'especes d'un livreur d'une autre societe.
        // `update()` et `delete()` etaient deja scopees — la dissymetrie habituelle,
        // ici dans l'autre sens.
        return CashReceivedFromDeliveryman::companywise()->find($id);
    }
    /**
     * Remise d'especes du livreur a son agence — le pendant de la livraison.
     *
     * Livrer laisse le livreur DEBITEUR de l'argent encaisse chez le client ;
     * cette etape solde cette dette et depose les especes sur un compte du
     * transporteur. Trois soldes bougent ensemble : le livreur, l'agence, le
     * compte.
     *
     * Deux gardes ajoutees le 2026-09-05 en couvrant l'etape de tests (D8) :
     * le livreur et le compte de depot sont lus `companywise()` — le socle
     * lisait `find()` nu, si bien qu'une agence soldait la dette d'un livreur
     * d'une AUTRE societe en deposant la somme sur son propre compte — et les
     * trois mouvements passent dans une transaction.
     */
    public function store($request){
        if(blank(Auth::user()->hub_id)){
            return false;
        }

        $deliveryman  = DeliveryMan::companywise()->find($request->delivery_man_id);
        $account      = Account::companywise()->find($request->account_id);
        if(blank($deliveryman) || blank($account)){
            return false;
        }

        try {
            return DB::transaction(function () use ($request, $deliveryman, $account) {
                $hub          = Hub::find(Auth::user()->hub_id);
                 //add hub statements
                 $cash_received                   = new CashReceivedFromDeliveryman();
                 $cash_received->company_id       = settings()->id;
                 $cash_received->user_id          = Auth::user()->id;
                 $cash_received->hub_id           = $hub->id;
                 $cash_received->account_id       = $request->account_id;
                 $cash_received->delivery_man_id  = $request->delivery_man_id;
                 $cash_received->amount           = $request->amount;
                 $cash_received->date             = $request->date;
                 $cash_received->receipt          = $this->file('',$request->receipt);
                 $cash_received->note             = __('permissions.cash_received_from_delivery_man');
                 $cash_received->save();

                //add hub statements
                $hub_statements                   = new HubStatement();
                $hub_statements->company_id       = settings()->id;
                $hub_statements->user_id          = Auth::user()->id;
                $hub_statements->hub_id           = $hub->id;
                $hub_statements->account_id       = $request->account_id;
                $hub_statements->delivery_man_id  = $request->delivery_man_id;
                $hub_statements->type             = AccountHeads::EXPENSE;
                $hub_statements->amount           = $request->amount;
                $hub_statements->date             = $request->date;
                $hub_statements->note             = __('permissions.cash_received_from_delivery_man');
                $hub_statements->save();
                //add (-) amount
                $hub->current_balance             = $hub->current_balance +(-$request->amount) ;
                $hub->save();

                //Account
                //hub bank account statements
                $bank_statments                    = new BankTransaction();
                $bank_statments->company_id       = settings()->id;
                $bank_statments->account_id        = $account->id;
                $bank_statments->user_type         = UserType::HUB;
                $bank_statments->hub_id            = $hub->id;
                $bank_statments->type              = AccountHeads::INCOME;
                $bank_statments->amount            = $request->amount;
                $bank_statments->date              = $request->date;
                $bank_statments->cash_received_dvry = $cash_received->id;
                $bank_statments->note              = __('permissions.cash_received_from_delivery_man');
                $bank_statments->save();
                //add (+) amount in account
                $account->balance          = $account->balance + $request->amount;
                $account->save();


                 //Delivery man
                 //add delivery man statements
                 $deliveryman_statment                  = new DeliverymanStatement();
                 $deliveryman_statment->company_id       = settings()->id;
                 $deliveryman_statment->delivery_man_id = $request->delivery_man_id;
                 $deliveryman_statment->hub_id          = $hub->id;
                 $deliveryman_statment->type            = AccountHeads::INCOME;
                 $deliveryman_statment->amount          = $request->amount;
                 $deliveryman_statment->date            = $request->date;
                 $deliveryman_statment->note            = __('permissions.cash_received_from_delivery_man');
                 $deliveryman_statment->save();

                 //add (+) amount
                 $deliveryman->current_balance         = $deliveryman->current_balance + $request->amount;
                 $deliveryman->save();

                 return true;
            });
        } catch (\Throwable $th) {

           return false;
        }
    }
    /**
     * Corriger une remise : on defait l'ancienne, on refait la nouvelle.
     *
     * ⚠️ Cette methode n'avait **ni transaction ni `try/catch`**, alors qu'elle
     * ecrit six mouvements de comptes a la suite. Un incident au milieu laissait
     * la remise a moitie defaite et pas refaite — l'etat le plus difficile a
     * rattraper de tout le socle, puisque relancer la correction defait une
     * seconde fois. Et elle lisait la remise sans scope : celle d'un autre
     * transporteur etait corrigeable.
     */
    public function update($request){
        if(blank(Auth::user()->hub_id)){
            return false;
        }

        $cash_received = CashReceivedFromDeliveryman::companywise()->find($request->id);
        if(blank($cash_received)){
            return false;
        }

        // Le livreur et le compte VISES par la correction, chez nous eux aussi.
        if(blank(DeliveryMan::companywise()->find($request->delivery_man_id))
            || blank(Account::companywise()->find($request->account_id))){
            return false;
        }

        try {
            return DB::transaction(function () use ($request, $cash_received) {

        //start first reverse
        $deliveryman  = DeliveryMan::find($cash_received->delivery_man_id);
        $account      = Account::find($cash_received->account_id);
        $hub          = Hub::find(Auth::user()->hub_id);
        //add hub statements
        $hub_statements                   = new HubStatement();
        $hub_statements->company_id       = settings()->id;
        $hub_statements->user_id          = Auth::user()->id;
        $hub_statements->hub_id           = $hub->id;
        $hub_statements->account_id       = $cash_received->account_id;
        $hub_statements->delivery_man_id  = $cash_received->delivery_man_id;
        $hub_statements->type             = AccountHeads::INCOME;
        $hub_statements->amount           = $cash_received->amount;
        $hub_statements->date             = $cash_received->date;
        $hub_statements->note             = __('permissions.cash_received_from_delivery_man');
        $hub_statements->save();
        //add (+) amount
        $hub->current_balance             = $hub->current_balance +(+$cash_received->amount) ;
        $hub->save();
        //hub bank account statements
        $bank_statments                    = new BankTransaction();
        $bank_statments->company_id        = settings()->id;
        $bank_statments->account_id        = $account->id;
        $bank_statments->user_type         = UserType::HUB;
        $bank_statments->hub_id            = $hub->id;
        $bank_statments->type              = AccountHeads::EXPENSE;
        $bank_statments->amount            = $cash_received->amount;
        $bank_statments->date              = $cash_received->date;
        $bank_statments->cash_received_dvry = $cash_received->id;
        $bank_statments->note              = __('permissions.cash_received_from_delivery_man');
        $bank_statments->save();

        //add (-) amount in account
        $account->balance          = $account->balance - $cash_received->amount;
        $account->save();
        //add delivery man statements
        $deliveryman_statment                  = new DeliverymanStatement();
        $deliveryman_statment->company_id      = settings()->id;
        $deliveryman_statment->delivery_man_id = $cash_received->delivery_man_id;
        $deliveryman_statment->hub_id          = $hub->id;
        $deliveryman_statment->type            = AccountHeads::EXPENSE;
        $deliveryman_statment->amount          = $cash_received->amount;
        $deliveryman_statment->date            = $cash_received->date;
        $deliveryman_statment->note            = __('permissions.cash_received_from_delivery_man');
        $deliveryman_statment->save();
        //add (-) amount
        $deliveryman->current_balance         = $deliveryman->current_balance - $cash_received->amount;
        $deliveryman->save();
        // end all reverse
        //again store
        $deliveryman  = DeliveryMan::find($request->delivery_man_id);
        $account      = Account::find($request->account_id);
        $hub          = Hub::find(Auth::user()->hub_id);

         //cash received from delivery man table
        //add hub statements
        $cash_received->user_id          = Auth::user()->id;
        $cash_received->hub_id           = $hub->id;
        $cash_received->account_id       = $request->account_id;
        $cash_received->delivery_man_id  = $request->delivery_man_id;
        $cash_received->amount           = $request->amount;
        $cash_received->date             = $request->date;
        $cash_received->receipt          = $this->file('',$request->receipt);
        $cash_received->note             = __('permissions.cash_received_from_delivery_man');
        $cash_received->save();

        //add hub statements
        $hub_statements                   = new HubStatement();
        $hub_statements->user_id          = Auth::user()->id;
        $hub_statements->company_id       = settings()->id;
        $hub_statements->hub_id           = $hub->id;
        $hub_statements->account_id       = $request->account_id;
        $hub_statements->delivery_man_id  = $request->delivery_man_id;
        $hub_statements->type             = AccountHeads::EXPENSE;
        $hub_statements->amount           = $request->amount;
        $hub_statements->date             = $request->date;
        $hub_statements->note             = __('permissions.cash_received_from_delivery_man');
        $hub_statements->save();

        //add (-) amount
        $hub->current_balance             = $hub->current_balance +(-$request->amount) ;
        $hub->save();

        //hub bank account statements
        $bank_statments                    = new BankTransaction();
        $bank_statments->company_id        = settings()->id;
        $bank_statments->account_id        = $account->id;
        $bank_statments->user_type         = UserType::HUB;
        $bank_statments->hub_id            = $hub->id;
        $bank_statments->type              = AccountHeads::INCOME;
        $bank_statments->amount            = $request->amount;
        $bank_statments->date              = $request->date;
        $bank_statments->note              = __('permissions.cash_received_from_delivery_man');
        $bank_statments->save();

        //add (+) amount in account
        $account->balance                  = $account->balance + $request->amount;
        $account->save();

        //add delivery man statements
        $deliveryman_statment                  = new DeliverymanStatement();
        $deliveryman_statment->company_id      = settings()->id;
        $deliveryman_statment->delivery_man_id = $request->delivery_man_id;
        $deliveryman_statment->hub_id          = $hub->id;
        $deliveryman_statment->type            = AccountHeads::INCOME;
        $deliveryman_statment->amount          = $request->amount;
        $deliveryman_statment->date            = $request->date;
        $deliveryman_statment->note            = __('permissions.cash_received_from_delivery_man');
        $deliveryman_statment->save();
        //add (+) amount
        $deliveryman->current_balance         = $deliveryman->current_balance + $request->amount;
        $deliveryman->save();

        return true;

            });
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Supprimer une remise : elle se defait entierement, ou pas du tout.
     * Le controle de societe existait deja ici ; la transaction manquait.
     */
    public function delete($id){

        if(Auth::user()->hub_id):

            try {
                return DB::transaction(function () use ($id) {

                $cash_received = CashReceivedFromDeliveryman::companywise()->find($id);
                if(!blank($cash_received)):

                    $deliveryman   = DeliveryMan::find($cash_received->delivery_man_id);
                    $account       = Account::find($cash_received->account_id);
                    $hub           = Hub::find(Auth::user()->hub_id);
                    //add hub statements
                    $hub_statements                   = new HubStatement();
                    $hub_statements->company_id       = settings()->id;
                    $hub_statements->user_id          = Auth::user()->id;
                    $hub_statements->hub_id           = $hub->id;
                    $hub_statements->account_id       = $cash_received->account_id;
                    $hub_statements->delivery_man_id  = $cash_received->delivery_man_id;
                    $hub_statements->type             = AccountHeads::INCOME;
                    $hub_statements->amount           = $cash_received->amount;
                    $hub_statements->date             = $cash_received->date;
                    $hub_statements->note             = __('permissions.cash_received_from_delivery_man');
                    $hub_statements->save();
                    //add (-) amount
                    $hub->current_balance             = $hub->current_balance +(+$cash_received->amount) ;
                    $hub->save();

                    //hub bank account statements
                    $bank_statments                    = new BankTransaction();
                    $bank_statments->company_id        = settings()->id;
                    $bank_statments->account_id        = $account->id;
                    $bank_statments->user_type         = UserType::HUB;
                    $bank_statments->hub_id            = $hub->id;
                    $bank_statments->type              = AccountHeads::EXPENSE;
                    $bank_statments->amount            = $cash_received->amount;
                    $bank_statments->date              = $cash_received->date;
                    $bank_statments->note              = __('permissions.cash_received_from_delivery_man');
                    $bank_statments->save();

                    //add (+) amount in account
                    $account->balance          = $account->balance - $cash_received->amount;
                    $account->save();

                    //add delivery man statements
                    $deliveryman_statment                  = new DeliverymanStatement();
                    $deliveryman_statment->company_id      = settings()->id;
                    $deliveryman_statment->delivery_man_id = $cash_received->delivery_man_id;
                    $deliveryman_statment->hub_id          = $hub->id;
                    $deliveryman_statment->type            = AccountHeads::EXPENSE;
                    $deliveryman_statment->amount          = $cash_received->amount;
                    $deliveryman_statment->date            = $cash_received->date;
                    $deliveryman_statment->note            = __('permissions.cash_received_from_delivery_man');
                    $deliveryman_statment->save();
                    //add (+) amount
                    $deliveryman->current_balance         = $deliveryman->current_balance - $cash_received->amount;
                    $deliveryman->save();
                    return CashReceivedFromDeliveryman::destroy($cash_received->id);
                endif;
                return false;

                });
            } catch (\Throwable $th) {
                return false;
            }
        else:
            return false;
        endif;

    }

    // Request image Store in Upload Model and image copy file attach in public/upload/user folder.
    public function file($image_id = '', $image)
    {
        try {

            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/income');
                $profileImage          = date('YmdHis') . "." . safeUploadExtension($image);
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/income/'.$profileImage;
            }

            if(blank($image_id)){
                $upload           = new Upload();
            }else{
                $upload           = Upload::find($image_id);
                if(file_exists($upload->original))
                {
                    unlink($upload->original);
                }
            }

            $upload->original     = $image_name;
            $upload->save();
            return $upload->id;

        }
        catch (\Exception $e) {
            return false;
        }
    }



}
