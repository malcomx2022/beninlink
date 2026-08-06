<?php

namespace  App\Repositories\Superadmin\Company;

use App\Enums\BooleanStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Services\SmsService;
use App\Mail\CompanySignup;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Models\Backend\FrontWeb\Blog;
use App\Models\Backend\FrontWeb\Faq;
use App\Models\Backend\FrontWeb\Partner;
use App\Models\Backend\FrontWeb\Service;
use App\Models\Backend\FrontWeb\SocialLink;
use App\Models\Backend\FrontWeb\WhyCourier;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\NotificationSettings;
use App\Models\Backend\SmsSendSetting;
use App\Models\Backend\SmsSetting;
use App\Models\Backend\Subscription;
use App\Models\Backend\Superadmin\Plan;
use App\Models\Backend\Upload;
use App\Models\Config;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use App\Repositories\User\UserInterface;
use App\Repositories\Superadmin\Company\CompanyInterface;
use Carbon\Carbon;
use Database\Seeders\CompanyFrontendDataSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Stancl\Tenancy\Database\Models\Domain;

class CompanyRepository implements CompanyInterface
{
    protected $userRepo, $generalSettingRepo;
    public function __construct(UserInterface $userRepo, GeneralSettingsInterface $generalSettingRepo)
    {
        $this->userRepo             = $userRepo;
        $this->generalSettingRepo   = $generalSettingRepo;
    }
    public function get()
    {
        return User::where('user_type', UserType::ADMIN)->where('company_owner', BooleanStatus::YES)->orderByDesc('id')->paginate(10);
    }

    public function getFind($id)
    {
        return User::where('id', $id)->where('user_type', UserType::ADMIN)->where('company_owner', BooleanStatus::YES)->first();
    }

