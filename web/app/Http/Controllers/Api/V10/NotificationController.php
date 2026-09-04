<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Resources\v10\NotificationResource;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Fil de notifications du marchand connecté.
 *
 * Le périmètre est inhérent au mécanisme : `notifications()` est la relation
 * polymorphe de l'utilisateur authentifié, il n'y a rien d'autre à scoper.
 */
class NotificationController extends Controller
{
    use ApiReturnFormatTrait;

    /** Vingt par page, du plus récent au plus ancien, avec le nombre de non lues. */
    public function index(Request $request)
    {
        $user = Auth::user();
        $notifications = $user->notifications()->orderByDesc('created_at')->paginate(20);

        return $this->responseWithSuccess(__('notification.title'), [
            'notifications' => NotificationResource::collection($notifications),
            'unread_count' => $user->unreadNotifications()->count(),
        ], 200);
    }

    public function unreadCount()
    {
        return $this->responseWithSuccess(__('notification.title'), [
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ], 200);
    }

    public function read(string $id)
    {
        $notification = Auth::user()->notifications()->whereKey($id)->first();
        if (blank($notification)) {
            return $this->responseWithError(__('notification.not_found'), [], 404);
        }

        $notification->markAsRead();

        return $this->responseWithSuccess(__('notification.marked_read'), [
            'notification' => new NotificationResource($notification->fresh()),
        ], 200);
    }

    public function readAll()
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->responseWithSuccess(__('notification.all_marked_read'), [
            'unread_count' => 0,
        ], 200);
    }
}
