<?php

use App\Enums\PaymentType;

return array(
    PaymentType::STRIPE       => 'Stripe',
    PaymentType::SSL_COMMERZ  => 'SSL Commerz',
    PaymentType::PAYPAL       => 'Paypal',
    PaymentType::PAYONEER     => 'Payoneer',
    PaymentType::BKASH        => 'Bkash',
    PaymentType::VISA         => 'Visa',
    PaymentType::SKRILL       => 'Skrill',
    PaymentType::AAMARPAY     =>  'Aamarpay',
);
