<?php

use App\Enums\ApprovalStatus;

return [
    ApprovalStatus::REJECT    =>'বাতিল',
    ApprovalStatus::APPROVED  =>'অনুমোদিত',
    ApprovalStatus::PENDING   =>'বিচারাধীন',
    ApprovalStatus::PROCESSED =>'প্রসেসড',

];
