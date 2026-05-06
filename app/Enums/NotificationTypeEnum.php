<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * NotificationTypeEnum Enum
 *
 * This enum defines the notification types available in the application.
 * It uses traits for invokable cases, values, and names for better usability.
 * @method static GENERAL()
 * @method static ORDER()
 * @method static PAYMENT()
 * @method static DELIVERY()
 * @method static PROMOTION()
 * @method static SYSTEM()
 * @method static PRODUCT()
 * @method static ORDER_UPDATE()
 * @method static NEW_ORDER()
 * @method static RETURN_ORDER()
 * @method static RETURN_ORDER_UPDATE()
 * @method static WALLET_TRANSACTION()
 * @method static WITHDRAWAL_REQUEST()
 * @method static WITHDRAWAL_PROCESS()
 * @method static SETTLEMENT_PROCESS()
 * @method static SETTLEMENT_CREATE()
 * @method static ORDER_READY_FOR_PICKUP()
 * @method static RETURN_ORDER_AVAILABLE()
 */
enum NotificationTypeEnum: string
{
    use InvokableCases, Values, Names;

    case GENERAL = 'general';
    case ORDER = 'order';
    case PAYMENT = 'payment';
    case DELIVERY = 'delivery';
    case PROMOTION = 'promotion';
    case SYSTEM = 'system';
    case PRODUCT = 'product';

    case ORDER_UPDATE = 'order_update';
    case NEW_ORDER = 'new_order';
    case RETURN_ORDER = 'return_order';
    case RETURN_ORDER_UPDATE = 'return_order_update';
    case WALLET_TRANSACTION = 'wallet_transaction';
    case WITHDRAWAL_REQUEST = 'withdrawal_request';
    case WITHDRAWAL_PROCESS = 'withdrawal_process';

    case SETTLEMENT_PROCESS = 'settlement_process';
    case SETTLEMENT_CREATE = 'settlement_create';
    case ORDER_READY_FOR_PICKUP = 'order_ready_for_pickup';
    case RETURN_ORDER_AVAILABLE = 'return_order_available';

}
