<?php
namespace App\Repositories\GeneralSettings;

use App\Enums\UserType;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Upload;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class GeneralSettingsRepository implements GeneralSettingsInterface{

    public function all(){

        $row =  GeneralSettings::with('rxlogo','rxfavicon')->where(function($query){
            if(Auth::user() && Auth::user()->user_type != UserType::SUPER_ADMIN):
                $query->where('id',Auth::user()->company_id);
            else:
                $query->where('id',1);
            endif;
        })->first();
        return $row;
    }

    public function update($request){

        $row               = GeneralSettings::with('rxlogo','rxfavicon')->where(function($query){
            if(Auth::user() && Auth::user()->user_type != UserType::SUPER_ADMIN):
                $query->where('id',Auth::user()->company_id);
            else:
                $query->where('id',1);
            endif;
        })->first();
        $row->name         = $request->name;
        $row->phone        = $request->phone;
        $row->email        = $request->email;
        $row->address      = $request->address;
        $row->currency     = $request->currency;
        $row->copyright    = $request->copyright;
        $row->par_track_prefix     = Str::upper($request->par_track_prefix);
        $row->invoice_prefix       = Str::upper($request->invoice_prefix);
        if($request->primary_color):
            $row->primary_color        = $request->primary_color;
        endif;
        if($request->text_color):
            $row->text_color           = $request->text_color;
        endif;

        if(isset($request->logo) && $request->logo != null)
        {
            $row->logo = $this->file($row->logo, $request->logo);
        }
        if(isset($request->light_logo) && $request->light_logo != null)
        {
            $row->light_logo = $this->file($row->light_logo, $request->light_logo);
        }
        if(isset($request->favicon) && $request->favicon != null)
        {
            $row->favicon = $this->file($row->favicon, $request->favicon);
        }
        $row->save();
        return $row;

    }

    public function file($image_id = '', $image)
    {
         
        try {
            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/settings');
                $profileImage          = date('YmdHis') .random_int(1000,9999). "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/settings/'.$profileImage;
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
