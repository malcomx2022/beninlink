<?php
namespace App\Repositories\DeliveryCharge;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory;
use App\Repositories\DeliveryCharge\DeliveryChargeInterface;
use App\Enums\Status;
use App\Enums\UserType;
use Illuminate\Support\Arr;

class DeliveryChargeRepository implements DeliveryChargeInterface{
    public function allGet(){
        $dCharges      = DeliveryCharge::companywise()->with('category')->orderBy('position')->get();
        $categoryWise  = $dCharges->groupBy('category_id'); 
        return Arr::collapse($categoryWise);
        $Charges = [];
        foreach ($categoryWise as $key=>$Deliverycharge) {
            foreach ($Deliverycharge as $charge) {
                $Charges[] = $charge;
            }
        }
        return $Charges;
    }
    public function getAllCharge(){
        $dCharges      = DeliveryCharge::companywise()->with('category')->orderBy('position')->get();
        $categoryWise  = $dCharges->groupBy('category_id'); 
        return Arr::collapse($categoryWise);
    }
    public function all(){
        return DeliveryCharge::companywise()->with('category')->orderBy('position')->paginate(10);
    }

     public function filter($request){
         return DeliveryCharge::companywise()->with('category')->where(function($query)use($request){
             if($request->category){
                 $query->where('category_id',$request->category);
             }
             if($request->weight):
                 $query->where('weight',$request->weight);
             endif;

         })->orderBy('position')->paginate(10);
     }

    public function categories(){
        return Deliverycategory::where(function($query){
            $query->companywise();
            $query->orWhere('id',1);
        })->get();
    }

    /**
     * S8 — le repli n'était pas scopé par société : l'écran d'édition ouvrait
     * la ligne de barème d'un autre transporteur dès lors qu'on en connaissait
     * l'identifiant. `delete()` vérifiait déjà la société ; `get()` non.
     */
    public function get($id){
        return DeliveryCharge::companywise()->find($id);
    }

    public function store($request){
        try {
            $delivery_charge               = new DeliveryCharge();
            $delivery_charge->company_id   = settings()->id;
            $delivery_charge->category_id  = $request->category;
            // When category select kg. then weight = null
            if($request->category == 1):
                $delivery_charge->weight   = $request->weight;
            endif;
            $delivery_charge->zone_id      = $request->zone;
            $delivery_charge->amount       = $request->amount;
            $delivery_charge->position     = $request->position;
            $delivery_charge->status       = $request->status;
            $delivery_charge->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($request)
    {
        try {
            // S29 — VOL DE LIGNE : recherche nue, puis `company_id` ecrase par la
            // societe connectee — la ligne d'une autre societe etait TRANSFEREE.
            $delivery_charge               = DeliveryCharge::companywise()->find($request->id);

            if (blank($delivery_charge)) {
                return false;
            }
            $delivery_charge->company_id   = settings()->id;
            $delivery_charge->category_id  = $request->category;
            // When category select kg. then weight = null
            if($request->category == 1):
                $delivery_charge->weight   = $request->weight;
            endif;
            $delivery_charge->zone_id      = $request->zone;
            $delivery_charge->amount       = $request->amount;
            $delivery_charge->position     = $request->position;
            $delivery_charge->status       = $request->status;
            $delivery_charge->save();
            return true;
        }catch (\Exception $e) {
            return false;
        }
    }
    public function delete($id){
        $charge = DeliveryCharge::find($id);
        if($charge->company_id == settings()->id):
            return DeliveryCharge::destroy($id);
        endif;
        return false;
    }
}
