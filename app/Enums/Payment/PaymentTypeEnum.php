<?php

namespace App\Enums\Payment;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * Payment Type Enum
 *
 * This enum defines the available payment types in the application.
 * @method static COD()
 * @method static WALLET()
 * @method static RAZORPAY()
 * @method static STRIPE()
 * @method static PAYSTACK()
 * @method static FLUTTERWAVE()
 */
enum PaymentTypeEnum: string
{
    use InvokableCases, Names, Values;

    case COD = 'cod'; // Cash On Delivery
    case WALLET = 'wallet';

    case RAZORPAY = 'razorpayPayment';
    case STRIPE = 'stripePayment';

    case PAYSTACK = 'paystackPayment';
    case FLUTTERWAVE = 'flutterwavePayment';
}
