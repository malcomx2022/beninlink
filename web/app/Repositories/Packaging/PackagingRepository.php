<?php
namespace App\Repositories\Packaging;
use App\Models\Backend\Packaging;
use App\Repositories\Packaging\PackagingInterface;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Upload;

class PackagingRepository implements PackagingInterface{
    public function all(){
        return Packaging::where('company_id',settings()->id)->orderBy('position')->paginate(10);
    }

    public function get($id){
        return Packaging::where(['company_id'=>settings()->id,'id'=>$id])->first();
    }

    public function store($request){
        try {
            $packaging               = new Packaging();
            $packaging->company_id   = settings()->id;
            $packaging->name         = $request->name;
            $packaging->price        = $request->price;
            $packaging->status       = $request->status;
            $packaging->position     = $request->position;
            $packaging->photo        = $this->file('', $request->image);
            $packaging->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($request)
    {
        try {
            $packaging                  = Packaging::find($request->id);
            $packaging->company_id      = settings()->id;
            $packaging->name            = $request->name;
            $packaging->price           = $request->price;
            $packaging->status          = $request->status;
            $packaging->position        = $request->position;
            if(isset($request->image) && $request->image != null)
            {
                $packaging->photo = $this->file($packaging->photo, $request->image);
            }
            $packaging->save();
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id){
        try {
            $row = Packaging::with('upload')->find($id);
            if($row->company_id == settings()->id):
                Upload::destroy($row->upload->id);
                if(file_exists($row->upload->original))
                    unlink($row->upload->original);
                $row->delete();
                return true;
            endif;
            return false;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function file($image_id = '', $image)
    {
        try {

            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/packaging');
                $profileImage          = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/packaging/'.$profileImage;
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
