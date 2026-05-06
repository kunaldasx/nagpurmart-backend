<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;
use Exception;

enum CategoryStatusEnum: string
{
    use InvokableCases, Values, Names;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    /**
     * @throws Exception
     */
    public static function fromString(string $status): self
    {
        return match ($status) {
            self::ACTIVE->value => self::ACTIVE,
            self::INACTIVE->value => self::INACTIVE,
            default => throw new Exception("Invalid status: $status"),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
