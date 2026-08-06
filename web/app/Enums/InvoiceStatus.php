<?php
namespace App\Enums;

Interface InvoiceStatus{
    const UNPAID        = 0;
    const PROCESSING    = 2;
    const PAID          = 3;
}
