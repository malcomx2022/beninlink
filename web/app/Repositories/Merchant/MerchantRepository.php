<?php
namespace App\Repositories\Merchant;

use App\Http\Services\SmsService;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\Backend\Upload;
use App\Models\Backend\Hub;
use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Resources\MerchantParcelExportResource;
use App\Mail\MerchantSignup;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class MerchantRepository implements MerchantInterface{
    public function all(){
        return Merchant::where('company_id',settings()->id)->with('user','user.upload')->orderByDesc('id')->paginate(10);
    }

    public function merchantIdlist(){
        return Merchant::where('company_id',settings()->id)->orderByDesc('id')->select('id','business_name')->get();
    }

    public function all_hubs()
    {
        return Hub::where('company_id',settings()->id)->get();
    }

    public function get($id) {
        return Merchant::where('company_id',settings()->id)->with('user','licensefile','nidfile')->find($id);
    }


    //merchan shop get
    public function merchant_shops_get($id){
        return MerchantShops::where('merchant_id',$id)->get();
    }
    //Store merchant data
    public function store($request) {

        try {

            DB::beginTransaction();
            $cod_charges = [];
            foreach($request->area as $key => $area){
                $cod_charges[$area] = $request->charge[$area];
            }


            $uniqueID                           =  $this->generateUniqueID();
            $merchantUser                       = new User();  
            $merchantUser->unique_id            = $uniqueID;
            $merchantUser->company_id           = settings()->id;
            $merchantUser->name                 = $request->name;
            $merchantUser->mobile               = $request->mobile;
            $merchantUser->email                = $request->email;
            $merchantUser->password             = Hash::make($request->password);
            $merchantUser->address              = $request->address;
            $merchantUser->hub_id               = $request->hub;
            $merchantUser->status               = $request->status;
            $merchantUser->user_type            = UserType::MERCHANT;

            if(isset($request->image_id) && $request->image_id != null) {
                $merchantUser->image_id = $this->merchant_image($merchantUser->image_id, $request->image_id);
            }
            $merchantUser->save();

            

            $merchant                       = new Merchant();
            $merchant->company_id           = settings()->id;
            $merchant->user_id              = $merchantUser->id;
            $merchant->business_name        = $request->business_name;
            // $merchant->merchant_unique_id   = $this->generateUniqueID();
            $merchant->merchant_unique_id   = $uniqueID;

            if($request->opening_balance !==""){
                $merchant->current_balance      = $request->opening_balance;
                $merchant->opening_balance      = $request->opening_balance;
            }
            if($request->vat !==""){
                $merchant->vat              = $request->vat;
            }
            $merchant->cod_charges          = $cod_charges;
            $merchant->address              = $request->address;

            if(isset($request->nid) && $request->nid != null) {
                $merchant->nid_id = $this->merchaant_nid($merchant->nid_id, $request->nid);
            }

            if(isset($request->trade_license) &&$request->trade_license != null) {
                $merchant->trade_license = $this->trade_license($merchant->trade_license, $request->trade_license);
            }

            if($request->payment_period):
                $merchant->payment_period = $request->payment_period == 0? 0: $request->payment_period;
            else:
                $merchant->payment_period = $request->payment_period == 0? 0: $request->payment_period;
            endif;

            $merchant->return_charges         = $request->return_charges ? $request->return_charges :100;
            $merchant->reference_name         = $request->reference_name;
            $merchant->reference_phone        = $request->reference_phone;
            $merchant->wallet_use_activation  = $request->wallet_use_activation;
            $merchant->save();
            $shop               = new MerchantShops();
            $shop->merchant_id  = $merchant->id;
            $shop->name         = $merchant->business_name;
            $shop->contact_no   = $request->mobile;
            $shop->address      = $request->address;
            $shop->status       = $request->status;
            $shop->default_shop = Status::ACTIVE;
            $shop->save();
            $deliveryCharges =  DeliveryCharge::companywise()->with('category')->orderBy('position')->get();
            if(!blank($deliveryCharges)){
                foreach ($deliveryCharges as $delivery){
                    $deliveryCharge                      = new MerchantDeliveryCharge();
                    $deliveryCharge->merchant_id         = $merchant->id;
                    $deliveryCharge->delivery_charge_id  = $delivery->id;
                    $deliveryCharge->weight              = $delivery->weight;
                    $deliveryCharge->category_id         = $delivery->category_id;
                    $deliveryCharge->same_day            = $delivery->same_day;
                    $deliveryCharge->next_day            = $delivery->next_day;
                    $deliveryCharge->sub_city            = $delivery->sub_city;
                    $deliveryCharge->outside_city        = $delivery->outside_city;
                    $deliveryCharge->status              = Status::ACTIVE;
                    $deliveryCharge->save();
                }
            }

            DB::commit();
            if( $merchantUser && $merchant):
                $data=[
                    'merchant'      => $merchant,
                    'merchant_user' => $merchantUser
                ];
                Mail::to($merchantUser->email)->send(new MerchantSignup($data));
            endif;
            return true;
        }
        catch (\Exception $e) {
            // dd($e);
            DB::rollBack();
            return false;
        }
    }
    //Sign up store merchant data
    public function signUpStore($request) {
        try {
            DB::beginTransaction();
            $otp                                = random_int(10000, 99999);
            $merchantUser                       = new User();
            $uniqueID                           =  $this->generateUniqueID();
            $merchantUser->unique_id            = $uniqueID;
            $merchantUser->company_id           = settings()->id;

            $merchantUser->name                 = $request->full_name;
            $merchantUser->mobile               = $request->mobile;
            $merchantUser->email                = $request->email;
            $merchantUser->password             = Hash::make($request->password);
            $merchantUser->user_type            = UserType::MERCHANT;
            $merchantUser->verification_status  = Status::INACTIVE;
            $merchantUser->otp                  = $otp;
            $merchantUser->hub_id               = $request->hub_id;
            $merchantUser->permissions          = [];
            $merchantUser->save();
            $merchant                           = new Merchant();
            $merchant->company_id               = settings()->id;
            $merchant->user_id                  = $merchantUser->id;
            $merchant->business_name            = $request->business_name;
            $merchant->merchant_unique_id       = $uniqueID;
            $merchant->cod_charges              = array(
                'inside_city'    => "0",
                'sub_city'       => "0",
                'outside_city'   => "0",
            );
            $merchant->address                  = $request->address;
            $merchant->opening_balance          = 0;
            $merchant->vat                      = 0;
            $merchant->save();

            $shop               = new MerchantShops();
            $shop->merchant_id  = $merchant->id;
            $shop->name         = $merchant->business_name;
            $shop->contact_no   = $request->mobile;
            $shop->address      = $request->address;
            if($request->status):
                $shop->status       = $request->status;
            else:
                $shop->status       = Status::ACTIVE;
            endif;
            $shop->default_shop = Status::ACTIVE;

            $shop->save();

            $deliveryCharges =  DeliveryCharge::companywise()->with('category')->orderBy('position')->get();

            if(!blank($deliveryCharges)){
                foreach ($deliveryCharges as $delivery){
                    $deliveryCharge                      = new MerchantDeliveryCharge();
                    $deliveryCharge->merchant_id         = $merchant->id;
                    $deliveryCharge->delivery_charge_id  = $delivery->id;
                    $deliveryCharge->weight              = $delivery->weight;
                    $deliveryCharge->category_id         = $delivery->category_id;
                    $deliveryCharge->same_day            = $delivery->same_day;
                    $deliveryCharge->next_day            = $delivery->next_day;
                    $deliveryCharge->sub_city            = $delivery->sub_city;
                    $deliveryCharge->outside_city        = $delivery->outside_city;
                    $deliveryCharge->status              = Status::ACTIVE;
                    $deliveryCharge->save();
                }
            }

            session([
                'otp'     => $otp,
                'merchant_id'     => $merchantUser->unique_id,
                'mobile'  => $request->mobile,
                'password'=> $request->password
            ]);
            $response =  app(SmsService::class)->sendOtp($merchantUser->mobile,$merchantUser->otp);
            DB::commit();
            return true;
       }
        catch (\Exception $e) { 
            DB::rollBack();
            return false;
        }
    }
    // Resend OTP
    public function resendOTP($request) {
        try {
            $otp                                = random_int(10000, 99999);
            $merchantUser = User::companywise()->where('mobile', $request->mobile)->first(); 
            $merchantUser->otp                  = $otp;
            $merchantUser->save();

            session([
                'otp'     => $otp,
                'mobile'  => $request->mobile,
            ]);
            $response =  app(SmsService::class)->sendOtp($merchantUser->mobile,$merchantUser->otp);
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    // OTP verification
    public function otpVerification($request) {
        try {

            $merchantUser     = User::where('mobile', $request->mobile)->where('otp', $request->otp)->first();
            if($merchantUser != null){
                $merchantUser->verification_status = Status::ACTIVE;
                $merchantUser->save();
                return $merchantUser;
            }
            else
                return 0;

        }
        catch (\Exception $e) {
            return false;
        }
    }

    //update merchant data
    public function update($id,$request) {

        $merchant = Merchant::find($id);

        try {
            DB::beginTransaction();
            $cod_charges = [];
            foreach($request->area as $key => $area){
                $cod_charges[$area] = $request->charge[$area];
            }

            $merchantUser                       = User::find($merchant->user_id);
            $merchantUser->name                 = $request->name;
            $merchantUser->mobile               = $request->mobile;
            $merchantUser->email                = $request->email;
            if($request->password != null)
                $merchantUser->password         = Hash::make($request->password);
            $merchantUser->address              = $request->address;
            $merchantUser->user_type            = UserType::MERCHANT;
            $merchantUser->hub_id               = $request->hub;
            $merchantUser->status               = $request->status;

            if($request->image_id != null) {
                $merchantUser->image_id = $this->merchant_image($merchantUser->image_id, $request->image_id);
            }

            $merchantUser->save();

            // Merchant row
            $merchant->business_name        = $request->business_name;
            if($request->opening_balance !==""){
                $merchant->current_balance      = $request->opening_balance;
                $merchant->opening_balance      = $request->opening_balance;
            }
            ;
            if($request->vat !==""){
            $merchant->vat                  = $request->vat;
            }
            $merchant->cod_charges          = $cod_charges;
            $merchant->address              = $request->address;

            if(isset($request->nid) && $request->nid != null) {
                $merchant->nid_id = $this->merchaant_nid($merchant->nid_id, $request->nid);
            }

            if(isset($request->trade_license) &&$request->trade_license != null) {
                $merchant->trade_license = $this->trade_license($merchant->trade_license, $request->trade_license);
            }

            if($request->payment_period):

                $merchant->payment_period = $request->payment_period == 0? 0: $request->payment_period;
            else:
                $merchant->payment_period = $request->payment_period == 0? 0: $request->payment_period;
            endif;

            if($request->return_charges):
                $merchant->return_charges            = $request->return_charges ;
            endif;
            if($request->reference_name):
                $merchant->reference_name        = $request->reference_name ;
            endif;
            if($request->reference_phone):
                $merchant->reference_phone       = $request->reference_phone;
            endif; 
            $merchant->wallet_use_activation  = $request->wallet_use_activation;
            $merchant->save();
            DB::commit();
            return true;
        }
        catch (\Exception $e) { 
            DB::rollBack();
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
    // for unique id ganarate
    public function generateUniqueID() {
        do {
            $merchant_unique_id = random_int(100000, 999999);
        } while (User::where("unique_id", "=", $merchant_unique_id)->first());
        return $merchant_unique_id;
    }

    public function delete($id) {
        try {
            // Find merchant row
            $merchant = Merchant::find($id);

            if($merchant->company_id == settings()->id):

                // Find user row
                $user     = User::find($merchant->user_id);
    
                $upload     = Upload::find($user->image_id);
                if($upload != null && file_exists($upload->original))
                {
                    unlink($upload->original);
                    $upload->delete();
                }
            
                $nid     = Upload::find($merchant->nid_id);
                if($nid != null && file_exists($nid->original))
                {
                    unlink($nid->original);
                    $nid->delete();
                }


                $trade_license     = Upload::find($merchant->trade_license);
                if($trade_license != null && file_exists($trade_license->original))
                {
                    unlink($trade_license->original);
                    $trade_license->delete();
                } 
                // Delete merchant row
                $merchant->delete();
                
                $user->delete();
                return true;
            endif;
            return false;

        }
        catch (\Exception $e) {
            return false;
        }

    }
 
    //social login merchant signup

        public function socialSignupStore($request,$social) {

            try {
                DB::beginTransaction();
                $otp                                = random_int(10000, 99999);

                $uniqueID  =  $this->generateUniqueID();

                $merchantUser                       = new User();
                $merchantUser->company_id           = settings()->id;
                $merchantUser->name                 = $request->name;
                $merchantUser->email                = $request->email;
                if($social == 'google'):
                    $merchantUser->google_id        = $request->id;
                elseif($social == 'facebook'):
                    $merchantUser->facebook_id      = $request->id;
                endif;
                $merchantUser->image_id             = $this->linktoAvatarUpload($request,$request->avatar_original);
                $merchantUser->password             = Hash::make(\Str::random(32));
                $merchantUser->user_type            = UserType::MERCHANT;
                $merchantUser->hub_id               = 1;
                $merchantUser->role_id              = null;
                $merchantUser->permissions          = [];
                $merchantUser->unique_id            = $uniqueID;
                $merchantUser->save();

                $merchant                           = new Merchant();
                $merchant->company_id               = settings()->id;
                $merchant->user_id                  = $merchantUser->id;
                $merchant->business_name            = $request->name;
                $merchant->merchant_unique_id       =  $uniqueID;
                $merchant->cod_charges              = array(
                    'inside_city'    => "0",
                    'sub_city'       => "0",
                    'outside_city'   => "0",
                );
                $merchant->opening_balance          = 0;
                $merchant->vat                      = 0;
                $merchant->save();

                $shop               = new MerchantShops();
                $shop->merchant_id  = $merchant->id;
                $shop->name         = $merchant->business_name;
                $shop->default_shop = Status::ACTIVE;
                $shop->save();

                $deliveryCharges =  DeliveryCharge::companywise()->with('category')->orderBy('position')->get();

                if(!blank($deliveryCharges)){
                    foreach ($deliveryCharges as $delivery){
                        $deliveryCharge                      = new MerchantDeliveryCharge();
                        $deliveryCharge->merchant_id         = $merchant->id;
                        $deliveryCharge->delivery_charge_id  = $delivery->id;
                        $deliveryCharge->weight              = $delivery->weight;
                        $deliveryCharge->category_id         = $delivery->category_id;
                        $deliveryCharge->same_day            = $delivery->same_day;
                        $deliveryCharge->next_day            = $delivery->next_day;
                        $deliveryCharge->sub_city            = $delivery->sub_city;
                        $deliveryCharge->outside_city        = $delivery->outside_city;
                        $deliveryCharge->status              = Status::ACTIVE;
                        $deliveryCharge->save();
                    }
                }

                DB::commit();
                return $merchantUser;
           }
            catch (\Exception $e) {

                DB::rollBack();
                return false;
            }
        }


        protected function linktoAvatarUpload($user=null,$avatar_original){
           
            try {
                //profile upload
                $file             = file_get_contents($avatar_original);
                $file_name        = date('YmdHisA').$user->id.'.jpg';
                if(!File::isDirectory(public_path('uploads/profile'))):
                    File::makeDirectory(public_path('uploads/profile'));
                endif;
                File::put(public_path('uploads/profile/').$file_name, $file);
                $file_full_path   = 'uploads/profile/'.$file_name;
                $upload           = new Upload();
                $upload->original = $file_full_path;
                $upload->save();
                //end profile upload

                return $upload->id;

            } catch (\Throwable $th) {
                return null;
            }
        }



}
