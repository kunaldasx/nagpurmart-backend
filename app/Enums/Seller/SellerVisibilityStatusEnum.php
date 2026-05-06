<?php

namespace App\Enums\Seller;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum SellerVisibilityStatusEnum: string
{
    use InvokableCases, Values, Names;

    case Visible = 'visible';
    case Draft = 'draft';
}
