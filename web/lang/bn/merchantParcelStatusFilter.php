<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'বিচারাধীন',
    ParcelStatus::PICKUP_ASSIGN                          => 'পিকআপ অ্যাসাইনড',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'পণ্যাগারে সংরক্ষন',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'ডেলিভারি ম্যান অ্যাসাইন',
    ParcelStatus::PARTIAL_DELIVERED                      => 'আংশিক বিতরণ করা হয়েছে',
    ParcelStatus::DELIVERED                              => 'বিতরণ করা হয়েছে',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'মার্চেন্টের কাছে ফেরত দিন',

);
