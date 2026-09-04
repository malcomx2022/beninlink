<?php

namespace App\Observers\Feed;

use App\Models\Backend\CustomsAlert;
use App\Services\Notifications\MerchantFeed;

/**
 * Une alerte douanière émise alimente le fil — c'est la « notification à la
 * création » demandée par `.claude/rules/customs.md`, restée en attente au
 * chantier 5 faute de canal.
 */
class CustomsAlertFeedObserver
{
    public function __construct(private readonly MerchantFeed $feed)
    {
    }

    public function created(CustomsAlert $alert): void
    {
        $this->feed->customsAlertRaised($alert);
    }
}
