<?php

use App\Enums\ParcelStatus;

return [
    ParcelStatus::PICKUP_ASSIGN => 'Pickup Assigned',
    ParcelStatus::PICKUP_RE_SCHEDULE => 'Parcel pickup Re-Scheduled',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN => 'Parcel received  Pickup Man',
    ParcelStatus::RECEIVED_WAREHOUSE => 'Parcel received to Warehouse',
    ParcelStatus::TRANSFER_TO_HUB        => 'Parcel transfer to hub',
    ParcelStatus::RECEIVED_BY_HUB        => 'Received By Hub',
    ParcelStatus::DELIVERY_MAN_ASSIGN => 'Delivery Man Assigned',
    ParcelStatus::DELIVERY_RE_SCHEDULE => 'Delivery Re-Scheduled',

    ParcelStatus::DELIVER => 'Deliver',
    ParcelStatus::RETURN_TO_COURIER => 'Return to Courier',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT => 'Return assign to merchant',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE => 'Return Assign To Merchant Re-Schedule',

    ParcelStatus::DELIVERED => 'Delivered',
    ParcelStatus::PARTIAL_DELIVERED => 'Partial Delivered',
    ParcelStatus::RETURN_WAREHOUSE => 'Return Warehouse',
    ParcelStatus::ASSIGN_MERCHANT => 'Assign Merchant',
    ParcelStatus::RETURNED_MERCHANT => 'Returned Merchant',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT => 'Return received by merchant',

    'hub_name'                      => 'Hub Name',
    'hub_phone'                      => 'Hub Phone',
    'delivery_man'                  => 'Delivery Man',
    'delivery_man_phone'            => 'Delivery man phone'


];
