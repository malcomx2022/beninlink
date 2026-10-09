<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Support\BeninPhone;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
        
        // S139 — « se souvenir de moi » garde l'identifiant saisi, jamais le mot de passe : le socle le
        // rangeait 24 h dans un cookie que la page de connexion rendait en clair dans son `value`. Le
        // retour sans mot de passe est le jeton de rappel de Laravel (`attemptLogin`, case `remember`).
        // Le cookie d'un navigateur servi avant S139 s'efface à la connexion suivante.
        Cookie::queue(Cookie::forget('userpassword'));
        if ($request->boolean('remember')) {
            Cookie::queue('useremail', $request->email, 1440);
        } else {
            Cookie::queue(Cookie::forget('useremail'));
        }
        
        $this->validateLogin($request);

        $user    = User::where(function($query)use ($request){
            $query->where('email',$request->email);
            $query->orWhere('mobile',$request->email);
            $query->orWhere('mobile',BeninPhone::normalize((string) $request->email)); // S133
        })->first();
        
        // S104 — un compte LIVREUR n'a aucun écran web : il se connecte dans
        // l'app livreur. Le laisser entrer menait à `/dashboard`, qui lui
        // répondait 500 (famille S37 : un refus annoncé comme une panne). On
        // refuse ici, avant la session, avec le mot qui dit où aller.
        // S142 — ce qu'on dit d'un compte (livreur, transporteur de rattachement) ne se dit qu'à qui a
        // son mot de passe. Le socle redirigeait vers le site du transporteur, et S104 refusait le livreur,
        // sur la seule adresse saisie : n'importe qui apprenait chez quel transporteur une PME est inscrite.
        $motDePasseJuste = $user && Hash::check((string) $request->get('password'), (string) $user->password);

        if ($motDePasseJuste && (int) $user->user_type === UserType::DELIVERYMAN) {
            throw ValidationException::withMessages([
                $this->username() => [__('auth.courier_app_only')],
            ]);
        }

        if(tenant()):
            if($user && $user->user_type == UserType::SUPER_ADMIN):
                return $this->sendFailedLoginResponse($request);
            elseif($user && $user->company_id != settings()->id): 
                return $this->sendFailedLoginResponse($request);
            endif;
           
        else: 
         
            if($motDePasseJuste && $user->user_type != UserType::SUPER_ADMIN): 
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
          
            if(($mobile = $this->mobileSaisi($request, settings()->id)) !== null)
            {
                return [ 
                        'mobile'        => $mobile,
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
            if(($mobile = $this->mobileSaisi($request)) !== null)
            {
                return ['mobile' => $mobile,'password' => $request->get('password'), 'status' => '1', 'verification_status' => '1' ];
            }
            return ['email' => $request->get('email'),'password' => $request->get('password'), 'status' => '1', 'verification_status' => '1'];
        endif;
    }

    /**
     * S133 — l'identifiant saisi est un numéro (« 01 97 00 00 00 », « +229… ») : il se cherche au
     * format rangé (`BeninPhone`). Un compte rangé avant S133 sous une autre forme reste joignable
     * par ce qu'on tape. `null` : l'identifiant est une adresse électronique.
     */
    private function mobileSaisi(Request $request, ?int $companyId = null): ?string
    {
        $saisie = (string) $request->get('email');
        $range  = BeninPhone::normalize($saisie);
        if ($range === $saisie && ! is_numeric($saisie)) {
            return null;
        }

        $existe = User::where('mobile', $range)
            ->when($companyId !== null, fn ($q) => $q->where('company_id', $companyId))
            ->exists();

        return $existe ? $range : $saisie;
    }
}
