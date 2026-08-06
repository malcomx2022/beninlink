<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'Pending',
    ParcelStatus::PICKUP_ASSIGN                          => 'Pickup Assign',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'Pickup Re-Schedule',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'Received By Pickup Man',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'Received Warehouse',
    ParcelStatus::TRANSFER_TO_HUB                        => 'Transfer to hub',
    ParcelStatus::RECEIVED_BY_HUB                        => 'Received by hub',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'Delivery Man Assign',
    // ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'Delivery Re-Schedule',
    ParcelStatus::RETURN_TO_COURIER                      => 'Return to Courier',
    ParcelStatus::PARTIAL_DELIVERED                      => 'Partial Delivered',
    ParcelStatus::DELIVERED                              => 'Delivered',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'Return assign to merchant',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'Return assign to merchant Re-Schedule ',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'Return received by merchant',
    // ParcelStatus::DELIVER                                => 'Deliver',
    // ParcelStatus::RETURN_WAREHOUSE                       => 'Return Warehouse',
    // ParcelStatus::ASSIGN_MERCHANT                        => 'Assign Merchant',
    // ParcelStatus::RETURNED_MERCHANT                      => 'Returned Merchant',



);
