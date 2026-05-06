<?php

namespace App\Enums\Product;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum ProductVarificationStatusEnum: string
{
    use InvokableCases, Values, Names;

    case PENDING = 'pending_verification';
    case REJECTED = 'rejected';
    case APPROVED = 'approved';
}
