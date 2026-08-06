<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'Pending',
    ParcelStatus::PICKUP_ASSIGN                          => 'पिकअप असाइन करें',
    ParcelStatus::PICKUP_ASSIGN_CANCEL                   => 'पिकअप असाइन रद्द करें',
    ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL              => 'पिकअप फिर से शेड्यूल रद्द',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'पिकअप री-शेड्यूल',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'पिकअप मैन द्वारा प्राप्त किया गया',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL          => 'पिकअप मैन द्वारा प्राप्त रद्द',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'प्राप्त गोदाम',
    ParcelStatus::RECEIVED_WAREHOUSE_CANCEL              => 'प्राप्त गोदाम रद्द',
    ParcelStatus::RECEIVED_BY_HUB_CANCEL                 => 'हब रद्द द्वारा प्राप्त',
    ParcelStatus::TRANSFER_TO_HUB                        => 'हब में स्थानांतरण',
    ParcelStatus::TRANSFER_TO_HUB_CANCEL                 => 'हब रद्द करने के लिए स्थानांतरण',
    ParcelStatus::RECEIVED_BY_HUB                        => 'हब द्वारा प्राप्त किया गया',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'डिलीवरी मैन असाइन करें',
    ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL             => 'डिलीवरी मैन असाइन करें रद्द करें',
    ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL            => 'वितरण पुन: अनुसूची रद्द',
    ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'वितरण पुन: अनुसूची',
    ParcelStatus::PARTIAL_DELIVERED_CANCEL               => 'आंशिक वितरित रद्द',
    ParcelStatus::RETURN_TO_COURIER                      => 'कूरियर पर लौटें',
    ParcelStatus::RETURN_TO_COURIER_CANCEL               => 'कुरियर पर लौटें रद्द',
    ParcelStatus::PARTIAL_DELIVERED                      => 'आंशिक वितरित',
    ParcelStatus::DELIVERED                              => 'पहुंचा दिया',
    ParcelStatus::DELIVERED_CANCEL                       => 'वितरित रद्द',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL       => 'मर्चेंट कैंसिल को रिटर्न असाइन करें',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'मर्चेंट को वापस असाइन करें',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL     => 'मर्चेंट को रिटर्न असाइन करें फिर से शेड्यूल करें रद्द करें',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'मर्चेंट री-शेड्यूल को रिटर्न असाइन करें ',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'व्यापारी द्वारा प्राप्त रिटर्न',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL     => 'मर्चेंट कैंसिल द्वारा प्राप्त रिटर्न',
    ParcelStatus::DELIVER                                => 'बाँटना',
    ParcelStatus::RETURN_WAREHOUSE                       => 'वापसी गोदाम',
    ParcelStatus::ASSIGN_MERCHANT                        => 'मर्चेंट असाइन करें',
    ParcelStatus::RETURNED_MERCHANT                      => 'लौटे व्यापारी',


);
