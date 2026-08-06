<?php
namespace App\Repositories\MerchantProfile;
use App\Models\User;
use App\Models\Backend\Merchant;
use App\Models\Backend\Upload;
use Illuminate\Support\Facades\Hash;
use App\Repositories\MerchantProfile\MerchantProfileInterface;
use Illuminate\Support\Facades\File;

class MerchantProfileRepository implements MerchantProfileInterface{

    public function get($id){ // auth id
        return Merchant::where('user_id', $id)->with('user','licensefile','nidfile','user.upload')->first();
    }

    public function update($id, $request)
    {
        try {

            $merchantUser                       = User::find($id);
            $merchantUser->name                 = $request->name;
            $merchantUser->mobile               = $request->mobile;
            $merchantUser->email                = $request->email;

            if($request->image_id != null) {
                $merchantUser->image_id = $this->merchant_image($merchantUser->image_id, $request->image_id);
            }
            $merchantUser->save();
 
            // Merchant row
            $merchant = Merchant::where('user_id',$id)->first();
            if($merchant):
                $merchant->business_name        = $request->business_name;
                $merchant->address              = $request->address;

                if(isset($request->nid) && $request->nid != null) {
                    $merchant->nid_id        = $this->merchaant_nid($merchant->nid_id, $request->nid);
                }

                if(isset($request->trade_license) &&$request->trade_license != null) {
                    $merchant->trade_license = $this->trade_license($merchant->trade_license, $request->trade_license);
                }
                $merchant->save();
            endif;
            return true;

        } catch (\Exception $e) {

            return false;
        }
    }

    // for merchant image upload
    public function merchant_image($image_id = '', $image) {
        try {

            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/merchant/image');
                $merchantImage         = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $merchantImage);
                $image_name            = 'uploads/merchant/image/'.$merchantImage;
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
    // for trade_license upload
    public function trade_license ($trade_license  = '', $image) {
        try {

            $image_name = '';
            if(!blank($image)){
                $destinationPath       = public_path('uploads/merchant/trade_license');
                $tradeLicense          = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $tradeLicense);
                $image_name            = 'uploads/merchant/trade_license/'.$tradeLicense;
            }

            if(blank($trade_license)){
                $upload           = new Upload();
            }else{
                $upload           = Upload::find($trade_license);
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
    // for merchant nid upload
    public function merchaant_nid ($nid_id  = '', $image) {
        try {

            $image_name = '';
            if(!blank($image)){
                $destinationPath = public_path('uploads/merchant/nid');
                $nid             = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $nid);
                $image_name      = 'uploads/merchant/nid/'.$nid;
            }
            if(blank($nid_id)){
                $upload           = new Upload();
            }else{
                $upload           = Upload::find($nid_id);
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
