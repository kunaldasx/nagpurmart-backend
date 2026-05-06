<?php

namespace App\Enums\Seller;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum SellerVerificationStatusEnum: string
{
    use InvokableCases, Values, Names;

    case Approved = 'approved';
    case Rejected = 'rejected';
    case NotApproved = 'not_approved';
}
