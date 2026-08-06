<?php

use App\Enums\ParcelStatus;

return array(
    ParcelStatus::PENDING                                => 'En attente',
    ParcelStatus::PICKUP_ASSIGN                          => 'Ramassage assigné',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'Reprogrammation du ramassage',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'Colis reçu par le livreur de ramassage',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'Colis reçu à l\'entrepôt',
    ParcelStatus::TRANSFER_TO_HUB                        => 'Transfert au hub',
    ParcelStatus::RECEIVED_BY_HUB                        => 'Reçu par le hub',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'Livreur affecté',
    // ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'Reprogrammation de la livraison',
    ParcelStatus::RETURN_TO_COURIER                      => 'Retour au coursier',
    ParcelStatus::PARTIAL_DELIVERED                      => 'Livraison partielle',
    ParcelStatus::DELIVERED                              => 'Livré',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'Retour assigné au marchand',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'Reprogrammation du retour assigné au marchand',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'Retour reçu par le marchand',
    // ParcelStatus::DELIVER                                => 'Livraison',
    // ParcelStatus::RETURN_WAREHOUSE                       => 'Retour à l\'entrepôt',
    // ParcelStatus::ASSIGN_MERCHANT                        => 'Affecter un marchand',
    // ParcelStatus::RETURNED_MERCHANT                      => 'Retourné au marchand',

);
