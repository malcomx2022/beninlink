<?php
namespace App\Repositories\Asset;
use App\Models\Backend\Asset;
use App\Repositories\Asset\AssetInterface;
use App\Models\Backend\Hub;
use App\Models\Backend\Assetcategory;
use Illuminate\Support\Facades\Auth;

class AssetRepository implements AssetInterface{

    public function all(){
        return Asset::companywise()->orderBy('name','asc')->paginate(10);
    }

    // get all rows in Hub model
    public function hubs(){
        return Hub::companywise()->orderBy('name')->get();
    }

    // get all rows in assetcategory model
    public function assetcategorys(){
        return Assetcategory::companywise()->orderBy('title')->get();
    }

    public function get($id){
        return Asset::find($id);
    }

    // All request data store in NewsOffer tabel.
    public function store($request)
    {

        try {

            $asset                     = new Asset();
            $asset->company_id         = settings()->id;
            $asset->author             = Auth::user()->id;
            $asset->name               = $request->name;
            $asset->assetcategory_id   = $request->assetcategory_id;
            $asset->hub_id             = $request->hub_id;
            $asset->supplyer_name      = $request->supplyer_name;
            $asset->quantity           = $request->quantity;
            $asset->warranty           = $request->warranty;
            $asset->invoice_no         = $request->invoice_no;
            $asset->amount             = $request->amount;
            $asset->description        = $request->description;
            $asset->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    // All request data update in
    public function update($request)
    {
        try {
            $asset                     = Asset::find($request->id);
            $asset->company_id         = settings()->id;
            $asset->author             = Auth::user()->id;
            $asset->name               = $request->name;
            $asset->assetcategory_id   = $request->assetcategory_id;
            $asset->hub_id             = $request->hub_id;
            $asset->supplyer_name      = $request->supplyer_name;
            $asset->quantity           = $request->quantity;
            $asset->warranty           = $request->warranty;
            $asset->invoice_no         = $request->invoice_no;
            $asset->amount             = $request->amount;
            $asset->description        = $request->description;
            $asset->save();
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
    // Delete single row in  Model
    public function delete($id){
         $asset = Asset::find($id);
         if($asset->company_id == settings()->id):
            return Asset::destroy($id);
         endif;
         return false;
    }
 
}