    public function company_create($request, $id = null)
    {


        try {
            if ($id) :
                $company                       =  GeneralSettings::find($id);
            else :
                $company                       = new GeneralSettings();
            endif;
            $company->name                 = $request->company_name;
            $company->phone                = $request->mobile;
            $company->email                = $request->email;
            $company->address              = $request->address;
            $company->currency             = $request->currency;
            $company->copyright            = settings()->copyright;
            $company->current_version      = settings()->current_version;
            $company->par_track_prefix     = Str::upper($request->par_track_prefix);
            $company->invoice_prefix       = Str::upper($request->invoice_prefix);
            if (isset($request->logo) && $request->logo != null) {

                $company->logo = $this->file('', $request->logo);
            }
            $company->favicon = 9;
            $company->status               = $request->status;
            $company->save();
            if ($company) :
                if (!$id) :
                    $this->smsSendSetting($company->id);
                    $this->smsSetting($company->id);
                    $this->configData($company->id);
                    $this->notificationSettings($company->id);
                endif;
                return $company->id;
            endif;
            return false;
        } catch (\Throwable $th) {

            return false;
        }
    }
    public function smsSendSetting($company_id)
    {
        try {

            foreach (trans('SmsSendStatus') as $key => $status) {
                $smsSendSetting                       = new SmsSendSetting();
                $smsSendSetting->company_id           = $company_id;
                $smsSendSetting->sms_send_status      = $key;
                $smsSendSetting->status               = Status::INACTIVE;
                $smsSendSetting->save();
            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function configData($company_id)
    {
        try {

            $config = [
                'fragile_liquid_status'         => Status::ACTIVE,
                'fragile_liquid_charge'         => 20,
                'same_day'                      => 1,
                'next_day'                      => 1,
                'sub_city'                      => 1,
                'outside_City'                  => 1
            ];
            foreach ($config as $key => $value) {
                $confg           = new Config();
                $confg->company_id   = $company_id;
                $confg->key      = $key;
                $confg->value    = $value;
                $confg->save();
            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function notificationSettings($company_id)
    {
        try {
            $notification                  = new NotificationSettings();
            $notification->company_id      = $company_id;
            $notification->fcm_secret_key  = "";
            $notification->fcm_topic       = "";
            $notification->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function smsSetting($company_id)
    {
        try {
            //SMS settings
            //REVE SMS
            SmsSetting::create(['company_id' => $company_id, 'key' => 'reve_api_key',      'value' => '']);
            SmsSetting::create(['company_id' => $company_id, 'key' => 'reve_secret_key',   'value' => '']);
            SmsSetting::create(['company_id' => $company_id, 'key' => 'reve_api_url',      'value' => '']);
            SmsSetting::create(['company_id' => $company_id, 'key' => 'reve_username',      'value' => '']);
            SmsSetting::create(['company_id' => $company_id, 'key' => 'reve_user_password', 'value' => '']);
            SmsSetting::create(['company_id' => $company_id, 'key' => 'reve_status',        'value' => Status::INACTIVE]);
            //Twilio SMS
            SmsSetting::create(['company_id' => $company_id, 'key' => 'twilio_sid',         'value' => '']);
            SmsSetting::create(['company_id' => $company_id, 'key' => 'twilio_token',       'value' => '']);
            SmsSetting::create(['company_id' => $company_id, 'key' => 'twilio_from',        'value' => '']);
            SmsSetting::create(['company_id' => $company_id, 'key' => 'twilio_status',      'value' => Status::INACTIVE]);

            //NEXMO SMS
            SmsSetting::create(['company_id' => $company_id, 'key' => 'nexmo_key',           'value' => '']);
            SmsSetting::create(['company_id' => $company_id, 'key' => 'nexmo_secret_key',    'value' => '']);
            SmsSetting::create(['company_id' => $company_id, 'key' => 'nexmo_status',         'value' => Status::INACTIVE]);

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function permissions($plan)
    {

        $array = [];
        $permissions                = Permission::whereIn('attribute', $plan->modules ?? [])->pluck('keywords');

        foreach ($permissions as $key => $module) {
            foreach ($module as $key => $value) {
                $array[] = $value;
            }
        }
        return $array;
    }

    public function store($request)
    {
     
        try {
            DB::beginTransaction();
            $company_id  = $this->company_create($request);
            if ($company_id) :

             

                //domain create  
                $tenant =  Tenant::create(['id' => $request->domain,'company_id'=>$company_id]);
                $tenant->domains()->create(['domain' => $request->domain.'.'.get_host(),'domain_name'=>$request->domain]);
                //end domain create
              
                $plan                   = Plan::find($request->plan_id);
                $permissions            = $this->permissions($plan);
                $user                   = new User();
                $user->company_owner    = BooleanStatus::YES;
                $user->company_id       = $company_id;
                $user->name             = $request->name;
                $user->email            = $request->email;
                $user->password         = Hash::make($request->password);
                $user->mobile           = $request->mobile;
                $user->user_type        = UserType::ADMIN;
                $user->nid_number       = $request->nid_number;
                $user->designation_id   = $request->designation_id;
                $user->department_id    = $request->department_id;
                $user->image_id         = $this->file('', $request->image);
                $user->joining_date     = $request->joining_date;
                $user->address          = $request->address;
                $user->permissions      = $permissions;
                $user->status           = $request->status;
                $user->save();

                //subscription
                $subscription               = new Subscription();
                $subscription->company_id   = $company_id;
                $subscription->user_id      = $user->id;
                $subscription->plan_id      = $plan->id;
                $subscription->parcel_count = $plan->parcel_count;
                $subscription->days_count   = $plan->days_count;
                $subscription->deliveryman_count = $plan->deliveryman_count;
                $subscription->price        = $plan->price;
                $subscription->start_date   = Carbon::now()->toDateTimeString();
                $subscription->expired_date = Carbon::now()->addDays($plan->days_count)->toDateTimeString();
                $subscription->save();
                //end subscription

                $company                  = GeneralSettings::find($company_id);
                $company->subscription_id = $subscription->id;
                $company->plan_id         = $plan->id;
                $company->save();
 
                // Company Site Data
                if ($user) :
                    (new CompanyFrontendDataSeeder)->companySiteData($company_id);
                endif;
                 

                DB::commit();

                return true;
            endif;
            return false;
        } catch (\Throwable $th) {

            DB::rollBack();
            return false;
        }
    }
    public function update($id, $request)
    {
        try {
            DB::beginTransaction();
            $user                   = User::find($id);
            $company_id  = $this->company_create($request, $user->company_id);
            if ($company_id) :

                //domain update  
                $tenant =  Tenant::whereId($user->tenantDetails->id)->first(); 
                $domain =  Domain::where('tenant_id',$user->tenantDetails->id)->first();
                $domain->domain         = $request->domain.'.'.get_host();
                $domain->domain_name    = $request->domain;
                $domain->save(); 
                $tenant->id = $request->domain;  
                $tenant->save();
                //end domain update
             

                $plan                   = Plan::find($request->plan_id);
                $permissions            = $this->permissions($plan);

                $user->company_owner    = BooleanStatus::YES;
                $user->company_id       = $company_id;
                $user->name             = $request->name;
                $user->email            = $request->email;
                if (!empty($request->password)) :
                    $user->password         = Hash::make($request->password);
                endif;
                $user->mobile           = $request->mobile;
                $user->user_type        = UserType::ADMIN;
                $user->nid_number       = $request->nid_number;
                $user->designation_id   = $request->designation_id;
                $user->department_id    = $request->department_id;
                $user->image_id         = $this->file('', $request->image);
                $user->joining_date     = $request->joining_date;
                $user->address          = $request->address;
                $user->permissions      = $permissions;
                $user->status           = $request->status;
                $user->save();

                $company                    = GeneralSettings::find($company_id);
                //subscription
                $subscription               = Subscription::find($company->subscription_id);
                $subscription->company_id   = $company_id;
                $subscription->user_id      = $user->id;
                $subscription->plan_id      = $plan->id;
                $subscription->parcel_count = $plan->parcel_count;
                $subscription->days_count   = $plan->days_count;
                $subscription->deliveryman_count = $plan->deliveryman_count;
                $subscription->price        = $plan->price;
                $subscription->expired_date = Carbon::parse($subscription->start_date)->addDays($plan->days_count)->toDateTimeString();
                $subscription->save();
                //end subscription

                $company->subscription_id   = $subscription->id;
                $company->plan_id           =  $plan->id;
                $company->save();
                DB::commit();
                return true;
            endif;
            return false;
        } catch (\Throwable $th) { 
            DB::rollBack();
            return false;
        }
    }


    public function delete($id)
    {

        try {
            return GeneralSettings::destroy($id);
        } catch (\Throwable $th) {
            return false;
        }
    }


    public function switchPlan($request)
    {
        try {
            $plan                   = Plan::find($request->plan_id);
            $permissions            = $this->permissions($plan);
            $user                   = User::find($request->user_id);
            $user->permissions      = $permissions;
            $user->save();

            $company                    = GeneralSettings::find($user->company_id);

            //subscription
            $subscription               = new Subscription();
            $subscription->company_id   = $company->id;
            $subscription->user_id      = $user->id;
            $subscription->plan_id      = $plan->id;
            $subscription->days_count   = $plan->days_count;
            $subscription->parcel_count = $plan->parcel_count;
            $subscription->deliveryman_count = $plan->deliveryman_count;
            $subscription->price        = $plan->price;
            $subscription->start_date   = Carbon::now()->toDateTimeString();
            $subscription->expired_date = Carbon::now()->addDays($plan->days_count)->toDateTimeString();
            $subscription->save();
            //end subscription

            $company->subscription_id   = $subscription->id;
            $company->plan_id           =  $plan->id;
            $company->save();
            return true;
        } catch (\Throwable $th) {

            return false;
        }
    }


    // Request image Store in Upload Model and image copy file attach in public/upload/user folder.
    public function file($image_id = '', $image)
    {
        try {
            $image_name = '';
            if (!blank($image)) {
                $destinationPath       = public_path('uploads/users');
                $profileImage          = date('YmdHis') . "." . $image->getClientOriginalExtension();
                $image->move($destinationPath, $profileImage);
                $image_name            = 'uploads/users/' . $profileImage;
            }
            if (blank($image_id)) {
                $upload           = new Upload();
            } else {
                $upload           = Upload::find($image_id);
                if (file_exists(public_path($upload->original))) {
                    unlink(public_path($upload->original));
                }
            }
            $upload->original     = $image_name;
            $upload->save();
            return $upload->id;
        } catch (\Exception $e) {
            return false;
        }
    }


    public function signUpStore($request)
    {
        try {
            DB::beginTransaction();
            $companyData                 = new Request();
            $companyData['company_name'] = $request->company_name;
            $companyData['mobile']       = $request->mobile;
            $companyData['email']        = $request->email;
            $companyData['address']      = $request->address;
            $companyData['currency']     = '$';
            $companyData['par_track_prefix']    = '';
            $companyData['invoice_prefix']      = '';
            $companyData['status']              = Status::ACTIVE;

            $company_id  = $this->company_create($companyData);
            if ($company_id) :

                //domain create  
                $tenant =  Tenant::create(['id' => $request->domain,'company_id'=>$company_id]);
                $tenant->domains()->create(['domain' => $request->domain.'.'.get_host(),'domain_name'=>$request->domain]);
                //end domain create

                $plan                   = Plan::first();
                $permissions            = []; 
                $otp                                = random_int(10000, 99999); 
                $user                   = new User();
                $user->company_owner    = BooleanStatus::YES;
                $user->company_id       = $company_id;
                $user->name             = $request->name;
                $user->email            = $request->email;
                $user->password         = Hash::make($request->password);
                $user->mobile           = $request->mobile;
                $user->user_type        = UserType::ADMIN;
                $user->designation_id   = Designation::first()->id;
                $user->department_id    = Department::first()->id;
                $user->address          = $request->address;
                $user->permissions      = $permissions;
                $user->status           = Status::ACTIVE;
                $user->otp              = $otp;
                $user->verification_status  = Status::INACTIVE;
                $user->save();

                $subscription               = new Subscription();
                $subscription->company_id   = $company_id;
                $subscription->user_id      = $user->id;
                $subscription->plan_id      = $plan->id;
                $subscription->days_count   = 0;
                $subscription->parcel_count = 0;
                $subscription->deliveryman_count = 0;
                $subscription->price        = 0;
                $subscription->start_date   = Carbon::now()->subDay(5)->toDateTimeString();
                $subscription->expired_date = Carbon::now()->subDay(2)->toDateTimeString();
                $subscription->save();

                $company                  = GeneralSettings::find($company_id);
                $company->subscription_id = $subscription->id;
                $company->plan_id         = $plan->id;
                $company->save();

                session([
                    'otp'     => $otp,
                    'email'   => $request->email,
                    'password' => $request->password
                ]);

                if ($user && $company) :
                    $data = [
                        'user'      => $user,
                        'otp'       => $otp
                    ];
                    Mail::to($user->email)->send(new CompanySignup($data));
                endif;

                // Company Site Data
                if ($user && $company) :
                  (new CompanyFrontendDataSeeder)->companySiteData($company->id);
                endif;
                DB::commit();
                return true;
            endif;
            return false;
        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }
 

    // Resend OTP
    public function resendOTP($request)
    {
        try {
            $otp                    = random_int(10000, 99999);
            $merchantUser           = User::where('email', $request->email)->first();
            $merchantUser->otp      = $otp;
            $merchantUser->save();

            $data = [
                'user'      => $merchantUser,
                'otp'       => $otp
            ];
            Mail::to($merchantUser->email)->send(new CompanySignup($data));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }



    // OTP verification
    public function otpVerification($request)
    {
        try {

            $merchantUser     = User::where('email', $request->email)->where('otp', $request->otp)->first();
            if ($merchantUser != null) {
                $merchantUser->verification_status = Status::ACTIVE;
                $merchantUser->otp = null;
                $merchantUser->save();
                return $merchantUser;
            } else
                return 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
