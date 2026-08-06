<?php
namespace App\Enums;

interface SmsSendStatus
{
    const PARCEL_CREATE                      = 1;
    const DELIVERED_CANCEL_CUSTOMER          = 2;
    const DELIVERED_CANCEL_MERCHANT          = 3;

}
