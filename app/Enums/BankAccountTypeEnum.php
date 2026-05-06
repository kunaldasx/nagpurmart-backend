<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum BankAccountTypeEnum: string
{
    use InvokableCases, Values, Names;
    case CHECKING = 'checking';
    case SAVINGS = 'savings';
}
