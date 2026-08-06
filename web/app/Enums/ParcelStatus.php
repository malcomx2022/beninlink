<?php
namespace App\Enums;

interface ParcelStatus
{
    const PENDING                           = 1;
    const PICKUP_ASSIGN                     = 2;
    const PICKUP_RE_SCHEDULE                = 3;
    const RECEIVED_BY_PICKUP_MAN            = 4;
    const RECEIVED_WAREHOUSE                = 5;
    const TRANSFER_TO_HUB                   = 6;
    const DELIVERY_MAN_ASSIGN               = 7;
    const DELIVERY_RE_SCHEDULE              = 8;
    const DELIVERED                         = 9;
    const DELIVER                           = 10;
    const RETURN_WAREHOUSE                  = 11;
    const ASSIGN_MERCHANT                   = 12;
    const RETURNED_MERCHANT                 = 13;
    const PICKUP_ASSIGN_CANCEL              = 14;
    const RECEIVED_BY_PICKUP_MAN_CANCEL     = 15;
    const RECEIVED_WAREHOUSE_CANCEL         = 16;
    const DELIVERY_MAN_ASSIGN_CANCEL        = 17;
    const DELIVERY_RE_SCHEDULE_CANCEL       = 18;
    const RECEIVED_BY_HUB                   = 19;
    const TRANSFER_TO_HUB_CANCEL            = 20;
    const RECEIVED_BY_HUB_CANCEL            = 21;
    const DELIVERED_CANCEL                  = 22;
    const PICKUP_RE_SCHEDULE_CANCEL         = 23;
    const RETURN_TO_COURIER                 = 24;
    const RETURN_TO_COURIER_CANCEL          = 25;
    const RETURN_ASSIGN_TO_MERCHANT         = 26;
    const RETURN_MERCHANT_RE_SCHEDULE       = 27;
    const RETURN_MERCHANT_RE_SCHEDULE_CANCEL= 28;
    const RETURN_ASSIGN_TO_MERCHANT_CANCEL  = 29;
    const RETURN_RECEIVED_BY_MERCHANT       = 30;
    const RETURN_RECEIVED_BY_MERCHANT_CANCEL= 31;
    const PARTIAL_DELIVERED                 = 32;
    const PARTIAL_DELIVERED_CANCEL          = 33;

}
