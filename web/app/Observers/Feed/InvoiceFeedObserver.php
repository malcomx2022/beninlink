<?php

namespace App\Observers\Feed;

use App\Models\Backend\Merchantpanel\Invoice;
use App\Services\Notifications\MerchantFeed;

/** Un relevé de règlement émis (commande `invoice:generate`) alimente le fil. */
class InvoiceFeedObserver
{
    public function __construct(private readonly MerchantFeed $feed)
    {
    }

    public function created(Invoice $invoice): void
    {
        $this->feed->invoiceIssued($invoice);
    }
}
