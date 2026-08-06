<?php

namespace App\Enums;


interface PaymentType {
    const STRIPE       = 1;//
    CONST SSL_COMMERZ  = 2;//
    CONST PAYPAL       = 3;//
    CONST PAYONEER     = 4;
    CONST BKASH        = 5;//
    CONST VISA         = 6;
    CONST SKRILL       = 7;//
    CONST AAMARPAY     = 8;//
    CONST RAZORPAY     = 9;//
}
