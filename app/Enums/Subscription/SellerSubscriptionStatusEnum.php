<?php

namespace App\Enums\Subscription;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static ACTIVE()
 * @method static EXPIRED()
 * @method static CANCELLED()
 * @method static PENDING()
 */
enum SellerSubscriptionStatusEnum: string
{
    use InvokableCases, Values, Names;

    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case PENDING = 'pending';
}
