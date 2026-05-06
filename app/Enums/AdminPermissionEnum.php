<?php

namespace App\Enums;

use App\Models\Setting;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 *
 * @method static DASHBOARD_VIEW()
 * @method static CATEGORY_VIEW()
 * @method static CATEGORY_EDIT()
 * @method static CATEGORY_DELETE()
 * @method static CATEGORY_CREATE()
 * @method static DELIVERY_ZONE_CREATE()
 * @method static DELIVERY_ZONE_EDIT()
 * @method static DELIVERY_ZONE_DELETE()
 * @method static BRAND_VIEW()
 * @method static BRAND_EDIT()
 * @method static BRAND_DELETE()
 * @method static BRAND_CREATE()
 * @method static SELLER_EDIT()
 * @method static SELLER_DELETE()
 * @method static SELLER_CREATE()
 * @method static SELLER_VIEW()
 * @method static ROLE_CREATE()
 * @method static ROLE_EDIT()
 * @method static ROLE_DELETE()
 * @method static ROLE_VIEW()
 * @method static ROLE_PERMISSIONS_VIEW()
 * @method static ROLE_PERMISSIONS_EDIT()
 * @method static TAX_CLASS_VIEW()
 * @method static TAX_CLASS_CREATE()
 * @method static TAX_CLASS_EDIT()
 * @method static TAX_CLASS_DELETE()
 * @method static SYSTEM_USER_CREATE()
 * @method static SYSTEM_USER_EDIT()
 * @method static SYSTEM_USER_DELETE()
 * @method static SYSTEM_USER_VIEW()
 * @method static FAQ_CREATE()
 * @method static FAQ_EDIT()
 * @method static FAQ_DELETE()
 * @method static BANNER_CREATE()
 * @method static BANNER_EDIT()
 * @method static BANNER_DELETE()
 * @method static BANNER_VIEW()
 * @method static FEATURED_SECTION_CREATE()
 * @method static FEATURED_SECTION_EDIT()
 * @method static FEATURED_SECTION_DELETE()
 * @method static FEATURED_SECTION_SORTING_MODIFY()
 * @method static FEATURED_SECTION_VIEW()
 * @method static FEATURED_SECTION_SORTING_VIEW()
 * @method static DELIVERY_BOY_EDIT()
 * @method static DELIVERY_BOY_DELETE()
 * @method static DELIVERY_BOY_VIEW()
 * @method static DELIVERY_BOY_EARNING_VIEW()
 * @method static DELIVERY_BOY_EARNING_PROCESS_PAYMENT()
 * @method static DELIVERY_BOY_CASH_COLLECTION_VIEW()
 * @method static DELIVERY_BOY_CASH_COLLECTION_PROCESS()
 * @method static DELIVERY_BOY_WITHDRAWAL_VIEW()
 * @method static DELIVERY_BOY_WITHDRAWAL_PROCESS()
 * @method static SELLER_WITHDRAWAL_VIEW()
 * @method static SELLER_WITHDRAWAL_PROCESS()
 * @method static COMMISSION_VIEW()
 * @method static COMMISSION_SETTLE()
 * @method static ORDER_VIEW()
 * @method static RETURN_VIEW()
 * @method static PRODUCT_VIEW()
 * @method static PRODUCT_STATUS_UPDATE()
 * @method static PRODUCT_FAQS_VIEW()
 * @method static PROMO_CREATE()
 * @method static PROMO_EDIT()
 * @method static PROMO_DELETE()
 * @method static PROMO_VIEW()
 * @method static NOTIFICATION_CREATE()
 * @method static NOTIFICATION_EDIT()
 * @method static NOTIFICATION_DELETE()
 * @method static NOTIFICATION_VIEW()
 * @method static SUBSCRIPTION_PLAN_VIEW()
 * @method static SUBSCRIPTION_PLAN_CREATE()
 * @method static SUBSCRIPTION_PLAN_EDIT()
 * @method static SUBSCRIPTION_PLAN_DELETE()
 * @method static SUBSCRIPTION_SUBSCRIBER_VIEW()
 * @method static STORE_VIEW()
 * @method static STORE_VERIFY()
 *
 * Module-wise setting permissions
 * @method static SETTING_SYSTEM_VIEW()
 * @method static SETTING_SYSTEM_EDIT()
 * @method static SETTING_STORAGE_VIEW()
 * @method static SETTING_STORAGE_EDIT()
 * @method static SETTING_EMAIL_VIEW()
 * @method static SETTING_EMAIL_EDIT()
 * @method static SETTING_PAYMENT_VIEW()
 * @method static SETTING_PAYMENT_EDIT()
 * @method static SETTING_AUTHENTICATION_VIEW()
 * @method static SETTING_AUTHENTICATION_EDIT()
 * @method static SETTING_NOTIFICATION_VIEW()
 * @method static SETTING_NOTIFICATION_EDIT()
 * @method static SETTING_WEB_VIEW()
 * @method static SETTING_WEB_EDIT()
 * @method static SETTING_APP_VIEW()
 * @method static SETTING_APP_EDIT()
 * @method static SETTING_DELIVERY_BOY_VIEW()
 * @method static SETTING_DELIVERY_BOY_EDIT()
 * @method static SETTING_HOME_GENERAL_SETTINGS_VIEW()
 * @method static SETTING_HOME_GENERAL_SETTINGS_EDIT()
 * @method static FAQ_VIEW()
 * @method static DELIVERY_ZONE_VIEW()
 */
