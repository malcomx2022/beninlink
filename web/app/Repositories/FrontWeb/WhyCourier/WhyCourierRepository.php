<?php

namespace App\Repositories\FrontWeb\WhyCourier;

use App\Models\Backend\FrontWeb\WhyCourier;
use App\Models\Backend\Upload;
use App\Repositories\FrontWeb\WhyCourier\WhyCourierInterface;
use Illuminate\Support\Facades\File;

class WhyCourierRepository implements WhyCourierInterface
{

    public function get()
    {
        return WhyCourier::companyWise()->orderBy('position', 'asc')->paginate(10);
    }

    public function getAll()
    {
        return WhyCourier::companyWise()->with('upload')->active()->orderBy('position', 'asc')->get();
    }

    public function getFind($id)
    {
        return WhyCourier::companyWise()->findOrFail($id);
    }

    public function store($request)
    {
        try {
            $whyCourier              = new WhyCourier();
            $whyCourier->company_id     = settings()->id;
            $whyCourier->title       = $request->title;
            if ($request->image) :
                $whyCourier->image_id = $this->imageStoreUpdate($whyCourier->image_id, $request->image);
            endif;
            $whyCourier->position    = $request->position;
            $whyCourier->status      = $request->status;
            $whyCourier->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $whyCourier              = $this->getFind($id);
            $whyCourier->company_id     = settings()->id;
            $whyCourier->title       = $request->title;
            if ($request->image) :
                $whyCourier->image_id = $this->imageStoreUpdate($whyCourier->image_id, $request->image);
            endif;
            $whyCourier->position    = $request->position;
            $whyCourier->status      = $request->status;
            $whyCourier->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function delete($id)
    {
        return WhyCourier::destroy($id);
    }

    // Image Store in Upload Model
    public function imageStoreUpdate($file_id = '', $file)
    {
        try {
            $file_name = '';
            if (!blank($file)) {
                if (!File::exists(public_path('uploads/whycourier'))) :
                    File::makeDirectory(public_path('uploads/whycourier'));
                endif;
                $destinationPath       = public_path('uploads/whycourier');
                $img          = date('YmdHis') . "." . $file->getClientOriginalExtension();
                $file->move($destinationPath, $img);
                $file_name            = 'uploads/whycourier/' . $img;
            }
            if (blank($file_id)) {
                $upload           = new Upload();
            } else {
                $upload           = Upload::find($file_id);
                if (File::exists(public_path($upload->original))) {
                    unlink(public_path($upload->original));
                }
            }
            $upload->original     = $file_name;
            $upload->save();
            return $upload->id;
        } catch (\Exception $e) {
            return null;
        }
    }
}
