<?php

use App\Enums\ParcelStatus;

return [
    ParcelStatus::PICKUP_ASSIGN => 'تم تعيين الالتقاط',
    ParcelStatus::PICKUP_RE_SCHEDULE => 'إعادة جدولة استلام الطرود
    ',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN => 'تلقى الطرود رجل بيك أب',
    ParcelStatus::RECEIVED_WAREHOUSE => 'تم استلام الطرد إلى المستودع',
    ParcelStatus::TRANSFER_TO_HUB        => 'نقل الطرود إلى المحور',
    ParcelStatus::RECEIVED_BY_HUB        => 'تم الاستلام بواسطة Hub',
    ParcelStatus::DELIVERY_MAN_ASSIGN => 'تعيين رجل التسليم',
    ParcelStatus::DELIVERY_RE_SCHEDULE => 'تمت إعادة جدولة التوصيل',

    ParcelStatus::DELIVER => 'ايصال',
    ParcelStatus::RETURN_TO_COURIER => 'العودة إلى البريد السريع',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT => 'العودة إلى التاجر',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE => 'إرجاع التخصيص إلى التاجر إعادة الجدولة',

    ParcelStatus::DELIVERED => 'تم التوصيل',
    ParcelStatus::PARTIAL_DELIVERED => 'تم التسليم الجزئي',
    ParcelStatus::RETURN_WAREHOUSE => 'مستودع العودة',
    ParcelStatus::ASSIGN_MERCHANT => 'عيّن التاجر',
    ParcelStatus::RETURNED_MERCHANT => 'التاجر العائد',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT => 'استلم الإرجاع من قبل التاجر',

    'hub_name'                      => 'اسم المركز',
    'hub_phone'                      => 'هاتف المحور',
    'delivery_man'                  => 'رجل التوصيل',
    'delivery_man_phone'            => 'هاتف رجل التوصيل'


];
