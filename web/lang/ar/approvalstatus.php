<?php

use App\Enums\ApprovalStatus;

return [
    ApprovalStatus::REJECT    =>'رفض',
    ApprovalStatus::APPROVED  =>'وافق',
    ApprovalStatus::PENDING   =>'قيد الانتظار',
    ApprovalStatus::PROCESSED =>'معالجتها',

];
