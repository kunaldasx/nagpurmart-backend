<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * DefaultSystemRolesEnum Enum
 *
 * This enum defines the default system roles available in the application.
 * It uses traits for invokable cases, values, and names for better usability.
 * @method static SUPER_ADMIN()
 * @method static SELLER()
 * @method static CUSTOMER()
 */
enum DefaultSystemRolesEnum: string
{
    use InvokableCases, Values, Names;
    case SUPER_ADMIN = 'Super Admin';
    case SELLER = 'seller';
    CASE CUSTOMER = 'customer';
}
