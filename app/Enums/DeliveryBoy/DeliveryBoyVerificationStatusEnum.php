<?php

namespace App\Enums\DeliveryBoy;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PENDING()
 * @method static REJECTED()
 * @method static VERIFIED()
 */
enum DeliveryBoyVerificationStatusEnum: string
{
    use InvokableCases, Values, Names;

    case PENDING = 'pending';
    case REJECTED = 'rejected';
    case VERIFIED = 'verified';
}
