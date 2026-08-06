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
            $Deliverycategory                   = Deliverycategory::find($request->id);
            $Deliverycategory->company_id       = settings()->id;
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
        $cateogry   = Deliverycategory::find($id);
        if($cateogry->company_id == settings()->id):
            return Deliverycategory::destroy($id);
        endif;
        return false;
    }
}
