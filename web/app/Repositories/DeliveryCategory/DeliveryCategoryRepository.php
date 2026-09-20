<?php
namespace App\Repositories\DeliveryCategory;
use App\Models\Backend\Deliverycategory;
use App\Repositories\DeliveryCategory\DeliveryCategoryInterface;
use App\Enums\Status;
use App\Enums\UserType;

class DeliveryCategoryRepository implements DeliveryCategoryInterface{
    public function all(){
        return Deliverycategory::where(function($query){
            $query->where('id',1);
            $query->orWhere(['company_id'=>settings()->id]);
        })->orderBy('position','asc')->paginate(10);
    }

    public function get($id){
        return Deliverycategory::where(function($query)use($id){ 
            if($id == 1):
                $query->where('id',1);
            else:
                $query->where(['company_id'=>settings()->id,'id'=>$id]);
            endif;
        })->first();
    }

    public function store($request){
        try {
            $Deliverycategory               = new Deliverycategory();
            $Deliverycategory->company_id    = settings()->id;
            $Deliverycategory->title        = $request->title;
            $Deliverycategory->status       = $request->status;
            $Deliverycategory->position     = $request->position;
            $Deliverycategory->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($request)
    {
        $request->validate([
            'title' => 'required|unique:deliverycategories,title,'.$request->id,
        ]);

        try {
            // S35 — `find($request->id)` nu suivi de `company_id = settings()->id` :
            // reprise de ligne. L'identifiant vient du CORPS
            // (`PUT admin/delivery-category/update`), donc hors du champ du filet.
            //
            // ⚠️ Volontairement PLUS strict que `get()` : celui-ci laisse lire la
            // categorie 1, partagee par toutes les societes. La laisser REECRIRE
            // reviendrait a laisser n'importe quel transporteur renommer la
            // categorie de tous les autres.
            $Deliverycategory                   = Deliverycategory::companywise()->find($request->id);
            if(blank($Deliverycategory)){
                return false;
            }
            $Deliverycategory->title            = $request->title;
            $Deliverycategory->status           = $request->status;
            $Deliverycategory->position         = $request->position;
            $Deliverycategory->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
        // S35 — forme gardee correcte, mais 500 sur `null` hors perimetre.
        return (bool) Deliverycategory::companywise()->whereKey($id)->delete();
    }
}
