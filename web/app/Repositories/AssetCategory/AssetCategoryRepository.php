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
        return Assetcategory::find($id);
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
            $assetcategory               =  Assetcategory::find($request->id);
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
