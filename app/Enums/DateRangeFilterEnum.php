<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static LAST_30_MINUTES()
 * @method static LAST_1_HOUR()
 * @method static LAST_5_HOURS()
 * @method static LAST_1_DAY()
 * @method static LAST_7_DAYS()
 * @method static LAST_30_DAYS()
 * @method static LAST_365_DAYS()
 */
enum DateRangeFilterEnum: string
{
    use InvokableCases, Values, Names;

    case LAST_30_MINUTES = 'last_30_minutes';
    case LAST_1_HOUR = 'last_1_hour';
    case LAST_5_HOURS = 'last_5_hours';
    case LAST_1_DAY = 'last_1_day';
    case LAST_7_DAYS = 'last_7_days';
    case LAST_30_DAYS = 'last_30_days';
    case LAST_365_DAYS = 'last_365_days';
}
