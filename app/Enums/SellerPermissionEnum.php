<?php

namespace App\Enums;

use App\Models\Setting;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static CATEGORY_VIEW()
 * @method static ROLE_PERMISSIONS_VIEW()
 * @method static ROLE_PERMISSIONS_EDIT()
 * @method static ROLE_VIEW()
 * @method static ROLE_CREATE()
 * @method static ROLE_EDIT()
 * @method static ROLE_DELETE()
 * @method static SYSTEM_USER_CREATE()
 * @method static SYSTEM_USER_EDIT()
 * @method static SYSTEM_USER_DELETE()
 * @method static SYSTEM_USER_VIEW()
 * @method static STORE_VIEW()
 * @method static STORE_CREATE()
 * @method static STORE_EDIT()
 * @method static STORE_DELETE()
 * @method static ATTRIBUTE_VIEW()
 * @method static ATTRIBUTE_CREATE()
 * @method static ATTRIBUTE_EDIT()
 * @method static ATTRIBUTE_DELETE()
 * @method static PRODUCT_CONDITION_CREATE()
 * @method static PRODUCT_CONDITION_EDIT()
 * @method static PRODUCT_CONDITION_DELETE()
 * @method static PRODUCT_VIEW()
 * @method static PRODUCT_CREATE()
 * @method static PRODUCT_EDIT()
 * @method static PRODUCT_DELETE()
 * @method static PRODUCT_FAQ_VIEW()
 * @method static PRODUCT_FAQ_CREATE()
 * @method static PRODUCT_FAQ_EDIT()
 * @method static PRODUCT_FAQ_DELETE()
 * @method static ORDER_VIEW()
 * @method static ORDER_EDIT()
 * @method static ORDER_UPDATE_STATUS()
 * @method static EARNING_VIEW()
 * @method static NOTIFICATION_CREATE()
 * @method static NOTIFICATION_VIEW()
 * @method static NOTIFICATION_EDIT()
 * @method static NOTIFICATION_DELETE()
 * @method static TAX_RATE_CREATE()
 * @method static TAX_RATE_EDIT()
 * @method static TAX_RATE_DELETE()
 * @method static TAX_RATE_VIEW()
 * @method static WALLET_VIEW()
 * @method static WITHDRAWAL_VIEW()
 * @method static WITHDRAWAL_REQUEST()
 * @method static RETURN_VIEW()
 * @method static RETURN_DECIDE()
 * @method static BRAND_VIEW()
 * @method static SUBSCRIPTION_VIEW()
 * @method static SUBSCRIPTION_BUY()
 * @method static DASHBOARD_VIEW()
 */
enum SellerPermissionEnum: string
{
    use InvokableCases, Values, Names;


    case DASHBOARD_VIEW = 'dashboard.view';
    case ROLE_VIEW = 'role.view';
    case ROLE_CREATE = 'role.create';
    case ROLE_EDIT = 'role.edit';
    case ROLE_DELETE = 'role.delete';
    case ROLE_PERMISSIONS_VIEW = 'role.permission.view';
    case ROLE_PERMISSIONS_EDIT = 'role.permission.edit';
    case SYSTEM_USER_CREATE = 'system_user.create';
    case SYSTEM_USER_EDIT = 'system_user.edit';
    case SYSTEM_USER_DELETE = 'system_user.delete';
    case SYSTEM_USER_VIEW = 'system_user.view';
    case STORE_VIEW = 'store.view';
    case STORE_CREATE = 'store.create';
    case STORE_EDIT = 'store.edit';
    case STORE_DELETE = 'store.delete';
    case ATTRIBUTE_VIEW = 'attribute.view';
    case ATTRIBUTE_CREATE = 'attribute.create';
    case ATTRIBUTE_EDIT = 'attribute.edit';
    case ATTRIBUTE_DELETE = 'attribute.delete';
    case PRODUCT_CONDITION_CREATE = 'product_condition.create';
    case PRODUCT_CONDITION_EDIT = 'product_condition.edit';
    case PRODUCT_CONDITION_DELETE = 'product_condition.delete';
    case PRODUCT_VIEW = 'product.view';
    case PRODUCT_CREATE = 'product.create';
    case PRODUCT_EDIT = 'product.edit';
    case PRODUCT_DELETE = 'product.delete';
    case PRODUCT_FAQ_VIEW = 'product_faq.view';
    case PRODUCT_FAQ_CREATE = 'product_faq.create';
    case PRODUCT_FAQ_EDIT = 'product_faq.edit';
    case PRODUCT_FAQ_DELETE = 'product_faq.delete';
    case ORDER_VIEW = 'order.view';
    case ORDER_EDIT = 'order.edit';
    case ORDER_UPDATE_STATUS = 'order.update_status';
    case EARNING_VIEW = 'earning.view';
    case NOTIFICATION_CREATE = 'notification.create';
    case NOTIFICATION_VIEW = 'notification.view';
    case NOTIFICATION_EDIT = 'notification.edit';
    case NOTIFICATION_DELETE = 'notification.delete';
    case TAX_RATE_CREATE = 'tax_rate.create';
    case TAX_RATE_EDIT = 'tax_rate.edit';
    case TAX_RATE_DELETE = 'tax_rate.delete';
    case TAX_RATE_VIEW = 'tax_rate.view';
    case WALLET_VIEW = 'wallet.view';
    case WITHDRAWAL_VIEW = 'withdrawal.view';
    case WITHDRAWAL_REQUEST = 'withdrawal.request';
    case RETURN_VIEW = 'return.view';
    case RETURN_DECIDE = 'return.decide';
    case CATEGORY_VIEW = 'category.view';
    case BRAND_VIEW = 'brand.view';
    case SUBSCRIPTION_VIEW = 'subscription.view';
    case SUBSCRIPTION_BUY = 'subscription.buy';


