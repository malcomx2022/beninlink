<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

  
    // Auth login 
    public function login(Request $request)
    {
        
        // Active Remember me 24 houre
        if($request->remember != null)
        {
            Cookie::queue('useremail',$request->email,1440);
            Cookie::queue('userpassword',$request->password,1440);
        }
        else
        {
            Cookie::queue(Cookie::forget('useremail'));
            Cookie::queue(Cookie::forget('userpassword'));
        }
        
        $this->validateLogin($request);

        $user    = User::where(function($query)use ($request){
            $query->where('email',$request->email);
            $query->orWhere('mobile',$request->email);
        })->first();
        
        if(tenant()):
            if($user && $user->user_type == UserType::SUPER_ADMIN):
                return $this->sendFailedLoginResponse($request);
            elseif($user && $user->company_id != settings()->id): 
                return $this->sendFailedLoginResponse($request);
            endif;
           
        else: 
         
            if($user && $user->user_type != UserType::SUPER_ADMIN): 
                return redirect()->to(scheme_name($user->tenantDetails->domains[0]->domain));
                // return $this->sendFailedLoginResponse($request);
            endif;
        endif;
         
        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            } 
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
        

    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function credentials(Request $request)
    {
         
        if(tenant()): 
          
            if(is_numeric($request->get('email')))
            {
                return [ 
                        'mobile'        => $request->get('email'),
                        'company_id'    => settings()->id,
                        'password'      => $request->get('password'),
                        'status'        => '1', 
                        'verification_status' => '1'
                     ];
            }
            return [
                    'email'               => $request->get('email'),
                    'company_id'          => settings()->id,
                    'password'            => $request->get('password'),
                    'status'              => '1',
                    'verification_status' => '1'
                ];
         
        else:  
            if(is_numeric($request->get('email')))
            {
                return ['mobile' => $request->get('email'),'password' => $request->get('password'), 'status' => '1', 'verification_status' => '1' ];
            }
            return ['email' => $request->get('email'),'password' => $request->get('password'), 'status' => '1', 'verification_status' => '1'];
        endif;
    }
}
