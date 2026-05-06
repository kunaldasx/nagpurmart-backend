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
 * @method static ADMIN()
 * @method static SELLER()
 * @method static WEB()
 */
enum GuardNameEnum: string
{
    use InvokableCases, Values, Names;

    case ADMIN = 'admin';
    case SELLER = 'seller';
    case WEB = 'web';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromString(string $guardName): self
    {
        return match ($guardName) {
            'admin' => self::ADMIN,
            'seller' => self::SELLER,
            default => self::WEB,
        };
    }
}
