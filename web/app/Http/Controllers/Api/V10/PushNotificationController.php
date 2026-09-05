<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Services\PushNotificationService;
use App\Models\Backend\DeviceToken;
use App\Services\Push\ExpoPushGateway;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Abonnement d'un appareil aux notifications poussées.
 *
 * Deux générations cohabitent ici :
 *   - `push/register` et `push/forget` : le transport actuel. L'app envoie le
 *     jeton que lui a donné le service de push ; le serveur le rattache au
 *     compte **authentifié**, jamais à un compte désigné par la requête.
 *   - `fcm-subscribe` / `fcm-unsubscribe` : les topics FCM du socle, servis par
 *     les apps Flutter dépréciées. Conservés tels quels (S11 les avait déjà
 *     durcis) ; l'API FCM qu'ils appellent est arrêtée côté Google.
 */
class PushNotificationController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(private PushNotificationService $pushNotificationService)
    {
    }

    /** Abonne l'appareil courant. Rejouable : le même jeton ne crée pas de doublon. */
    public function register(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'token' => 'required|string|max:255',
            'platform' => 'nullable|in:ios,android',
            'app' => 'nullable|in:merchant,deliveryman',
        ]);

        if ($validation->fails()) {
            return $this->responseWithError(__('push.invalid_token'), $validation->errors(), 422);
        }

        $jeton = (string) $request->input('token');

        // Un jeton d'une autre forme ne serait jamais livrable : autant le dire
        // à l'app tout de suite plutôt que de garder une ligne morte.
        if (!app(ExpoPushGateway::class)->accepte($jeton)) {
            return $this->responseWithError(__('push.invalid_token'), [], 422);
        }

        $user = Auth::user();

        // Un appareil qui change de main suit son dernier compte : le jeton est
        // unique, on le réaffecte au lieu d'empiler deux lignes.
        $appareil = DeviceToken::updateOrCreate(
            ['token' => $jeton],
            [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'platform' => $request->input('platform'),
                'app' => $request->input('app'),
                'last_used_at' => null,
            ],
        );

        return $this->responseWithSuccess(__('push.registered'), [
            'device_id' => $appareil->id,
        ], 200);
    }

    /** Désabonne l'appareil courant — à la déconnexion, notamment. */
    public function forget(Request $request)
    {
        $validation = Validator::make($request->all(), ['token' => 'required|string|max:255']);
        if ($validation->fails()) {
            return $this->responseWithError(__('push.invalid_token'), $validation->errors(), 422);
        }

        // Seulement ses propres appareils : un jeton d'autrui n'est pas à
        // supprimer, même en le connaissant.
        DeviceToken::where('user_id', Auth::id())
            ->where('token', (string) $request->input('token'))
            ->delete();

        return $this->responseWithSuccess(__('push.forgotten'), [], 200);
    }

    public function fcmSubscribe(Request $request)
    {
        $validation = Validator::make($request->all(),  [
            'device_token' => 'required',
            'topic' => 'required',
        ]);
        if ($validation->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validation->errors(),
            ], 422);
        }

        return $this->pushNotificationService->fcmSubscribe($request);

    }

    public function fcmUnsubscribe(Request $request)
    {
        return $this->pushNotificationService->fcmUnsubscribe($request);
    }

}
