<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'قيد الانتظار',
    ParcelStatus::PICKUP_ASSIGN                          => 'تعيين الالتقاط',
    ParcelStatus::PICKUP_ASSIGN_CANCEL                   => 'اختيار الالتقاط إلغاء',
    ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL              => 'إعادة جدولة الالتقاط إلغاء',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'Pickup Re-Schedule',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'استلمها الرجل الصغير',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL          => 'تم الاستلام عن طريق Pickup Man إلغاء',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'المستودع المستلم',
    ParcelStatus::RECEIVED_WAREHOUSE_CANCEL              => 'تم استلام إلغاء المستودع',
    ParcelStatus::RECEIVED_BY_HUB_CANCEL                 => 'تلقت من قبل المحور إلغاء',
    ParcelStatus::TRANSFER_TO_HUB                        => 'نقل إلى المحور',
    ParcelStatus::TRANSFER_TO_HUB_CANCEL                 => 'إلغاء التحويل إلى المحور',
    ParcelStatus::RECEIVED_BY_HUB                        => 'استلمها المحور',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'تعيين رجل التسليم',
    ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL             => 'إلغاء تعيين رجل التسليم',
    ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL            => 'إعادة جدولة التسليم إلغاء',
    ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'إعادة جدولة التسليم',
    ParcelStatus::PARTIAL_DELIVERED_CANCEL               => 'تم التسليم الجزئي إلغاء',
    ParcelStatus::RETURN_TO_COURIER                      => 'العودة إلى البريد السريع',
    ParcelStatus::RETURN_TO_COURIER_CANCEL               => 'العودة إلى الساعي إلغاء',
    ParcelStatus::PARTIAL_DELIVERED                      => 'تم التسليم الجزئي',
    ParcelStatus::DELIVERED                              => 'تم التوصيل',
    ParcelStatus::DELIVERED_CANCEL                       => 'تم التسليم إلغاء',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL       => 'إرجاع التعيين إلى التاجر إلغاء',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'العودة إلى التاجر',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL     => 'إرجاع التعيين إلى التاجر إعادة جدولة إلغاء',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'إرجاع التعيين إلى التاجر إعادة الجدولة ',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'استلم الإرجاع من قبل التاجر',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL     => 'الإرجاع الذي استلمه التاجر إلغاء',
    ParcelStatus::DELIVER                                => 'ايصال',
    ParcelStatus::RETURN_WAREHOUSE                       => 'مستودع العودة',
    ParcelStatus::ASSIGN_MERCHANT                        => 'عيّن التاجر',
    ParcelStatus::RETURNED_MERCHANT                      => 'التاجر العائد',


);
