<?php

namespace App\Enums\Payment;

enum PaymentModeEnum: string
{
    case Test = "test";
    case Live = "live";
}
