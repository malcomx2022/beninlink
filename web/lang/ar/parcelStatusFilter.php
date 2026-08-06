<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'قيد الانتظار',
    ParcelStatus::PICKUP_ASSIGN                          => 'تعيين الالتقاط',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'إعادة جدولة الالتقاط',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'استلمها الرجل الصغير',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'المستودع المستلم',
    ParcelStatus::TRANSFER_TO_HUB                        => 'نقل إلى المحور',
    ParcelStatus::RECEIVED_BY_HUB                        => 'استلمها المحور',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'تعيين رجل التسليم
    ',
    ParcelStatus::RETURN_TO_COURIER                      => 'العودة إلى البريد السريع',
    ParcelStatus::PARTIAL_DELIVERED                      => 'تم التسليم الجزئي',
    ParcelStatus::DELIVERED                              => 'تم التوصيل',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'العودة إلى التاجر',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'إرجاع التعيين إلى التاجر إعادة الجدولة',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'استلم الإرجاع من قبل التاجر',



);
