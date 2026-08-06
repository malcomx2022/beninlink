<?php
use App\Enums\Wallet\WalletStatus;
return [
    WalletStatus::PENDING     => 'Pending',
    WalletStatus::APPROVED    => 'Confirm',
    WalletStatus::REJECTED    => 'Rejected',
];