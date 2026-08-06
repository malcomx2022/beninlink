<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'বিচারাধীন',
    ParcelStatus::PICKUP_ASSIGN                          => 'পিকআপ অ্যাসাইনড',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'পিকআপ  রি-শিডিউল',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'পিকআপ ম্যান দ্বারা প্রাপ্ত',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'পণ্যাগারে সংরক্ষন',
    ParcelStatus::TRANSFER_TO_HUB                        => 'হাবে স্থানান্তর',
    ParcelStatus::RECEIVED_BY_HUB                        => 'হাব দ্বারা সংরক্ষন',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'ডেলিভারি ম্যান অ্যাসাইন',
    // ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'ডেলিভারি রি-সিডিউল',
    ParcelStatus::RETURN_TO_COURIER                      => 'কুরিয়ার-এ ফেরত',
    ParcelStatus::PARTIAL_DELIVERED                      => 'আংশিক বিতরণ করা হয়েছে',
    ParcelStatus::DELIVERED                              => 'বিতরণ করা হয়েছে',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'মার্চেন্টের কাছে ফেরত দিন',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'মার্চেন্টের কাছে পুনরায় সময়সূচী ফেরত দেওয়া হয়েছে',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'মার্চেন্ট দ্বারা প্রাপ্ত রিটার্ন',
    // ParcelStatus::DELIVER                                => 'Deliver',
    // ParcelStatus::RETURN_WAREHOUSE                       => 'Return Warehouse',
    // ParcelStatus::ASSIGN_MERCHANT                        => 'Assign Merchant',
    // ParcelStatus::RETURNED_MERCHANT                      => 'Returned Merchant',



);
