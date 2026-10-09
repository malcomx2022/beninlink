<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Upload;
use App\Models\User;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\SocialLoginSettings\SocialLoginSettingsInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Laravel\Socialite\Facades\Socialite;
class SocialLoginController extends Controller
{
    protected $merchantRepo;
    public function __construct(MerchantInterface $merchantRepo,SocialLoginSettingsInterface $repo)
    {
        $this->merchantRepo = $merchantRepo;
        $this->repo         = $repo;
    }
    public function socialRedirect($social){

        if($social == 'google'):
            if(globalSettings('google_status') != Status::ACTIVE):
                Toastr::error(__('Google login is not enabled.'), __('message.error'));
                return redirect()->back();
            endif;
            \Config([
                'services.google.client_id'        => globalSettings('google_client_id'),
                'services.google.client_secret'    => globalSettings('google_client_secret'),
                'services.google.redirect'         => url('google/login')
            ]);
 
            return Socialite::driver('google')->redirect();

        elseif($social == 'facebook'):

            if(globalSettings('facebook_status') != Status::ACTIVE):
                Toastr::error(__('Facebook login is not enabled.'), __('message.error'));
                return redirect()->back();
            endif;
            \Config([
                'services.facebook.client_id'        => globalSettings('facebook_client_id'),
                'services.facebook.client_secret'    => globalSettings('facebook_client_secret'),
                'services.facebook.redirect'         => url('facebook/login')
            ]);

            return Socialite::driver('facebook')->redirect();
        endif;

        Toastr::error(__('parcel.error_msg'),__('message.error'));
        return redirect()->back();
    }
    public function authGoogleLogin(Request $request){
       try {

        \Config([
            'services.google.client_id'        => globalSettings('google_client_id'),
            'services.google.client_secret'    => globalSettings('google_client_secret'),
            'services.google.redirect'         => url('google/login')
        ]);

        return $this->connecter($request, 'google', Socialite::driver('google')->user());

       } catch (\Throwable $th) {
           Toastr::error(__('parcel.error_msg'),__('message.error'));
           return redirect()->back();
       }
    }
    public function authFacebookLogin(Request $request){
        try {

            \Config([
                'services.facebook.client_id'        => globalSettings('facebook_client_id'),
                'services.facebook.client_secret'    => globalSettings('facebook_client_secret'),
                'services.facebook.redirect'         => url('facebook/login')
            ]);
            return $this->connecter($request, 'facebook', Socialite::driver('facebook')->user());

        } catch (\Throwable $th) {

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * **S140** — le retour Google ou Facebook ouvre la session d'un compte **de la société du site**, marchand,
     * actif et vérifié, comme `LoginController`. Le socle cherchait le compte par son
     * identifiant social **sur toutes les sociétés** et ouvrait sa session sans autre contrôle : la PME d'un
     * transporteur entrait sur le site d'un autre, un compte désactivé ou un agent du back-office entrait encore.
     * `Auth::login()` régénère l'identifiant de session. `google_id` et `facebook_id` sont uniques sur la plateforme : un compte
     * social déjà rattaché ailleurs ne s'inscrit pas une seconde fois ici.
     */
    private function connecter(Request $request, string $social, $profil)
    {
        $colonne = $social === 'google' ? 'google_id' : 'facebook_id';
        $compte  = User::where($colonne, (string) $profil->id)->first();

        if ($compte === null) {
            $compte = $this->merchantRepo->socialSignupStore($profil, $social);
            if (! $compte) {
                Toastr::error(__('parcel.error_msg'), __('message.error'));
                return redirect()->back();
            }
            $compte = $compte->fresh(); // statut et vérification : valeurs par défaut de la base
        }

        if ((int) $compte->company_id !== (int) settings()->id
            || (int) $compte->user_type !== UserType::MERCHANT
            || (int) $compte->status !== Status::ACTIVE
            || (int) $compte->verification_status !== Status::ACTIVE) {
            Toastr::error(__('auth.social_refused'), __('message.error'));
            return redirect()->route('login');
        }

        Auth::login($compte);

        return redirect('/');
    }

    public function socialLoginSettingsIndex(){

        return view('backend.setting.social_login_settings.index');
    }

    public function socialLoginSettingsUpdate(Request $request,$social){

        if($this->repo->update($request,$social)):
            Toastr::success(__('parcel.settings_update_success'),__('message.success'));
            return redirect()->route('social.login.settings.index');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }


}
