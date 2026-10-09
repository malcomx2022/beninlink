<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * **S141** — le lien ne réinitialise qu'un compte **de la société du site**. Le socle cherchait l'adresse
     * sur toutes les sociétés.
     */
    protected function credentials(Request $request)
    {
        $champs = $request->only('email', 'password', 'password_confirmation', 'token');

        return tenant() ? $champs + ['company_id' => settings()->id] : $champs;
    }

    /**
     * **S141** — réinitialiser n'ouvre pas de session. Le socle connectait le compte aussitôt, sans les
     * conditions de `LoginController` : un compte désactivé, un livreur (S104) ou un compte d'une autre
     * société entraient par « mot de passe oublié ». On se connecte ensuite avec le nouveau mot de passe.
     */
    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);
        $user->save(); // `User::booted()` ferme les autres accès et change le jeton de rappel (S135, S139)

        event(new PasswordReset($user));
    }

    protected function sendResetResponse(Request $request, $response)
    {
        Toastr::success(__($response), __('message.success'));

        return redirect()->route('login');
    }
}
