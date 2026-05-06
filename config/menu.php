<?php

return [

    'admin' => [
        'dashboard' => [
            'icon' => 'ti-home',
            'route' => 'admin.dashboard',
            'title' => 'labels.dashboard',
            'active' => 'dashboard',
        ],
        'orders' => [
            'icon' => 'ti-package',
            'route' => 'admin.orders.index',
            'title' => 'labels.orders',
            'active' => 'orders',
            'permission' => 'orders.view',
        ],
        'categories' => [
            'icon' => 'ti-category-2',
            'title' => 'labels.categories',
            'active' => 'categories',
            'route' => [
                'categories' => [
                    'sub_active' => 'categories',
                    'sub_route' => 'admin.categories.index',
                    'sub_title' => 'labels.categories',
                    'permission' => 'category.view',
                ],
                'sort' => [
                    'sub_active' => 'sort',
                    'sub_route' => 'admin.categories.sort',
                    'sub_title' => 'labels.sort',
                    'permission' => 'category.create',
                ],
                'bulk_upload' => [
                    'sub_active' => 'bulk_upload',
                    'sub_route' => 'admin.categories.bulk-upload',
                    'sub_title' => 'labels.bulk_upload',
                    'permission' => 'category.create',
                ],
            ],
        ],
        'brands' => [
            'icon' => 'ti-sparkles',
            'route' => 'admin.brands.index',
            'title' => 'labels.brands',
            'active' => 'brands',
            'permission' => 'brand.view',
        ],
        'customers' => [
            'icon' => 'ti-users',
            'title' => 'labels.customers',
            'active' => 'customers',
            'route' => [
                'customers' => [
                    'sub_active' => 'customers',
                    'sub_route' => 'admin.customers.index',
                    'sub_title' => 'labels.customers',
                    'permission' => 'customer.view',
                ],
                'transactions' => [
                    'sub_active' => 'transactions',
                    'sub_route' => 'admin.wallet.transactions',
                    'sub_title' => 'labels.wallet_transactions',
                    'permission' => 'orders.view',
                ],
                'deposits' => [
                    'sub_active' => 'deposits',
                    'sub_route' => 'admin.wallet.deposits',
                    'sub_title' => 'labels.pending_wallet_deposits',
                    'permission' => 'orders.view',
                ],
            ],
        ],
        'subscriptions' => [
            'icon' => 'ti-user-dollar',
            'title' => 'labels.subscriptions',
            'active' => 'subscriptions',
            'route' => [
                'plans' => [
                    'sub_active' => 'plans',
                    'sub_route' => 'admin.subscription-plans.index',
                    'sub_title' => 'labels.plans',
                    'permission' => 'subscription_plan.view',
                ],
                'subscribers' => [
                    'sub_active' => 'subscribers',
                    'sub_route' => 'admin.subscription-plans.subscribers',
                    'sub_title' => 'labels.subscribers',
                    'permission' => 'subscription_subscriber.view',
                ],
            ],
        ],
        'seller_management' => [
            'icon' => 'ti-users-group',
            'title' => 'labels.seller_management',
            'active' => 'sellers',
            'route' => [
                'sellers' => [
                    'sub_active' => 'sellers',
                    'sub_route' => 'admin.sellers.index',
                    'sub_title' => 'labels.sellers',
                    'permission' => 'seller.view',
                ],
                'add_sellers' => [
                    'sub_active' => 'add_sellers',
                    'sub_route' => 'admin.sellers.create',
                    'sub_title' => 'labels.add_sellers',
                    'permission' => 'seller.create',
                ],
                'earning_settlement' => [
                    'sub_active' => 'seller_earning_settlement',
                    'sub_route' => 'admin.commissions.index',
                    'sub_title' => 'labels.earning_settlement',
                    'permission' => 'commission.view',
                ],
                'seller_withdrawals' => [
                    'sub_active' => 'seller_withdrawals',
                    'sub_route' => 'admin.seller-withdrawals.index',
                    'sub_title' => 'labels.seller_withdrawals',
                    'permission' => 'seller_withdrawal.view',
                ],
                'seller_withdrawal_history' => [
                    'sub_active' => 'seller_withdrawal_history',
                    'sub_route' => 'admin.seller-withdrawals.history',
                    'sub_title' => 'labels.seller_withdrawal_history',
                    'permission' => 'seller_withdrawal.view',
                ],
            ],
        ],
        'stores' => [
            'icon' => 'ti-building-warehouse',
            'route' => 'admin.sellers.store.index',
            'title' => 'labels.stores',
            'active' => 'stores',
            'permission' => 'store.view',
        ],
        'products' => [
            'icon' => 'ti-cube-spark',
            'title' => 'labels.products',
            'active' => 'products',
            'route' => [
                'products' => [
                    'sub_active' => 'products',
                    'sub_route' => 'admin.products.index',
                    'sub_title' => 'labels.products',
                    'permission' => 'product.view',
                ],
                'pending_approval_products' => [
                    'sub_active' => 'pending_approval_products',
                    'sub_route' => 'admin.products.index',
                    'route_param' => ['verification_status' => 'pending_verification'],
                    'sub_title' => 'labels.pending_approval_products',
                    'permission' => 'product.view',
                ],
                'product_faqs' => [
                    'sub_active' => 'product_faqs',
                    'sub_route' => 'admin.product_faqs.index',
                    'sub_title' => 'labels.product_faqs',
                    'permission' => 'product_faqs.view',
                ],
            ],
        ],
        'tax_rates' => [
            'icon' => 'ti-square-rounded-percentage',
            'route' => 'admin.tax-rates.index',
            'title' => 'labels.tax_rates',
            'active' => 'tax_rates',
            'permission' => 'tax_class.view',
        ],
        'delivery_boy_management' => [
            'icon' => 'ti-truck-delivery',
            'title' => 'labels.delivery_boy_management',
            'active' => 'delivery_boy_management',
            'route' => [
                'delivery_boys' => [
                    'sub_active' => 'delivery_boys',
                    'sub_route' => 'admin.delivery-boys.index',
                    'sub_title' => 'labels.delivery_boys',
                    'permission' => 'delivery_boy.view',
                ],
                'delivery_boy_earnings' => [
                    'sub_active' => 'delivery_boy_earnings',
                    'sub_route' => 'admin.delivery-boy-earnings.index',
                    'sub_title' => 'labels.delivery_boy_earnings',
                    'permission' => 'delivery_boy_earning.view',
                ],
                'earning_history' => [
                    'sub_active' => 'earning_history',
                    'sub_route' => 'admin.delivery-boy-earnings.history',
                    'sub_title' => 'labels.earning_history',
                    'permission' => 'delivery_boy_earning.view',
                ],
                'delivery_boy_cash_collections' => [
                    'sub_active' => 'delivery_boy_cash_collections',
                    'sub_route' => 'admin.delivery-boy-cash-collections.index',
                    'sub_title' => 'labels.delivery_boy_cash_collections',
                    'permission' => 'delivery_boy_cash_collection.view',
                ],
                'cash_collection_history' => [
                    'sub_active' => 'cash_collection_history',
                    'sub_route' => 'admin.delivery-boy-cash-collections.history',
                    'sub_title' => 'labels.cash_collection_history',
                    'permission' => 'delivery_boy_cash_collection.view',
                ],
                'delivery_boy_withdrawals' => [
                    'sub_active' => 'delivery_boy_withdrawals',
                    'sub_route' => 'admin.delivery-boy-withdrawals.index',
                    'sub_title' => 'labels.delivery_boy_withdrawals',
                    'permission' => 'delivery_boy_withdrawal.view',
                ],
                'withdrawal_history' => [
                    'sub_active' => 'withdrawal_history',
                    'sub_route' => 'admin.delivery-boy-withdrawals.history',
                    'sub_title' => 'labels.withdrawal_history',
                    'permission' => 'delivery_boy_withdrawal.view',
                ],
            ],
        ],
        'banners' => [
            'icon' => 'ti-photo',
            'route' => 'admin.banners.index',
            'title' => 'labels.banners',
            'active' => 'banners',
            'permission' => 'banner.view',
        ],
        'featured_section' => [
            'icon' => 'ti-star',
            'title' => 'labels.featured_section',
            'active' => 'featured_section',
            'route' => [
                'featured_section' => [
                    'sub_active' => 'featured_section',
                    'sub_route' => 'admin.featured-sections.index',
                    'sub_title' => 'labels.featured_section',
                    'permission' => 'featured_section.view',
                ],
                'sort_featured_section' => [
                    'sub_active' => 'sort_featured_section',
                    'sub_route' => 'admin.featured-sections.sort',
                    'sub_title' => 'labels.sort_featured_section',
                    'permission' => 'featured_section.sorting_view',
                ],
            ],
        ],
        'promos' => [
            'icon' => 'ti-ticket',
            'route' => 'admin.promos.index',
            'title' => 'labels.promos',
            'active' => 'promos',
            'permission' => 'promo.view',
        ],
        'faqs' => [
            'icon' => 'ti-help-circle',
            'route' => 'admin.faqs.index',
            'title' => 'labels.faqs',
            'active' => 'faqs',
            'permission' => 'faq.view',
        ],
        'delivery_zones' => [
            'icon' => 'ti-map-pin',
            'route' => 'admin.delivery-zones.index',
            'title' => 'labels.delivery_zones',
            'active' => 'delivery_zones',
            'permission' => 'delivery_zone.view',
        ],
        'notifications' => [
            'icon' => 'ti-bell-ringing',
            'route' => 'admin.notifications.index',
            'title' => 'labels.notifications',
            'active' => 'notifications',
            'permission' => 'notification.view',
        ],
        'roles_permissions' => [
            'icon' => 'ti-users-group',
            'title' => 'labels.roles_permissions',
            'active' => 'roles_permissions',
            'route' => [
                'roles' => [
                    'sub_active' => 'roles',
                    'sub_route' => 'admin.roles.index',
                    'sub_title' => 'labels.roles',
                    'permission' => 'role.view',
                ],
                'system_users' => [
                    'sub_active' => 'system_users',
                    'sub_route' => 'admin.system-users.index',
                    'sub_title' => 'labels.system_users',
                    'permission' => 'system_user.view',
                ]
            ],
        ],
        'settings' => [
            'icon' => 'ti-settings',
            'title' => 'labels.settings',
            'active' => 'settings',
            'route' => [
                'system' => [
                    'sub_active' => 'system',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'system'],
                    'sub_title' => 'labels.menu_system',
                    'permission' => 'setting.system.view',
                ],
                'web' => [
                    'sub_active' => 'web',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'web'],
                    'sub_title' => 'labels.menu_web',
                    'permission' => 'setting.web.view',
                ],
                'app' => [
                    'sub_active' => 'app',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'app'],
                    'sub_title' => 'labels.menu_app',
                    'permission' => 'setting.app.view',
                ],
                'home_general_settings' => [
                    'sub_active' => 'home_general_settings',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'home_general_settings'],
                    'sub_title' => 'labels.home_general_settings',
                    'permission' => 'setting.home_general_settings.view',
                ],
                'storage' => [
                    'sub_active' => 'storage',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'storage'],
                    'sub_title' => 'labels.menu_storage',
                    'permission' => 'setting.storage.view',
                ],
                'authentication' => [
                    'sub_active' => 'authentication',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'authentication'],
                    'sub_title' => 'labels.menu_authentication',
                    'permission' => 'setting.authentication.view',
                ],
                'email' => [
                    'sub_active' => 'email',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'email'],
                    'sub_title' => 'labels.email',
                    'permission' => 'setting.email.view',
                ],
                'payment' => [
                    'sub_active' => 'payment',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'payment'],
                    'sub_title' => 'labels.menu_payment',
                    'permission' => 'setting.payment.view',
                ],
                'notification' => [
                    'sub_active' => 'notification',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'notification'],
                    'sub_title' => 'labels.menu_notification',
                    'permission' => 'setting.notification.view',
                ],
                'delivery_boy' => [
                    'sub_active' => 'delivery_boy',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'delivery_boy'],
                    'sub_title' => 'labels.delivery_boy',
                    'permission' => 'setting.delivery_boy.view',
                ],
                'seller' => [
                    'sub_active' => 'seller',
                    'sub_route' => 'admin.settings.show',
                    'route_param' => ['setting' => 'seller'],
                    'sub_title' => 'labels.seller',
                    'permission' => 'setting.seller.view',
                ],
            ],
        ],
        'system_updates' => [
            'icon' => 'ti-package',
            'route' => 'admin.system-updates.index',
            'title' => 'labels.system_updates',
            'active' => 'system_updates',
            'permission' => 'setting.system.view',
        ],
        'logout' => [
            'icon' => 'ti-logout-2',
            'route' => 'admin.logout',
            'title' => 'labels.logout',
        ],
    ],

    'delivery-partner' => [
        'dashboard' => [
            'icon' => 'ti-home',
            'route' => 'delivery-partner.dashboard',
            'title' => 'labels.delivery_partner_dashboard',
        ],
    ],

    'seller' => [
        'dashboard' => [
            'icon' => 'ti-home',
            'route' => 'seller.dashboard',
            'title' => 'labels.seller_dashboard',
            'active' => 'dashboard',
        ],
        'wallet' => [
            'icon' => 'ti-wallet',
            'title' => 'labels.wallet',
            'active' => 'wallet',
            'route' => [
                'balance' => [
                    'sub_active' => 'wallet_balance',
                    'sub_route' => 'seller.wallet.index',
                    'sub_title' => 'labels.wallet_balance',
                    'permission' => 'wallet.view'

                ],
                'withdrawals' => [
                    'sub_active' => 'withdrawals',
                    'sub_route' => 'seller.withdrawals.index',
                    'sub_title' => 'labels.withdrawals',
                    'permission' => 'withdrawal.view'
                ],
                'withdrawal_history' => [
                    'sub_active' => 'withdrawal_history',
                    'sub_route' => 'seller.withdrawals.history',
                    'sub_title' => 'labels.withdrawal_history',
                    'permission' => 'withdrawal.view'
                ],
            ],
        ],
        'earnings' => [
            'icon' => 'ti-currency-dollar',
            'route' => 'seller.commissions.index',
            'title' => 'labels.earnings',
            'active' => 'earnings',
            'permission' => 'earning.view'
        ],
        'orders' => [
            'icon' => 'ti-package',
            'route' => 'seller.orders.index',
            'title' => 'labels.seller_orders',
            'active' => 'orders',
            'permission' => 'order.view'
        ],
        'return_orders' => [
            'icon' => 'ti-truck-return',
            'title' => 'labels.return_orders',
            'active' => 'return_orders',
            'route' => [
                'return_requests' => [
                    'sub_active' => 'return_requests',
                    'sub_route' => 'seller.returns.index',
                    'sub_title' => 'labels.return_requests',
                    'permission' => 'return.view'
                ],
            ],
        ],
        'categories' => [
            'icon' => 'ti-category-2',
            'route' => 'seller.categories.index',
            'title' => 'labels.seller_categories',
            'active' => 'categories',
            'permission' => 'category.view'
        ],
        'brands' => [
            'icon' => 'ti-sparkles',
            'route' => 'seller.brands.index',
            'title' => 'labels.seller_brands',
            'active' => 'brands',
            'permission' => 'brand.view'
        ],
        'attributes' => [
            'icon' => 'ti-tag-starred',
            'route' => 'seller.attributes.index',
            'title' => 'labels.attributes',
            'active' => 'attributes',
            'permission' => 'attribute.view'
        ],
        'products' => [
            'icon' => 'ti-cube-spark',
            'title' => 'labels.manage_products',
            'active' => 'products',
            'route' => [
                'products' => [
                    'sub_active' => 'products',
                    'sub_route' => 'seller.products.index',
                    'sub_title' => 'labels.seller_products',
                    'permission' => 'product.view'

                ],
                'add_products' => [
                    'sub_active' => 'add_products',
                    'sub_route' => 'seller.products.create',
                    'sub_title' => 'labels.add_products',
                    'permission' => 'product.create'

                ],
                'bulk_upload' => [
                    'sub_active' => 'bulk_upload',
                    'sub_route' => 'seller.products.bulk-upload',
                    'sub_title' => 'labels.bulk_upload',
                    'permission' => 'product.create'

                ],
                'product_faqs' => [
                    'sub_active' => 'product_faqs',
                    'sub_route' => 'seller.product_faqs.index',
                    'sub_title' => 'labels.seller_product_faqs',
                    'permission' => 'product_faq.view'
                ],
            ],
        ],
        'subscriptions' => [
            'icon' => 'ti-user-dollar',
            'title' => 'labels.subscriptions',
            'active' => 'subscriptions',
            'route' => [
                'plans' => [
                    'sub_active' => 'plans',
                    'sub_route' => 'seller.subscription-plans.index',
                    'sub_title' => 'labels.plans',
                    'permission' => 'subscription.view',
                ],
                'current' => [
                    'sub_active' => 'current',
                    'sub_route' => 'seller.subscription-plans.current',
                    'sub_title' => 'labels.current_subscription',
                    'permission' => 'subscription.view',
                ],
                'history' => [
                    'sub_active' => 'history',
                    'sub_route' => 'seller.subscription-plans.history',
                    'sub_title' => 'labels.subscription_history',
                    'permission' => 'subscription.view',
                ],
            ],
        ],
        'tax_rates' => [
            'icon' => 'ti-square-rounded-percentage',
            'route' => 'seller.tax-rates.index',
            'title' => 'labels.seller_tax_rates',
            'active' => 'tax_rates',
            'permission' => 'tax_rate.view'
        ],
        'stores' => [
            'icon' => 'ti-building-warehouse',
            'title' => 'labels.seller_stores',
            'active' => 'stores',
            'route' => 'seller.stores.index',
            'permission' => 'store.view'
        ],
        'notifications' => [
            'icon' => 'ti-bell-ringing',
            'route' => 'seller.notifications.index',
            'title' => 'labels.seller_notifications',
            'active' => 'notifications',
            'permission' => 'notification.view'
        ],
        'roles_permissions' => [
            'icon' => 'ti-users-group',
            'title' => 'labels.seller_roles_permissions',
            'active' => 'roles_permissions',
            'route' => [
                'roles' => [
                    'sub_active' => 'roles',
                    'sub_route' => 'seller.roles.index',
                    'sub_title' => 'labels.seller_roles',
                    'permission' => 'role.view'

                ],
                'system_users' => [
                    'sub_active' => 'system_users',
                    'sub_route' => 'seller.system-users.index',
                    'sub_title' => 'labels.seller_system_users',
                    'permission' => 'system_user.view'

                ]
            ],
        ],
        'logout' => [
            'icon' => 'ti-logout-2',
            'route' => 'seller.logout',
            'title' => 'labels.seller_logout',
        ],
    ]
];
