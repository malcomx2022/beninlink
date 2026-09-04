<?php

namespace App\Observers\Feed;

use App\Models\Backend\Parcel;
use App\Services\Notifications\MerchantFeed;

/**
 * Un changement de statut de colis alimente le fil du marchand.
 *
 * Même raison qu'`ParcelCustomsObserver` : les statuts sont écrits par une
 * dizaine de chemins (repositories admin et marchand, API livreur…). Un seul
 * point d'accroche les couvre tous, présents et futurs. Seul un changement
 * effectif de `status` émet ; une autre modification du colis reste muette.
 */
class ParcelFeedObserver
{
    public function __construct(private readonly MerchantFeed $feed)
    {
    }

    public function updated(Parcel $parcel): void
    {
        if ($parcel->wasChanged('status')) {
            $this->feed->parcelStatusChanged($parcel);
        }
    }
}