    public static function groupedPermissions(): array
    {
        $permissions = [
            'dashboard' => [
                'name' => 'Dashboard',
                'permissions' => [
                    self::DASHBOARD_VIEW(),
                ],
            ],
            'wallet' => [
                'name' => 'Wallet',
                'permissions' => [
                    self::WALLET_VIEW(),
                ],
            ],
            'withdrawal' => [
                'name' => 'Withdrawal',
                'permissions' => [
                    self::WITHDRAWAL_VIEW(),
                    self::WITHDRAWAL_REQUEST(),
                ],
            ],
            'earning' => [
                'name' => 'Settlements',
                'permissions' => [
                    self::EARNING_VIEW(),
                ],
            ],
            'order' => [
                'name' => 'Order',
                'permissions' => [
                    self::ORDER_VIEW(),
                    self::ORDER_EDIT(),
                    self::ORDER_UPDATE_STATUS(),
                ],
            ],
            'return' => [
                'name' => 'Return Orders',
                'permissions' => [
                    self::RETURN_VIEW(),
                    self::RETURN_DECIDE(),
                ],
            ],
            'category' => [
                'name' => 'Category',
                'permissions' => [
                    self::CATEGORY_VIEW(),
                ],
            ],
            'brand' => [
                'name' => 'Brand',
                'permissions' => [
                    self::BRAND_VIEW(),
                ],
            ],
            'subscription' => [
                'name' => 'Subscriptions',
                'permissions' => [
                    self::SUBSCRIPTION_VIEW(),
                    self::SUBSCRIPTION_BUY(),
                ],
            ],
            'attribute' => [
                'name' => 'Attribute',
                'permissions' => [
                    self::ATTRIBUTE_VIEW(),
                    self::ATTRIBUTE_CREATE(),
                    self::ATTRIBUTE_EDIT(),
                    self::ATTRIBUTE_DELETE(),
                ],
            ],
            'product' => [
                'name' => 'Product',
                'permissions' => [
                    self::PRODUCT_VIEW(),
                    self::PRODUCT_CREATE(),
                    self::PRODUCT_EDIT(),
                    self::PRODUCT_DELETE(),
                ],
            ],
            'product_faq' => [
                'name' => 'Product FAQ',
                'permissions' => [
                    self::PRODUCT_FAQ_VIEW(),
                    self::PRODUCT_FAQ_CREATE(),
                    self::PRODUCT_FAQ_EDIT(),
                    self::PRODUCT_FAQ_DELETE(),
                ],
            ],
            'tax_rate' => [
                'name' => 'Tax Rate',
                'permissions' => [
                    self::TAX_RATE_VIEW(),
                    self::TAX_RATE_CREATE(),
                    self::TAX_RATE_EDIT(),
                    self::TAX_RATE_DELETE(),
                ],
            ],
            'store' => [
                'name' => 'Store',
                'permissions' => [
                    self::STORE_VIEW(),
                    self::STORE_CREATE(),
                    self::STORE_EDIT(),
                    self::STORE_DELETE(),
                ],
            ],
            'notification' => [
                'name' => 'Notification',
                'permissions' => [
                    self::NOTIFICATION_VIEW(),
                    self::NOTIFICATION_CREATE(),
                    self::NOTIFICATION_EDIT(),
                    self::NOTIFICATION_DELETE(),
                ],
            ],
            'role' => [
                'name' => 'Role',
                'permissions' => [
                    self::ROLE_VIEW(),
                    self::ROLE_CREATE(),
                    self::ROLE_EDIT(),
                    self::ROLE_DELETE(),
                ],
            ],
            'permission' => [
                'name' => 'Permission',
                'permissions' => [
                    self::ROLE_PERMISSIONS_VIEW(),
                    self::ROLE_PERMISSIONS_EDIT(),
                ],
            ],
            'system_user' => [
                'name' => 'System User',
                'permissions' => [
                    self::SYSTEM_USER_CREATE(),
                    self::SYSTEM_USER_EDIT(),
                    self::SYSTEM_USER_DELETE(),
                    self::SYSTEM_USER_VIEW(),
                ],
            ],

//            'product_condition' => [
//                'name' => 'Product Condition',
//                'permissions' => [
//                    self::PRODUCT_CONDITION_CREATE(),
//                    self::PRODUCT_CONDITION_EDIT(),
//                    self::PRODUCT_CONDITION_DELETE(),
//                ],
//            ],

        ];

        if (Setting::isSystemVendorTypeSingle()) {
            unset($permissions['subscription']);
        }

        return $permissions;
    }
}
