<?php

namespace App\Enums\Store;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static ONLINE()
 * @method static OFFLINE()
 */
enum StoreStatusEnum: string
{
    use InvokableCases, Values, Names;
    case ONLINE = 'online';
    case OFFLINE = 'offline';
}
