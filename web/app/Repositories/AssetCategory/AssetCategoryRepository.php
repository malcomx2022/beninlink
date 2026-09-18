<?php
namespace App\Repositories\AssetCategory;

use App\Models\Backend\Asset;
use App\Models\Backend\Assetcategory;
use App\Repositories\AssetCategory\AssetCategoryInterface;

class AssetCategoryRepository implements AssetCategoryInterface{
    public function all(){
        return Assetcategory::companywise()->orderBy('position','asc')->paginate(10);
    }

    public function get($id){
        // S29 — lecture nue, comme `Asset`.
        return Assetcategory::companywise()->find($id);
    }

    public function store($request){
        try {
            $assetcategory               = new Assetcategory();
            $assetcategory->company_id   = settings()->id;
            $assetcategory->title        = $request->title;
            $assetcategory->position     = $request->position;
            $assetcategory->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($request)
    {

        try {
            // S29 — VOL DE LIGNE : `Assetcategory::find($request->id)` etait NU, et la ligne
            // suivante ecrasait `company_id` avec la societe connectee. La ligne
            // d'une autre societe n'etait donc pas seulement lue : elle etait
            // TRANSFEREE chez nous, et disparaissait de chez son proprietaire.
            $assetcategory               =  Assetcategory::companywise()->find($request->id);

            if (blank($assetcategory)) {
                return false;
            }
            $assetcategory->company_id   = settings()->id;
            $assetcategory->title        = $request->title;
            $assetcategory->position     = $request->position;
            $assetcategory->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
        $asset_category =  Assetcategory::find($id);
        if($asset_category->company_id == settings()->id):
            return  Assetcategory::destroy($id);
        endif;
        return false;
    }
}
