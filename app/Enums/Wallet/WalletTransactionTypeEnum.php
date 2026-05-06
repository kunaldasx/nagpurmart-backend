<?php

namespace App\Enums\Wallet;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PAYMENT()
 * @method static DEPOSIT()
 * @method static REFUND()
 * @method static ADJUSTMENT()
 */
enum WalletTransactionTypeEnum: string
{
    use InvokableCases, Values, Names;

    case DEPOSIT = 'deposit';
    case PAYMENT = 'payment';
    case REFUND = 'refund';
    case ADJUSTMENT = 'adjustment';
}
