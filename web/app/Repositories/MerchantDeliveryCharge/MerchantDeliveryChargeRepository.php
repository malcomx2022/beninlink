<?php
namespace App\Repositories\MerchantDeliveryCharge;

use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;

/**
 * S34 — les **barèmes négociés** d'un marchand.
 *
 * Trois identifiants circulent ici, et un seul était vérifié :
 *
 *  - le **marchand**, pris dans l'URL (`merchant/{merchant}/delivery-charge/…`) :
 *    jamais vérifié à l'écriture. On créait donc une ligne de barème pour le
 *    marchand d'un autre transporteur ;
 *  - la **ligne de barème** elle-même : `update()` la lisait sans `companywise()`
 *    puis écrivait `company_id = settings()->id` — la forme de **reprise de
 *    ligne** relevée onze fois en 3ᵉ passe. Le tarif négocié d'un autre
 *    transporteur devenait le nôtre ;
 *  - la **ligne de grille** référencée (`delivery_charge_id`) : lue nue, elle
 *    recopiait la catégorie et la tranche de poids de la grille d'ailleurs.
 *
 * `all()` et `get()`, en lecture, étaient déjà `companywise()`.
 */
class MerchantDeliveryChargeRepository implements MerchantDeliveryChargeInterface{

        /** Les barèmes négociés de la société, et eux seuls. */
        private function baremesDeLaSociete(){
            return MerchantDeliveryCharge::companywise();
        }

        /** Le marchand désigné, s'il est bien de la société. */
        private function marchandDeLaSociete($merchantId){
            return blank($merchantId) ? null : Merchant::companywise()->find($merchantId);
        }

        /** La ligne de grille référencée, si elle est bien de la société. */
        private function grilleDeLaSociete($chargeId){
            return blank($chargeId) ? null : DeliveryCharge::companywise()->find($chargeId);
        }

        public function all($id){
           return MerchantDeliveryCharge::companywise()->where(['merchant_id'=>$id])->orderBy('weight')->paginate(10);
        }
        public function getAll($id){
           return MerchantDeliveryCharge::companywise()->where(['merchant_id'=>$id])->orderBy('weight')->get();
        }
        public function get($merchant_id,$id){
            return MerchantDeliveryCharge::companywise()->where(['id'=>$id,'merchant_id'=>$merchant_id])->first();
        }

        public function delivery_charges_get(){
            return DeliveryCharge::companywise()->orderBy('weight','asc')->get();
        }

        public function store($request,$merchant_id){

            try {
                $merchant       = $this->marchandDeLaSociete($merchant_id);
                $deliverycharge = $this->grilleDeLaSociete($request->delivery_charge_id);

                if(blank($merchant) || blank($deliverycharge)){
                    return false;
                }

                $deliveryCharge                      = new MerchantDeliveryCharge();
                $deliveryCharge->company_id          = settings()->id;
                $deliveryCharge->merchant_id         = $merchant->id;
                $deliveryCharge->delivery_charge_id  = $deliverycharge->id;
                $deliveryCharge->category_id         = $deliverycharge->category_id;
                // D4, etape 6 : une ligne negociee doit porter sa ZONE, sinon le
                // resolveur ne la trouve jamais et le tarif negocie ne facture
                // rien. `MerchantRepository::store()` la porte deja a la creation
                // du marchand ; cet ecran l'avait manquee. Voir la note du lot.
                $deliveryCharge->zone_id             = $deliverycharge->zone_id;
                $deliveryCharge->weight              = $deliverycharge->weight;
                $deliveryCharge->amount              = $request->amount;
                $deliveryCharge->status              = $request->status;
                $deliveryCharge->save();
                 return true;

            } catch (\Throwable $th) {
                return false;
            }
        }

        public function update($request,$id, $merchant_id){

            try {
                $merchant       = $this->marchandDeLaSociete($merchant_id);
                $deliverycharge = $this->grilleDeLaSociete($request->delivery_charge_id);
                // La ligne doit etre a nous : sans ce perimetre, le
                // `company_id = settings()->id` ci-dessous la RAPATRIAIT.
                $deliveryCharge = $this->baremesDeLaSociete()
                    ->where(['id'=>$id,'merchant_id'=>$merchant_id])->first();

                if(blank($merchant) || blank($deliverycharge) || blank($deliveryCharge)){
                    return false;
                }

                $deliveryCharge->merchant_id         = $merchant->id;
                $deliveryCharge->delivery_charge_id  = $deliverycharge->id;
                $deliveryCharge->category_id         = $deliverycharge->category_id;
                // D4, etape 6 — meme raison qu'a la creation.
                $deliveryCharge->zone_id             = $deliverycharge->zone_id;
                $deliveryCharge->weight              = $deliverycharge->weight;
                $deliveryCharge->amount              = $request->amount;
                $deliveryCharge->status              = $request->status;
                $deliveryCharge->save();

                 return true;

            } catch (\Throwable $th) {
                return false;
            }

        }

        public function delete($id,$merchant_id){
            // `find($id)` nu rendait `null` hors perimetre, et `->company_id`
            // dessus levait une erreur : 500 la ou le module repond 404. Le
            // `$merchant_id` de l'URL etait par ailleurs ignore — on supprimait
            // la ligne d'un marchand par l'ecran d'un autre.
            return (bool) $this->baremesDeLaSociete()
                ->where(['id'=>$id,'merchant_id'=>$merchant_id])->delete();
        }


}
