<?php

namespace App\Enums\Subscription;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static STORE_LIMIT()
 * @method static PRODUCT_LIMIT()
 * @method static ROLE_LIMIT()
 * @method static SYSTEM_USER_LIMIT()
 * @method static VARIATION_PRODUCT_LIMIT()
 */
enum SubscriptionPlanKeyEnum : string
{
    use InvokableCases, Values, Names;

    case STORE_LIMIT = 'store_limit';
    case PRODUCT_LIMIT = 'product_limit';
    case ROLE_LIMIT = 'role_limit';
    case SYSTEM_USER_LIMIT = 'system_user_limit';
}
