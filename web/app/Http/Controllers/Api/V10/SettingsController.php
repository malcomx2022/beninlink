<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\DeliveryChargeResource;
use App\Http\Resources\v10\DeliveryDelayResource;
use App\Http\Resources\v10\DeliveryZoneResource;
use App\Models\Backend\Merchant;
use App\Repositories\DeliveryZone\DeliveryZoneInterface;
use App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeInterface;
use App\Services\Parcel\ChargeCalculator;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Référentiels servis à l'app marchand.
 *
 * **D4, étape 5** — le barème par zones entre dans le contrat. Les deux
 * endpoints servent désormais **les deux formes** : les quatre colonnes
 * héritées, inchangées, et le nouveau modèle à côté. Un APK déjà installé ne
 * voit aucune différence ; une version à jour lit `zones` si elle n'est pas
 * vide, et retombe sur les colonnes sinon. C'est la période de transition
 * prévue par `docs/guides/tarification/refonte-bareme.md` : sans elle, une
 * app déployée afficherait une grille vide le jour de la bascule.
 */
class SettingsController extends Controller
{
    use ApiReturnFormatTrait;

    protected $deliveryCharges;

    protected $zones;

    public function __construct(MerchantDeliveryChargeInterface $deliveryCharges, DeliveryZoneInterface $zones)
    {
        $this->deliveryCharges = $deliveryCharges;
        $this->zones = $zones;
    }

    /**
     * Taux d'encaissement du marchand.
     *
     * Chaque entrée gagne `zone_code` : la zone à laquelle ce taux se rattache
     * (**D4**). La correspondance vient de `ChargeCalculator::COD_KEY_BY_ZONE`,
     * la table que le calcul lui-même lit — l'app n'a pas à la deviner, et
     * elle ne peut pas dériver. `null` quand la clé n'appartient à aucune
     * zone : le contrat le dit plutôt que de rattacher au hasard.
     */
    public function codCharges(){
        try {
            $codCharge  = Merchant::where('user_id', auth()->user()->id)->first();
            $zoneParCle = array_flip(ChargeCalculator::COD_KEY_BY_ZONE);
            $codCharges = [];
            $i = 0;
            if(!blank($codCharge)){
                foreach($codCharge->cod_charges as $key => $charge){
                    $codCharges[$i]['name']       = __('merchant.'.$key);
                    $codCharges[$i]['charge']     = $charge;
                    $codCharges[$i]['zone_code']  = $zoneParCle[$key] ?? null;
                    $i++;
                }
            }
            return $this->responseWithSuccess(__('delivery_charge.cod_charges'), ['codCharges'=>$codCharges], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('delivery_charge.error_msg'), [], 500);

        }
    }

    /**
     * Barème du marchand — les quatre colonnes **et** les zones.
     *
     * `zones` est vide tant que la société n'en a pas configuré : c'est le
     * signal, pour l'app, de rester sur `deliveryCharges`. `delays` porte le
     * supplément **global** de chaque délai, qui s'ajoute au montant de la
     * zone et n'en dépend pas.
     */
    public function deliveryCharges(){
        try {
            $merchant_id = Merchant::where('user_id', auth()->user()->id)->first();
            $deliveryCharges = DeliveryChargeResource::collection($this->deliveryCharges->getAll($merchant_id->id));
            $zones = DeliveryZoneResource::collection($this->zones->grilleMarchand($merchant_id->id));
            $delays = DeliveryDelayResource::collection($this->zones->delais());

            return $this->responseWithSuccess(__('delivery_charge.title'), [
                'deliveryCharges' => $deliveryCharges,
                'zones' => $zones,
                'delays' => $delays,
            ], 200);
        }catch (\Exception $exception) {
            return $this->responseWithError(__('delivery_charge.error_msg'), [], 500);
        }

    }
}
