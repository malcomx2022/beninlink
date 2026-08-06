<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'Pending',
    ParcelStatus::PICKUP_ASSIGN                          => 'Pickup Assign',
    ParcelStatus::PICKUP_ASSIGN_CANCEL                   => 'Pickup Assign Cancel',
    ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL              => 'Pickup Re-Schedule Cancel',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'Pickup Re-Schedule',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'Received By Pickup Man',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL          => 'Received By Pickup Man Cancel',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'Received Warehouse',
    ParcelStatus::RECEIVED_WAREHOUSE_CANCEL              => 'Received Warehouse Cancel',
    ParcelStatus::RECEIVED_BY_HUB_CANCEL                 => 'Received by hub cancel',
    ParcelStatus::TRANSFER_TO_HUB                        => 'Transfer to hub',
    ParcelStatus::TRANSFER_TO_HUB_CANCEL                 => 'Transfer to hub cancel',
    ParcelStatus::RECEIVED_BY_HUB                        => 'Received by hub',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'Delivery Man Assign',
    ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL             => 'Delivery Man Assign Cancel',
    ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL            => 'Delivery Re-Schedule Cancel',
    ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'Delivery Re-Schedule',
    ParcelStatus::PARTIAL_DELIVERED_CANCEL               => 'Partial Delivered Cancel',
    ParcelStatus::RETURN_TO_COURIER                      => 'Return to Courier',
    ParcelStatus::RETURN_TO_COURIER_CANCEL               => 'Return to courier cancel',
    ParcelStatus::PARTIAL_DELIVERED                      => 'Partial Delivered',
    ParcelStatus::DELIVERED                              => 'Delivered',
    ParcelStatus::DELIVERED_CANCEL                       => 'Delivered Cancel',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL       => 'Return assign to merchant cancel',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'Return assign to merchant',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL     => 'Return assign to merchant Re-Schedule Cancel',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'Return assign to merchant Re-Schedule ',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'Return received by merchant',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL     => 'Return received by merchant cancel',
    ParcelStatus::DELIVER                                => 'Deliver',
    ParcelStatus::RETURN_WAREHOUSE                       => 'Return Warehouse',
    ParcelStatus::ASSIGN_MERCHANT                        => 'Assign Merchant',
    ParcelStatus::RETURNED_MERCHANT                      => 'Returned Merchant',

 
);
