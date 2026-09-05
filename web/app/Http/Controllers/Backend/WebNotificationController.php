<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Enregistrement du jeton de push navigateur — **retiré** (S22, décision D12).
 *
 * La route reste servie pour une raison précise : un navigateur d'agent peut
 * encore porter l'ancien service worker et rappeler cette adresse au prochain
 * chargement. Elle répond désormais sans rien écrire, plutôt que de renvoyer
 * une 404 dans la console de tout le monde.
 *
 * Ce qu'elle faisait : stocker `users.web_token` (un jeton du projet Firebase
 * de l'éditeur) puis appeler l'abonnement au topic FCM. Les deux appartiennent
 * à un canal qui ne livrait plus rien.
 */
class WebNotificationController extends Controller
{
    public function store(Request $request)
    {
        return response()->json(['Browser push notifications are retired.'], 410);
    }
}
