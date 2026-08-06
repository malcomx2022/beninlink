<?php
  use App\Enums\ParcelStatus;
return [

    ParcelStatus::PICKUP_ASSIGN                          => 'पिकअप असाइन करें',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'पिकअप री-शेड्यूल',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'पिकअप मैन द्वारा प्राप्त किया गया',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'प्राप्त गोदाम',
    ParcelStatus::TRANSFER_TO_HUB                        => 'हब में स्थानांतरण',
    ParcelStatus::RECEIVED_BY_HUB                        => 'हब द्वारा प्राप्त किया गया',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'डिलीवरी मैन असाइन करें',
    ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'वितरण पुन: अनुसूची',
    ParcelStatus::RETURN_TO_COURIER                      => 'कूरियर पर लौटें',
    ParcelStatus::PARTIAL_DELIVERED                      => 'आंशिक वितरित',
    ParcelStatus::DELIVERED                              => 'पहुंचा दिया',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'मर्चेंट को वापस असाइन करें',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'मर्चेंट री-शेड्यूल को रिटर्न असाइन करें ',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'व्यापारी द्वारा प्राप्त रिटर्न',
    ParcelStatus::RETURN_WAREHOUSE                       => 'वापसी गोदाम',
    ParcelStatus::ASSIGN_MERCHANT                        => 'मर्चेंट असाइन करें',
    ParcelStatus::RETURNED_MERCHANT                      => 'लौटा हुआ व्यापारी',

];
