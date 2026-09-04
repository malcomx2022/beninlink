<?php

namespace App\Observers\Feed;

use App\Models\Backend\PushNotification;
use App\Services\Notifications\MerchantFeed;

/**
 * Un message rédigé par l'administration (`push_notifications`) atteint le fil
 * des marchands visés — le push FCM qui l'accompagnait est hors service.
 */
class PushNotificationFeedObserver
{
    public function __construct(private readonly MerchantFeed $feed)
    {
    }

    public function created(PushNotification $message): void
    {
        $this->feed->adminMessage($message);
    }
}
