<?php

use App\Enums\SmsSendStatus;

return array (
    SmsSendStatus::PARCEL_CREATE                                 => 'Création de colis',
    SmsSendStatus::DELIVERED_CANCEL_CUSTOMER                     => 'Livraison annulée par le client',
    SmsSendStatus::DELIVERED_CANCEL_MERCHANT                     => 'Livraison annulée par le marchand',

);
