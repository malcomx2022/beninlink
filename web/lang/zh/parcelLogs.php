<?php

use App\Enums\ParcelStatus;

return [
    ParcelStatus::PICKUP_ASSIGN => '已分配取件',
    ParcelStatus::PICKUP_RE_SCHEDULE => '重新安排包裹取件',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN => '包裹收到 取件人',
    ParcelStatus::RECEIVED_WAREHOUSE => '包裹收到仓库',
    ParcelStatus::TRANSFER_TO_HUB        => '包裹转运到枢纽',
    ParcelStatus::RECEIVED_BY_HUB        => '集线器接收',
    ParcelStatus::DELIVERY_MAN_ASSIGN => '送货员分配',
    ParcelStatus::DELIVERY_RE_SCHEDULE => '交货改期',

    ParcelStatus::DELIVER => '递送',
    ParcelStatus::RETURN_TO_COURIER => '返回快递',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT => '返回分配给商家',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE => '返回分配给商家重新安排',

    ParcelStatus::DELIVERED => '发表',
    ParcelStatus::PARTIAL_DELIVERED => '部分交付',
    ParcelStatus::RETURN_WAREHOUSE => '退货仓库',
    ParcelStatus::ASSIGN_MERCHANT => '分配商户',
    ParcelStatus::RETURNED_MERCHANT => '退货商家',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT => '商家收到退货',

    'hub_name'                      => '集线器名称',
    'hub_phone'                      => '集线器电话',
    'delivery_man'                  => '邮递员',
    'delivery_man_phone'            => '送货员电话'


];
