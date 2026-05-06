<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PAYMENT()
 * @method static AUTHENTICATION()
 * @method static NOTIFICATION()
 * @method static WEB()
 * @method static APP()
 * @method static DELIVERY_BOY()
 * @method static SELLER()
 * @method static HOME_GENERAL_SETTINGS()
 * @method static SUBSCRIPTION()
 * @method static SYSTEM()
 * @method static STORAGE()
 * @method static EMAIL()
 */
enum SettingTypeEnum: string
{
    use InvokableCases, Values, Names;
    case SYSTEM = 'system';
    case STORAGE = 'storage';
    case EMAIL = 'email';
    case PAYMENT = 'payment';
    case AUTHENTICATION = 'authentication';
    case NOTIFICATION = 'notification';
    case WEB = 'web';
    case APP = 'app';
    case DELIVERY_BOY = 'delivery_boy';
    case SELLER = 'seller';
    case HOME_GENERAL_SETTINGS = 'home_general_settings';
    case SUBSCRIPTION = 'subscription';
}
