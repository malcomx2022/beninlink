<?php
namespace App\Repositories\Profile;
use App\Models\User;
use App\Models\Backend\Hub;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Models\Backend\Upload;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Profile\ProfileInterface;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;

class ProfileRepository implements ProfileInterface{

    public function get($id){
        return User::with('upload')->find($id);
    }

    public function update($id, $request)
    {
        try {
            $user                   = User::find($id);
            $user->name             = $request->name;
            $user->address          = $request->address;
            if($request->image != null)
            {
                $user->image_id = $this->file($user->image_id, $request->image);
            }
            $user->save();
            return true;

        } catch (\Exception $e) {

            return false;
        }
    }

    public function updatePassword($id, $request)
    {
        try {

            $user = User::find($id);
            if(Hash::check($request->old_password , $user->password ) )
            {
                $user->password = Hash::make($request->new_password);
                $user->save();
                return true;
            }
            else
                return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function file($image_id = '', $image)
    {
        try {

            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/users');
                $profileImage          = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/users/'.$profileImage;
            }

            if(blank($image_id)){
                $upload           = new Upload();
            }else{
                $upload           = Upload::find($image_id);
                if(File::exists($upload->original)){
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
