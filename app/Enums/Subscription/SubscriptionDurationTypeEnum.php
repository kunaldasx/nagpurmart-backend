<?php

namespace App\Enums\Subscription;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum SubscriptionDurationTypeEnum: string
{
    use InvokableCases, Values, Names;

    case UNLIMITED = 'unlimited';
    case DAYS = 'days';
}
