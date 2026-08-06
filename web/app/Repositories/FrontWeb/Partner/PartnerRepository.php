<?php

namespace App\Repositories\FrontWeb\Partner;

use App\Repositories\FrontWeb\Partner\PartnerInterface;
use App\Models\Backend\FrontWeb\Partner;
use App\Models\Backend\Upload;
use Illuminate\Support\Facades\File;

class PartnerRepository implements PartnerInterface
{
    public function get()
    {
        return Partner::companyWise()->orderBy('position', 'asc')->paginate(10);
    }

    public function getAll()
    {
        return Partner::companyWise()->with('upload')->active()->orderBy('position', 'asc')->get();
    }

    public function getFind($id)
    {
        return Partner::companyWise()->findOrFail($id);
    }

    public function store($request)
    {
        try {
            $partner           = new Partner();
            $partner->company_id     = settings()->id;
            $partner->name     = $request->name;
            if ($request->image) :
                $partner->image_id    = $this->imageStoreUpdate('', $request->image);
            endif;
            $partner->link     = $request->link;
            $partner->position = $request->position;
            $partner->status   = $request->status;
            $partner->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $partner           = $this->getFind($id);
            $partner->company_id     = settings()->id;
            $partner->name     = $request->name;
            if ($request->image) :
                $partner->image_id    = $this->imageStoreUpdate($partner->image_id, $request->image);
            endif;
            $partner->link     = $request->link;
            $partner->position = $request->position;
            $partner->status   = $request->status;
            $partner->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function delete($id)
    {
        return Partner::destroy($id);
    }

    // Image Store in Upload Model
    public function imageStoreUpdate($file_id = '', $file)
    {
        try {
            $file_name = '';
            if (!blank($file)) {
                if (!File::exists(public_path('uploads/partner'))) :
                    File::makeDirectory(public_path('uploads/partner'));
                endif;
                $destinationPath       = public_path('uploads/partner');
                $img          = date('YmdHis') . "." . $file->getClientOriginalExtension();
                $file->move($destinationPath, $img);
                $file_name            = 'uploads/partner/' . $img;
            }

            if (blank($file_id)) {
                $upload           = new Upload();
            } else {
                $upload           = Upload::find($file_id);
                if (file_exists(public_path($upload->original))) {
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
