<?php

use App\Enums\ParcelStatus;

return [
    ParcelStatus::PICKUP_ASSIGN                 => 'পিকআপ নিয়োগ করা হয়েছে',
    ParcelStatus::PICKUP_RE_SCHEDULE            => 'পার্সেলটি পিকআপ পুনরায় করা হয়েছে',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN        => 'পার্সেলটি পিকআপ ম্যান পেয়েছে',
    ParcelStatus::RECEIVED_WAREHOUSE            => 'পার্সেলটি পণ্যাগারে সংরক্ষন করা হয়েছে',
    ParcelStatus::TRANSFER_TO_HUB               => 'পার্সেলটি হাবে স্থানান্তর করা হয়েছে',
    ParcelStatus::RECEIVED_BY_HUB               => 'পার্সেলটি পণ্যাগারে সংরক্ষন করা হয়েছে',
    ParcelStatus::DELIVERY_MAN_ASSIGN           => 'সরবরাহকারী নিয়োগ করা হয়েছে',
    ParcelStatus::DELIVERY_RE_SCHEDULE          => 'সরবরাহকারী পুনরায় নিয়োগ করা হয়েছে',

    ParcelStatus::DELIVER                       => 'প্রদান করা',
    ParcelStatus::RETURN_TO_COURIER             => 'কুরিয়ার-এ ফেরত আসছে',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT     => 'মার্চেন্টের কাছে ফেরত দেওয়ার জন্য নিয়োগ করা হয়েছে',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE   => 'মার্চেন্টের কাছে পুনরায় ফেরত দেওয়ার জন্য নিয়োগ করা হয়েছে',

    ParcelStatus::DELIVERED                     => 'সফলভাবে বিতরণ করা হয়েছে',
    ParcelStatus::PARTIAL_DELIVERED             => 'আংশিক বিতরণ করা হয়েছে',
    ParcelStatus::RETURN_WAREHOUSE              => 'পণ্যাগারে ফেরত করা হয়েছে',
    ParcelStatus::ASSIGN_MERCHANT               => 'অ্যাসাইন মার্চেন্ট',
    ParcelStatus::RETURNED_MERCHANT             => 'মার্চেন্টের কাছে ফেরত এসেছে',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT   => 'মার্চেন্ট দ্বারা প্রাপ্ত রিটার্ন',

    'hub_name'                                   => 'হাবের নাম',
    'hub_phone'                                  => 'হাব ফোন',
    'delivery_man'                               => 'সরবরাহকারী',
    'delivery_man_phone'                         => 'ডেলিভারি ম্যান ফোন'


];
