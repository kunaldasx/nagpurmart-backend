<?php

namespace App\Enums\Subscription;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static COMPLETED()
 * @method static PENDING()
 * @method static FAILED()
 * @method static CANCELLED()
 */
enum SubscriptionTransactionStatusEnum: string
{
    use InvokableCases, Values, Names;

    case COMPLETED = 'completed';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
