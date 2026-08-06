<?php
namespace App\Enums\Wallet;
interface WalletStatus{
    const PENDING  = 1;
    const APPROVED = 2;
    const REJECTED = 3;
}