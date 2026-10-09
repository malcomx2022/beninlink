<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * **S141** — le lien ne part que pour un compte **de la société du site** : le socle cherchait l'adresse sur
     * toutes les sociétés et envoyait un lien de ce site à un compte d'un autre transporteur.
     */
    protected function credentials(Request $request)
    {
        return tenant() ? $request->only('email') + ['company_id' => settings()->id] : $request->only('email');
    }

    /**
     * **S147** — la page répond pareil, que l'adresse ait un compte ici ou non : le socle disait « nous ne
     * pouvons pas trouver un utilisateur avec cette adresse », donc lesquelles sont inscrites (contraire à S142).
     */
    protected function sendResetLinkResponse(Request $request, $response)
    {
        return $this->reponseNeutre($request);
    }

    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        return $this->reponseNeutre($request);
    }

    private function reponseNeutre(Request $request)
    {
        return $request->wantsJson()
            ? new JsonResponse(['message' => __('passwords.sent_neutral')], 200)
            : back()->with('status', __('passwords.sent_neutral'));
    }
}
