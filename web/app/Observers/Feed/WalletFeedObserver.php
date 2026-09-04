<?php

namespace App\Observers\Feed;

use App\Models\Backend\Wallet;
use App\Services\Notifications\MerchantFeed;

/**
 * Un crédit de wallet approuvé alimente le fil — qu'il vienne du webhook
 * FedaPay ou d'une approbation manuelle : les deux passent par
 * `WalletRepository::approved()`, donc par `save()`.
 */
class WalletFeedObserver
{
    public function __construct(private readonly MerchantFeed $feed)
    {
    }

    public function updated(Wallet $wallet): void
    {
        if ($wallet->wasChanged('status')) {
            $this->feed->walletCredited($wallet);
        }
    }
}
