<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => '待办的',
    ParcelStatus::PICKUP_ASSIGN                          => '取件分配',
    ParcelStatus::PICKUP_ASSIGN_CANCEL                   => '已分配取件取消',
    ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL              => '取件重新安排取消',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => '重新安排取件时间',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => '接机员收到',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL          => '取件人收到 取消',
    ParcelStatus::RECEIVED_WAREHOUSE                     => '收货仓库',
    ParcelStatus::RECEIVED_WAREHOUSE_CANCEL              => '收货仓库取消',
    ParcelStatus::RECEIVED_BY_HUB_CANCEL                 => '由集线器取消接收',
    ParcelStatus::TRANSFER_TO_HUB                        => '转移到枢纽',
    ParcelStatus::TRANSFER_TO_HUB_CANCEL                 => '转移到枢纽取消',
    ParcelStatus::RECEIVED_BY_HUB                        => '由集线器接收',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => '送货员分配',
    ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL             => '送货员分配 取消',
    ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL            => '交货重新安排取消',
    ParcelStatus::DELIVERY_RE_SCHEDULE                   => '交货改期',
    ParcelStatus::PARTIAL_DELIVERED_CANCEL               => '部分交付 取消',
    ParcelStatus::RETURN_TO_COURIER                      => '返回快递',
    ParcelStatus::RETURN_TO_COURIER_CANCEL               => '退回快递取消',
    ParcelStatus::PARTIAL_DELIVERED                      => '部分交付',
    ParcelStatus::DELIVERED                              => '发表',
    ParcelStatus::DELIVERED_CANCEL                       => '已交付 取消',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL       => '返回分配给商家取消',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => '返回分配给商家',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL     => '返回分配给商家重新安排取消',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => '返回分配给商家重新安排 ',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => '商家收到退货',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL     => '商家取消收到的退货',
    ParcelStatus::DELIVER                                => '递送',
    ParcelStatus::RETURN_WAREHOUSE                       => '退货仓库',
    ParcelStatus::ASSIGN_MERCHANT                        => '分配商户',
    ParcelStatus::RETURNED_MERCHANT                      => '退货商家',

 
);
