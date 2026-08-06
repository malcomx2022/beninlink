<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\OtpRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateRequest;
use App\Http\Resources\v10\DeliverymanUserResource;
use App\Http\Resources\v10\UserResource;
use App\Models\User;
use App\Repositories\MerchantProfile\MerchantProfileInterface;
use App\Repositories\Profile\ProfileInterface;
use Illuminate\Http\Request;
use App\Traits\ApiReturnFormatTrait;
use App\Repositories\Merchant\MerchantInterface;
use App\Http\Requests\Merchant\SignUpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiReturnFormatTrait;

    protected $merchantRepo;
    protected $profileRepo;
    protected $merchantProfile;
    public function __construct(MerchantInterface $merchantRepo,ProfileInterface $profileRepo,MerchantProfileInterface $merchantProfile)
    {
        $this->merchantRepo = $merchantRepo;
        $this->profileRepo = $profileRepo;
        $this->merchantProfile = $merchantProfile;
    }


    public function register(Request $request)
    {
        $validator = new SignUpRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('merchant.added_msg'), ['message' => $validator->errors()], 422);
        }
        if($this->merchantRepo->signUpStore($request)){
            return $this->responseWithSuccess(__('merchant.added_msg'), ['mobile'=>$request->mobile], 200);
        }else{
            return $this->responseWithError(__('merchant.error_msg'), [], 500);
        }
    }

    public function otpVerification(OtpRequest $request)
    {
        $result     = $this->merchantRepo->otpVerification($request);
        if($result != null){
             $user = User::where('mobile',$result->mobile)->first();
             if($user){
                 Auth::login($user);
                 return $this->responseWithSuccess(__('auth.signin_msg'), ['token' => auth()->user()->createToken($result->mobile)->plainTextToken,'user'=>new UserResource(auth()->user())], 200);
             }else{
                 return $this->responseWithSuccess(__('auth.invalid_otp'), [], 401);
             }
        } elseif($result == 0) {
            return $this->responseWithSuccess(__('auth.invalid_otp'), [], 401);
        } else {
            return $this->responseWithError(__('auth.error_msg'), [], 500);
        }
    }

    public function resendOTP(Request $request)
    {
        $this->merchantRepo->resendOTP($request);
        if($this->merchantRepo->resendOTP($request)){
            return $this->responseWithSuccess(__('auth.resend_otp_msg'), ['mobile'=>$request->mobile], 200);
        }else{
            return $this->responseWithError(__('auth.error_msg'), [], 500);
        }
    }

    public function signin(Request $request)
    {

        $attr = $request->validate([
            'merchant_id'     => 'required|string',
            'password'  => 'required|string|min:6'
        ]);
 
        $attr = ['unique_id'=>$request->get('merchant_id'),'password'=>$request->get('password')];
      
        if (!Auth::attempt($attr)) {
            return $this->responseWithError(__('auth.credentials_msg'), [], 401);
        }
        if(Auth::user() && Auth::user()->user_type != UserType::MERCHANT):
            return $this->responseWithError(__('auth.credentials_msg'), [], 401);
        endif;
        
        return $this->responseWithSuccess(__('auth.signin_msg'), ['token' => auth()->user()->createToken($request->merchant_id)->plainTextToken,'user'=>new UserResource(auth()->user())], 200);

    }

    public function deliveryManLogin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'driver_id'     => 'required|string',
            'password'      => 'required|string|min:6'
        ]);
 
        if ($validator->fails()) {
            return $this->responseWithError(__('auth.credentials_msg'), ['message' => $validator->errors()], 422);
        }
 
        $attr = ['unique_id'=>$request->get('driver_id'),'password'=>$request->get('password')];
          
        if (!Auth::attempt($attr)) {
            return $this->responseWithError(__('auth.credentials_msg'), [], 401);
        }

        if(Auth::user() && Auth::user()->user_type != UserType::DELIVERYMAN):
            return $this->responseWithError(__('auth.credentials_msg'), [], 401);
        endif;

        return $this->responseWithSuccess(__('auth.signin_msg'), ['token' => auth()->user()->createToken($request->driver_id)->plainTextToken,'user'=>new  DeliverymanUserResource(auth()->user())], 200);

    }

    public function profile()
    {
        return $this->responseWithSuccess(__('auth.profile_msg'), ['user'=>new UserResource(auth()->user())], 200);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        return $this->responseWithSuccess(__('auth.token_refresh'), ['token' =>  $user->createToken($user->mobile)->plainTextToken], 200);
    }

    public function logout()
    {
        
        auth()->user()->tokens()->delete();
        return $this->responseWithSuccess(__('auth.token_delete'), [], 200);
    }

    public function updatePassword(Request $request)
    {
        $validator = new UpdatePasswordRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('auth.update_password'), ['message' => $validator->errors()], 422);
        }
        $result = $this->profileRepo->updatePassword(auth()->user()->id, $request);
        if($result == 1){
            return $this->responseWithSuccess(__('auth.password_update'), [], 200);
        }
        elseif($result == 0){
            return $this->responseWithError(__('auth.password_old'), [], 422);
        }
        else
        {
            return $this->responseWithError(__('auth.error_msg'), [], 500);
        }
    }

    public function profileUpdate(Request $request)
    {
        $validator = new UpdateRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->responseWithError(__('auth.profile_update'), ['message' => $validator->errors()], 422);
        }
        if($this->merchantProfile->update(auth()->user()->id, $request)){
            return $this->responseWithSuccess(__('auth.profile_update'), [], 200);
        }else{
            return $this->responseWithError(__('auth.error_msg'), [], 500);
        }
    }

    public function sendPasswordResetLinkEmail(Request $request) {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if($status === Password::RESET_LINK_SENT) {
            return $this->responseWithSuccess(__('auth.password_reset_link'), ['message' => __($status)], 200);
        } else {
            throw ValidationException::withMessages([
                'email' => __($status)
            ]);
        }
    }

    public function resetPassword(Request $request) {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if($status == Password::PASSWORD_RESET) {
            return $this->responseWithSuccess(__('auth.password_reset'), ['message' => __($status)], 200);

        } else {
            throw ValidationException::withMessages([
                'email' => __($status)
            ]);
        }
    }

}
