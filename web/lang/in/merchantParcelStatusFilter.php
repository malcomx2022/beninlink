<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'लंबित',
    ParcelStatus::PICKUP_ASSIGN                          => 'पिकअप असाइन करें',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'प्राप्त गोदाम',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'डिलीवरी मैन असाइन करें',
    ParcelStatus::PARTIAL_DELIVERED                      => 'आंशिक वितरण',
    ParcelStatus::DELIVERED                              => 'पहुंचा दिया',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'व्यापारी को असाइन करें लौटाएं',

);
