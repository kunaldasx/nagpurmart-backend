<?php

namespace App\Enums\Store;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static NOT_APPROVED()
 * @method static APPROVED()
 */
enum StoreVerificationStatusEnum: string
{
    use InvokableCases, Values, Names;
    case APPROVED = 'approved';
    case NOT_APPROVED = 'not_approved';
}
