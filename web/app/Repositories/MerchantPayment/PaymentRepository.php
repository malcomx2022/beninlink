<?php
namespace App\Repositories\MerchantPayment;

use App\Models\Backend\Merchant;
use App\Models\MerchantPayment;
use App\Repositories\MerchantPayment\PaymentInterface;

/**
 * S34 — les **comptes de versement** des marchands, vus du back-office.
 *
 * C'est le dépôt le plus ouvert rencontré depuis le début du chantier : aucune
 * de ses méthodes ne portait de périmètre, et la table `merchant_payments` n'a
 * pas de `company_id` — le rattachement passe par le marchand, ce que le scope
 * du modèle ne disait pas (il interrogeait une colonne inexistante).
 *
 * Ce qui était atteignable avec l'identifiant d'un marchand d'un autre
 * transporteur :
 *
 *  - `get()` / `edit()` : banque, titulaire, **numéro de compte**, code guichet,
 *    numéro Mobile Money — la fiche de versement complète ;
 *  - `delete()` : suppression de n'importe quelle ligne, et le contrôleur
 *    annonçait un succès sans regarder ;
 *  - `bankstore()`, `mobilestore()`, `bankUpdate()`, `mobileUpdate()` : le
 *    `merchant_id` venait du **formulaire**, et `editid` **détruisait** la ligne
 *    désignée avant d'en créer une neuve. On pouvait donc retirer le compte de
 *    versement d'un marchand d'ailleurs et le remplacer par le sien : c'est
 *    l'endroit exact où **l'argent est versé**.
 *
 * ⚠️ Ces quatre écritures portent leur identifiant **dans le corps** de la
 * requête, pas dans l'URL : elles sont invisibles au filet d'isolation, qui
 * n'énumère que les routes à paramètre. Quatrième occurrence de cette tache
 * aveugle, et la plus coûteuse.
 *
 * La forme retenue est celle de S31 (`HubInChargeRepository`) : une méthode
 * privée qui porte le périmètre, et toutes les autres passent par elle. Le
 * marchand désigné est vérifié séparément, parce qu'une écriture qui le nomme
 * ne doit pas pouvoir le nommer hors de la société.
 */
class PaymentRepository implements PaymentInterface{

    /** Les comptes de versement de la société, et eux seuls. */
    private function comptesDeLaSociete(){
        return MerchantPayment::companywise();
    }

    /** Le marchand désigné, s'il est bien de la société. */
    private function marchandDeLaSociete($merchantId){
        return blank($merchantId) ? null : Merchant::companywise()->find($merchantId);
    }

    public function all(){
         //
    }

    public function get($id){
        // Sans marchand vérifié, aucune ligne : mieux qu'une fiche de versement
        // d'un autre transporteur affichée dans mon écran. Le contrôleur, lui,
        // répond 404 — mais le dépôt ne s'appuie pas sur son appelant.
        $merchant = $this->marchandDeLaSociete($id);
        if(blank($merchant)){
            return collect();
        }
        return $this->comptesDeLaSociete()->where('merchant_id',$merchant->id)->get();
    }

    public function edit($id){
        return $this->comptesDeLaSociete()->where('id',$id)->first();
    }


    public function bankstore($request){
        try {
            $merchant = $this->marchandDeLaSociete($request->merchant_id);
            if(blank($merchant)){
                return false;
            }
            // `editid` DETRUIT la ligne designee : elle doit etre a nous, sinon
            // l'ecran « ajouter » devient un « supprimer chez le voisin ».
            if($request->editid){
                if(blank($this->comptesDeLaSociete()->where('id',$request->editid)->first())){
                    return false;
                }
                $delete      = MerchantPayment::destroy($request->editid);
            }
            $merchantpayment                 = new MerchantPayment();
            $merchantpayment->merchant_id    = $merchant->id;
            $merchantpayment->payment_method = $request->payment_method_name;
            if($request->payment_method_name == 'cash'){

            }else{
                $merchantpayment->bank_name      = $request->bank_name;
                $merchantpayment->holder_name    = $request->holder_name;
                $merchantpayment->account_no     = $request->account_no;
                $merchantpayment->branch_name    = $request->branch_name;
                $merchantpayment->routing_no     = $request->routing_no;
                $merchantpayment->status         = $request->status;
            }
            $merchantpayment->save();
            return true;

        } catch (\Throwable $th) {
            return false;
        }
    }
    public function mobilestore($request){
        try {
            $merchant = $this->marchandDeLaSociete($request->merchant_id);
            if(blank($merchant)){
                return false;
            }
            if($request->editid){
                if(blank($this->comptesDeLaSociete()->where('id',$request->editid)->first())){
                    return false;
                }
                $delete     = MerchantPayment::destroy($request->editid);
            }
            $merchantpayment                 = new MerchantPayment();
            $merchantpayment->merchant_id    = $merchant->id;
            $merchantpayment->payment_method = $request->payment_method_name;
            $merchantpayment->holder_name    = $request->mobile_holder_name;
            $merchantpayment->mobile_company = $request->mobile_company;
            $merchantpayment->mobile_no      = $request->mobile_no;
            $merchantpayment->account_type   = $request->account_type;
            $merchantpayment->status         = $request->status;
            $merchantpayment->save();
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }

    public function update($id,$request){
        //
    }



    public function bankUpdate($request){

        try {
            $merchant = $this->marchandDeLaSociete($request->merchant_id);
            if(blank($merchant) || blank($this->comptesDeLaSociete()->where('id',$request->editid)->first())){
                return false;
            }

            $delete                          = MerchantPayment::destroy($request->editid);
            $merchantpayment                 = new MerchantPayment();
            $merchantpayment->merchant_id    = $merchant->id;
            $merchantpayment->payment_method = $request->payment_method_name;
            if($request->payment_method_name == 'cash'){
            }else{
                $merchantpayment->bank_name      = $request->bank_name;
                $merchantpayment->holder_name    = $request->holder_name;
                $merchantpayment->account_no     = $request->account_no;
                $merchantpayment->branch_name    = $request->branch_name;
                $merchantpayment->routing_no     = $request->routing_no;
                $merchantpayment->status         = $request->status;
            }
            $merchantpayment->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function mobileUpdate($request){

        try {
            $merchant = $this->marchandDeLaSociete($request->merchant_id);
            if(blank($merchant) || blank($this->comptesDeLaSociete()->where('id',$request->editid)->first())){
                return false;
            }

            $delete                          = MerchantPayment::destroy($request->editid);
            $merchantpayment                 = new MerchantPayment();
            $merchantpayment->merchant_id    = $merchant->id;
            $merchantpayment->payment_method = $request->payment_method_name;
            $merchantpayment->holder_name    = $request->mobile_holder_name;
            $merchantpayment->mobile_company = $request->mobile_company;
            $merchantpayment->mobile_no      = $request->mobile_no;
            $merchantpayment->account_type   = $request->account_type;
            $merchantpayment->status         = $request->status;
            $merchantpayment->save();
            return true;

        } catch (\Throwable $th) {
            return false;
        }
    }

    public function delete($id){
        // `destroy($id)` nu supprimait la ligne de n'importe quelle societe.
        return $this->comptesDeLaSociete()->where('id',$id)->delete();
    }
}
