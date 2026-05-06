<?php

namespace App\Enums\DeliveryBoy;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PENDING()
 * @method static APPROVED()
 * @method static REJECTED()
 */
enum DeliveryBoyWithdrawalStatusEnum: string
{
    use InvokableCases, Values, Names;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
