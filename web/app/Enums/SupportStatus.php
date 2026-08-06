<?php
namespace App\Enums;
interface SupportStatus{
    const PENDING    = 1;
    const PROCESSING = 2;
    const RESOLVED   = 3;
    const CLOSED     = 4;
}