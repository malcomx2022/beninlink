<?php

use App\Enums\ApprovalStatus;

return [
    ApprovalStatus::REJECT    =>'拒绝',
    ApprovalStatus::APPROVED  =>'得到正式认可的',
    ApprovalStatus::PENDING   =>'待办的',
    ApprovalStatus::PROCESSED =>'处理',

];
