<?php
namespace App\Enums;

interface ApprovalStatus
{
    const REJECT   = 1;
    const APPROVED = 2;
    const PENDING  = 3;
    const PROCESSED  = 4;
}
