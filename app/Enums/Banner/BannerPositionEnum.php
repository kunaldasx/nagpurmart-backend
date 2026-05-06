<?php

namespace App\Enums\Banner;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static TOP()
 * @method static CAROUSEL()
 */
enum BannerPositionEnum: string
{
    use InvokableCases, Values, Names;

    case TOP = 'top';
    case CAROUSEL = 'carousel';
}
