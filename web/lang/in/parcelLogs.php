<?php

use App\Enums\ParcelStatus;

return [
    ParcelStatus::PICKUP_ASSIGN => 'पिकअप असाइन किया गया',
    ParcelStatus::PICKUP_RE_SCHEDULE => 'पार्सल पिकअप फिर से शेड्यूल किया गया',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN => 'पार्सल प्राप्त पिक मैन',
    ParcelStatus::RECEIVED_WAREHOUSE => 'गोदाम को मिला पार्सल',
    ParcelStatus::TRANSFER_TO_HUB        => 'हब में पार्सल स्थानांतरण',
    ParcelStatus::RECEIVED_BY_HUB        => 'हब द्वारा प्राप्त',
    ParcelStatus::DELIVERY_MAN_ASSIGN => 'डिलीवरी मैन असाइन किया गया',
    ParcelStatus::DELIVERY_RE_SCHEDULE => 'वितरण समय में परिवर्तन',

    ParcelStatus::DELIVER => 'बाँटना',
    ParcelStatus::RETURN_TO_COURIER => 'कूरियर पर लौटें',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT => 'मर्चेंट को वापस असाइन करें',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE => 'मर्चेंट री-शेड्यूल को वापस असाइन करें',

    ParcelStatus::DELIVERED => 'पहुंचा दिया',
    ParcelStatus::PARTIAL_DELIVERED => 'आंशिक वितरित',
    ParcelStatus::RETURN_WAREHOUSE => 'वापसी गोदाम',
    ParcelStatus::ASSIGN_MERCHANT => 'मर्चेंट असाइन करें',
    ParcelStatus::RETURNED_MERCHANT => 'लौटा हुआ व्यापारी',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT => 'व्यापारी द्वारा प्राप्त रिटर्न',

    'hub_name'                      => 'हब का नाम',
    'hub_phone'                      => 'हब फोन',
    'delivery_man'                  => 'डिलीवरी मैन',
    'delivery_man_phone'            => 'डिलीवरी मैन फोन'


];
