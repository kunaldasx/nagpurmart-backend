<?php

namespace App\Enums\Wallet;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static COMPLETED()
 * @method static PENDING()
 * @method static FAILED()
 * @method static CANCELLED()
 */
enum WalletTransactionStatusEnum: string
{
    use InvokableCases, Values, Names;

    case COMPLETED = 'completed';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
