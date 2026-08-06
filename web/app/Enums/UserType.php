<?php
namespace App\Enums;

interface UserType
{
    const ADMIN       = 1;
    const MERCHANT    = 2;
    const DELIVERYMAN = 3;
    const INCHARGE    = 4;
    CONST HUB         = 5;
    CONST SUPER_ADMIN = 6;
}
