<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'বিচারাধীন',
    ParcelStatus::PICKUP_ASSIGN                          => 'পিকআপ অ্যাসাইনড',
    ParcelStatus::PICKUP_ASSIGN_CANCEL                   => 'পিকআপ অ্যাসাইনড বাতিল',
    ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL              => 'পিকআপ  রি-শিডিউল বাতিল',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'পিকআপ  রি-শিডিউল',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'পিকআপ ম্যান দ্বারা প্রাপ্ত',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL          => 'পিকআপ ম্যান দ্বারা প্রাপ্ত বাতিল',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'পণ্যাগারে সংরক্ষন',
    ParcelStatus::RECEIVED_WAREHOUSE_CANCEL              => 'পণ্যাগারে সংরক্ষন বাতিল',
    ParcelStatus::RECEIVED_BY_HUB_CANCEL                 => 'হাব দ্বারা সংরক্ষন বাতিল',
    ParcelStatus::TRANSFER_TO_HUB                        => 'হাবে স্থানান্তর',
    ParcelStatus::TRANSFER_TO_HUB_CANCEL                 => 'হাবে স্থানান্তর বাতিল',
    ParcelStatus::RECEIVED_BY_HUB                        => 'হাব দ্বারা সংরক্ষন',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'ডেলিভারি ম্যান অ্যাসাইন',
    ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL             => 'ডেলিভারি ম্যান অ্যাসাইন বাতিল',
    ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL            => 'ডেলিভারি রি-সিডিউল বাতিল',
    ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'ডেলিভারি রি-সিডিউল',
    ParcelStatus::PARTIAL_DELIVERED_CANCEL               => 'আংশিক বিতরণ বাতিল',
    ParcelStatus::RETURN_TO_COURIER                      => 'কুরিয়ার-এ ফেরত',
    ParcelStatus::RETURN_TO_COURIER_CANCEL               => 'কুরিয়ার-এ ফেরত বাতিল',
    ParcelStatus::PARTIAL_DELIVERED                      => 'আংশিক বিতরণ করা হয়েছে',
    ParcelStatus::DELIVERED                              => 'বিতরণ করা হয়েছে',
    ParcelStatus::DELIVERED_CANCEL                       => 'বিতরণ করা বাতিল হয়েছে',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL       => 'মার্চেন্টের কাছে অ্যাসাইনড ফেরত দিন বাতিল',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'মার্চেন্টের কাছে অ্যাসাইনড ফেরত দিন',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL     => 'মার্চেন্টের কাছে পুনরায় সময়সূচী অ্যাসাইনড ফেরত বাতিল',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'মার্চেন্টের কাছে পুনরায় সময়সূচী অ্যাসাইনড ফেরত',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'মার্চেন্ট দ্বারা প্রাপ্ত রিটার্ন',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL     => 'মার্চেন্ট দ্বারা প্রাপ্ত রিটার্ন বাতিল',
    ParcelStatus::DELIVER                                => 'ডেলিভার',
    ParcelStatus::RETURN_WAREHOUSE                       => 'গুদাম ফেরত',
    ParcelStatus::ASSIGN_MERCHANT                        => 'অ্যাসাইন মার্চেন্ট',
    ParcelStatus::RETURNED_MERCHANT                      => 'মার্চেন্টে ফেরত',



);
