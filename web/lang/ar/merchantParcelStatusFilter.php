<?php

use App\Enums\ParcelStatus;
return array (
    ParcelStatus::PENDING                                => 'قيد الانتظار',
    ParcelStatus::PICKUP_ASSIGN                          => 'تعيين الالتقاط',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'المستودع المستلم',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'تعيين رجل التسليم',
    ParcelStatus::PARTIAL_DELIVERED                      => 'تم التسليم الجزئي',
    ParcelStatus::DELIVERED                              => 'تم التوصيل',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'العودة إلى التاجر',

);
