<?php

namespace App\Observers\Feed;

use App\Models\Backend\Payment;
use App\Services\Notifications\MerchantFeed;

/** Une demande de retrait approuvée, traitée ou rejetée alimente le fil. */
class PaymentFeedObserver
{
    public function __construct(private readonly MerchantFeed $feed)
    {
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged('status')) {
            $this->feed->payoutUpdated($payment);
        }
    }
}
