<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'En attente',
    ParcelStatus::PICKUP_ASSIGN                          => 'Attribution de ramassage',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'Entrepôt reçu',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'Attribution de livraison',
    ParcelStatus::PARTIAL_DELIVERED                      => 'Livraison partielle',
    ParcelStatus::DELIVERED                              => 'Livré',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'Attribution de retour au marchand',
);
