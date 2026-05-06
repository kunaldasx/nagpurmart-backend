<?php

namespace App\Enums\Banner;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PUBLISHED()
 * @method static DRAFT()
 */
enum BannerVisibilityStatusEnum: string
{
    use InvokableCases, Values, Names;

    case PUBLISHED = 'published';
    case DRAFT = 'draft';
}