enum AdminPermissionEnum: string
{

    use InvokableCases, Values, Names;

    case DASHBOARD_VIEW = 'dashboard.view';
    case CATEGORY_CREATE = 'category.create';
    case CATEGORY_EDIT = 'category.edit';
    case CATEGORY_DELETE = 'category.delete';
    case CATEGORY_VIEW = 'category.view';
    case BRAND_CREATE = 'brand.create';
    case BRAND_EDIT = 'brand.edit';
    case BRAND_DELETE = 'brand.delete';
    case BRAND_VIEW = 'brand.view';
    case SELLER_CREATE = 'seller.create';
    case SELLER_EDIT = 'seller.edit';
    case SELLER_DELETE = 'seller.delete';
    case SELLER_VIEW = 'seller.view';
    // Deprecated generic setting permissions (kept for backward compatibility, no longer used)
    case SETTING_VIEW_ALL = 'setting.view.all';
    case SETTING_VIEW = 'setting.view';
    case SETTING_EDIT = 'setting.edit';
    // Module-wise setting permissions
    case SETTING_SYSTEM_VIEW = 'setting.system.view';
    case SETTING_SYSTEM_EDIT = 'setting.system.edit';
    case SETTING_STORAGE_VIEW = 'setting.storage.view';
    case SETTING_STORAGE_EDIT = 'setting.storage.edit';
    case SETTING_EMAIL_VIEW = 'setting.email.view';
    case SETTING_EMAIL_EDIT = 'setting.email.edit';
    case SETTING_PAYMENT_VIEW = 'setting.payment.view';
    case SETTING_PAYMENT_EDIT = 'setting.payment.edit';
    case SETTING_AUTHENTICATION_VIEW = 'setting.authentication.view';
    case SETTING_AUTHENTICATION_EDIT = 'setting.authentication.edit';
    case SETTING_NOTIFICATION_VIEW = 'setting.notification.view';
    case SETTING_NOTIFICATION_EDIT = 'setting.notification.edit';
    case SETTING_WEB_VIEW = 'setting.web.view';
    case SETTING_WEB_EDIT = 'setting.web.edit';
    case SETTING_APP_VIEW = 'setting.app.view';
    case SETTING_APP_EDIT = 'setting.app.edit';
    case SETTING_DELIVERY_BOY_VIEW = 'setting.delivery_boy.view';
    case SETTING_DELIVERY_BOY_EDIT = 'setting.delivery_boy.edit';
    case SETTING_HOME_GENERAL_SETTINGS_VIEW = 'setting.home_general_settings.view';
    case SETTING_HOME_GENERAL_SETTINGS_EDIT = 'setting.home_general_settings.edit';
    case ROLE_CREATE = 'role.create';
    case ROLE_EDIT = 'role.edit';
    case ROLE_DELETE = 'role.delete';
    case ROLE_VIEW = 'role.view';
    case ROLE_PERMISSIONS_VIEW = 'role.permission.view';
    case ROLE_PERMISSIONS_EDIT = 'role.permission.edit';
    case TAX_CLASS_VIEW = 'tax_class.view';
    case TAX_CLASS_CREATE = 'tax_class.create';
    case TAX_CLASS_EDIT = 'tax_class.edit';
    case TAX_CLASS_DELETE = 'tax_class.delete';
    case SYSTEM_USER_CREATE = 'system_user.create';
    case SYSTEM_USER_EDIT = 'system_user.edit';
    case SYSTEM_USER_DELETE = 'system_user.delete';
    case SYSTEM_USER_VIEW = 'system_user.view';
    case FAQ_CREATE = 'faq.create';
    case FAQ_EDIT = 'faq.edit';
    case FAQ_DELETE = 'faq.delete';
    case FAQ_VIEW = 'faq.view';
    case BANNER_CREATE = 'banner.create';
    case BANNER_EDIT = 'banner.edit';
    case BANNER_DELETE = 'banner.delete';
    case BANNER_VIEW = 'banner.view';
    case DELIVERY_ZONE_CREATE = 'delivery_zone.create';
    case DELIVERY_ZONE_EDIT = 'delivery_zone.edit';
    case DELIVERY_ZONE_DELETE = 'delivery_zone.delete';
    case DELIVERY_ZONE_VIEW = 'delivery_zone.view';
    case FEATURED_SECTION_CREATE = 'featured_section.create';
    case FEATURED_SECTION_EDIT = 'featured_section.edit';
    case FEATURED_SECTION_DELETE = 'featured_section.delete';
    case FEATURED_SECTION_SORTING_MODIFY = 'featured_section.sorting_modify';
    case FEATURED_SECTION_VIEW = 'featured_section.view';
    case FEATURED_SECTION_SORTING_VIEW = 'featured_section.sorting_view';
    case DELIVERY_BOY_EDIT = 'delivery_boy.edit';
    case DELIVERY_BOY_DELETE = 'delivery_boy.delete';
    case DELIVERY_BOY_VIEW = 'delivery_boy.view';
    case DELIVERY_BOY_EARNING_VIEW = 'delivery_boy_earning.view';
    case DELIVERY_BOY_EARNING_PROCESS_PAYMENT = 'delivery_boy_earning.process_payment';
    case DELIVERY_BOY_CASH_COLLECTION_VIEW = 'delivery_boy_cash_collection.view';
    case DELIVERY_BOY_CASH_COLLECTION_PROCESS = 'delivery_boy_cash_collection.process';
    case DELIVERY_BOY_WITHDRAWAL_VIEW = 'delivery_boy_withdrawal.view';
    case DELIVERY_BOY_WITHDRAWAL_PROCESS = 'delivery_boy_withdrawal.process';
    case SELLER_WITHDRAWAL_VIEW = 'seller_withdrawal.view';
    case SELLER_WITHDRAWAL_PROCESS = 'seller_withdrawal.process';
    case COMMISSION_VIEW = 'commission.view';
    case COMMISSION_SETTLE = 'commission.settle';
    case ORDER_VIEW = 'orders.view';
    case RETURN_VIEW = 'return.view';
    case PRODUCT_VIEW = 'product.view';
    case PRODUCT_STATUS_UPDATE = 'product.status_update';
    case PRODUCT_FAQS_VIEW = 'product_faqs.view';
    case PROMO_CREATE = 'promo.create';
    case PROMO_EDIT = 'promo.edit';
    case PROMO_DELETE = 'promo.delete';
    case PROMO_VIEW = 'promo.view';
    case NOTIFICATION_CREATE = 'notification.create';
    case NOTIFICATION_EDIT = 'notification.edit';
    case NOTIFICATION_DELETE = 'notification.delete';
    case NOTIFICATION_VIEW = 'notification.view';
    case SUBSCRIPTION_PLAN_VIEW = 'subscription_plan.view';
    case SUBSCRIPTION_PLAN_CREATE = 'subscription_plan.create';
    case SUBSCRIPTION_PLAN_EDIT = 'subscription_plan.edit';
    case SUBSCRIPTION_PLAN_DELETE = 'subscription_plan.delete';
    case SUBSCRIPTION_SUBSCRIBER_VIEW = 'subscription_subscriber.view';
    case STORE_VIEW = 'store.view';
    case STORE_VERIFY = 'store.verify';
    // Customers (web panel users)
    case CUSTOMER_VIEW = 'customer.view';

