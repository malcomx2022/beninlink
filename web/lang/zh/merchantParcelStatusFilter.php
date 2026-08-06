<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => '待办的',
    ParcelStatus::PICKUP_ASSIGN                          => '取件分配',
    ParcelStatus::RECEIVED_WAREHOUSE                     => '收货仓库',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => '送货员分配',
    ParcelStatus::PARTIAL_DELIVERED                      => '部分交付',
    ParcelStatus::DELIVERED                              => '发表',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => '返回分配给商家',

);
