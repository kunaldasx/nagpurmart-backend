<?php

namespace App\Enums\Store;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static DRAFT()
 * @method static VISIBLE()
 */
enum StoreVisibilityStatusEnum: string
{
    use InvokableCases, Values, Names;

    case VISIBLE = 'visible';
    case DRAFT = 'draft';
}