    public static function groupedPermissions(): array
    {
        $permissions = [
            'dashboard' => [
                'name' => 'Dashboard',
                'permissions' => [
                    self::DASHBOARD_VIEW(),
                ],
            ],
            'orders' => [
                'name' => 'Orders',
                'permissions' => [
                    self::ORDER_VIEW(),
                ],
            ],
            'return' => [
                'name' => 'Return Orders',
                'permissions' => [
                    self::RETURN_VIEW(),
                ],
            ],
            'category' => [
                'name' => 'Category',
                'permissions' => [
                    self::CATEGORY_VIEW(),
                    self::CATEGORY_CREATE(),
                    self::CATEGORY_EDIT(),
                    self::CATEGORY_DELETE(),
                ],
            ],
            'brand' => [
                'name' => 'Brand',
                'permissions' => [
                    self::BRAND_VIEW(),
                    self::BRAND_CREATE(),
                    self::BRAND_EDIT(),
                    self::BRAND_DELETE(),
                ],
            ],
            'seller' => [
                'name' => 'Seller',
                'permissions' => [
                    self::SELLER_VIEW(),
                    self::SELLER_CREATE(),
                    self::SELLER_EDIT(),
                    self::SELLER_DELETE(),
                ],
            ],
            'commission' => [
                'name' => 'Seller Settlement',
                'permissions' => [
                    self::COMMISSION_VIEW(),
                    self::COMMISSION_SETTLE(),
                ],
            ],
            'seller_withdrawal' => [
                'name' => 'Seller Withdrawals',
                'permissions' => [
                    self::SELLER_WITHDRAWAL_VIEW(),
                    self::SELLER_WITHDRAWAL_PROCESS(),
                ],
            ],
            'store' => [
                'name' => 'Store',
                'permissions' => [
                    self::STORE_VIEW(),
                    self::STORE_VERIFY(),
                ],
            ],
            'customers' => [
                'name' => 'Customers',
                'permissions' => [
                    self::CUSTOMER_VIEW(),
                ],
            ],
            'products' => [
                'name' => 'Products',
                'permissions' => [
                    self::PRODUCT_VIEW(),
                    self::PRODUCT_STATUS_UPDATE(),
                ],
            ],
            'products_faqs' => [
                'name' => 'Products FAQs',
                'permissions' => [
                    self::PRODUCT_FAQS_VIEW(),
                ],
            ],
            'tax_class' => [
                'name' => 'Tax Rates',
                'permissions' => [
                    self::TAX_CLASS_VIEW(),
                    self::TAX_CLASS_CREATE(),
                    self::TAX_CLASS_EDIT(),
                    self::TAX_CLASS_DELETE(),
                ],
            ],
            'delivery_boy' => [
                'name' => 'Delivery Boy',
                'permissions' => [
                    self::DELIVERY_BOY_VIEW(),
                    self::DELIVERY_BOY_EDIT(),
                    self::DELIVERY_BOY_DELETE(),
                ],
            ],
            'delivery_boy_earning' => [
                'name' => 'Delivery Boy Earning',
                'permissions' => [
                    self::DELIVERY_BOY_EARNING_VIEW(),
                    self::DELIVERY_BOY_EARNING_PROCESS_PAYMENT(),
                ],
            ],
            'delivery_boy_cash_collection' => [
                'name' => 'Delivery Boy Cash Collection',
                'permissions' => [
                    self::DELIVERY_BOY_CASH_COLLECTION_VIEW(),
                    self::DELIVERY_BOY_CASH_COLLECTION_PROCESS(),
                ],
            ],
            'delivery_boy_withdrawal' => [
                'name' => 'Delivery Boy Withdrawal',
                'permissions' => [
                    self::DELIVERY_BOY_WITHDRAWAL_VIEW(),
                    self::DELIVERY_BOY_WITHDRAWAL_PROCESS(),
                ],
            ],
            'banner' => [
                'name' => 'Banner',
                'permissions' => [
                    self::BANNER_VIEW(),
                    self::BANNER_CREATE(),
                    self::BANNER_EDIT(),
                    self::BANNER_DELETE(),
                ],
            ],
            'featured_section' => [
                'name' => 'Featured Section',
                'permissions' => [
                    self::FEATURED_SECTION_VIEW(),
                    self::FEATURED_SECTION_CREATE(),
                    self::FEATURED_SECTION_EDIT(),
                    self::FEATURED_SECTION_DELETE(),
                ],
            ],
            'featured_section_sorting' => [
                'name' => 'Featured Section Sorting',
                'permissions' => [
                    self::FEATURED_SECTION_SORTING_VIEW(),
                    self::FEATURED_SECTION_SORTING_MODIFY(),
                ],
            ],
            'promo' => [
                'name' => 'Promo',
                'permissions' => [
                    self::PROMO_VIEW(),
                    self::PROMO_CREATE(),
                    self::PROMO_EDIT(),
                    self::PROMO_DELETE(),
                ],
            ],
            'faq' => [
                'name' => 'FAQ',
                'permissions' => [
                    self::FAQ_VIEW(),
                    self::FAQ_CREATE(),
                    self::FAQ_EDIT(),
                    self::FAQ_DELETE(),
                ],
            ],
            'delivery_zone' => [
                'name' => 'Delivery Zone',
                'permissions' => [
                    self::DELIVERY_ZONE_VIEW(),
                    self::DELIVERY_ZONE_CREATE(),
                    self::DELIVERY_ZONE_EDIT(),
                    self::DELIVERY_ZONE_DELETE(),
                ],
            ],
            'notification' => [
                'name' => 'Notification',
                'permissions' => [
                    self::NOTIFICATION_VIEW(),
//                    self::NOTIFICATION_CREATE(),
                    self::NOTIFICATION_EDIT(),
                    self::NOTIFICATION_DELETE(),
                ],
            ],
            'subscription' => [
                'name' => 'Subscriptions',
                'permissions' => [
                    self::SUBSCRIPTION_PLAN_VIEW(),
                    self::SUBSCRIPTION_PLAN_CREATE(),
                    self::SUBSCRIPTION_PLAN_EDIT(),
                    self::SUBSCRIPTION_PLAN_DELETE(),
                    self::SUBSCRIPTION_SUBSCRIBER_VIEW(),
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
                    self::SYSTEM_USER_VIEW(),
                    self::SYSTEM_USER_CREATE(),
                    self::SYSTEM_USER_EDIT(),
                    self::SYSTEM_USER_DELETE(),
                ],
            ],
            'setting_system' => [
                'name' => 'System Settings',
                'permissions' => [
                    self::SETTING_SYSTEM_VIEW(),
                    self::SETTING_SYSTEM_EDIT(),
                ],
            ],
            'setting_storage' => [
                'name' => 'Storage Settings',
                'permissions' => [
                    self::SETTING_STORAGE_VIEW(),
                    self::SETTING_STORAGE_EDIT(),
                ],
            ],
            'setting_email' => [
                'name' => 'Email Settings',
                'permissions' => [
                    self::SETTING_EMAIL_VIEW(),
                    self::SETTING_EMAIL_EDIT(),
                ],
            ],
            'setting_payment' => [
                'name' => 'Payment Settings',
                'permissions' => [
                    self::SETTING_PAYMENT_VIEW(),
                    self::SETTING_PAYMENT_EDIT(),
                ],
            ],
            'setting_authentication' => [
                'name' => 'Authentication Settings',
                'permissions' => [
                    self::SETTING_AUTHENTICATION_VIEW(),
                    self::SETTING_AUTHENTICATION_EDIT(),
                ],
            ],
            'setting_notification' => [
                'name' => 'Notification Settings',
                'permissions' => [
                    self::SETTING_NOTIFICATION_VIEW(),
                    self::SETTING_NOTIFICATION_EDIT(),
                ],
            ],
            'setting_web' => [
                'name' => 'Web Settings',
                'permissions' => [
                    self::SETTING_WEB_VIEW(),
                    self::SETTING_WEB_EDIT(),
                ],
            ],
            'setting_app' => [
                'name' => 'App Settings',
                'permissions' => [
                    self::SETTING_APP_VIEW(),
                    self::SETTING_APP_EDIT(),
                ],
            ],
            'setting_delivery_boy' => [
                'name' => 'Delivery Boy Settings',
                'permissions' => [
                    self::SETTING_DELIVERY_BOY_VIEW(),
                    self::SETTING_DELIVERY_BOY_EDIT(),
                ],
            ],
            'setting_home_general' => [
                'name' => 'Home General Settings',
                'permissions' => [
                    self::SETTING_HOME_GENERAL_SETTINGS_VIEW(),
                    self::SETTING_HOME_GENERAL_SETTINGS_EDIT(),
                ],
            ],
        ];

        if (Setting::isSystemVendorTypeSingle()) {
            unset($permissions['subscription']);
        }

        return $permissions;
    }
}