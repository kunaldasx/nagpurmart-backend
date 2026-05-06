<?php

namespace App\Enums\DeliveryBoy;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static BIKE()
 * @method static CAR()
 */
enum DeliveryBoyVehicleTypeEnum: string
{
    use InvokableCases, Values, Names;

    case BIKE = 'bike';
    case CAR = 'car';
}
