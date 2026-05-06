<?php

namespace App\Console\Commands;

use App\Enums\Subscription\SubscriptionPlanKeyEnum;
use App\Models\SellerUser;
use Illuminate\Console\Command;
use App\Models\Seller;
use App\Models\SellerSubscriptionUsage;
use App\Models\Store;
use App\Models\Product;
use App\Models\Role;

class SyncSellerSubscriptionUsage extends Command
{
    protected $signature = 'subscription:sync-usage';

    protected $description = 'Sync existing seller usage into seller_subscription_usages table';

    public function handle()
    {
        $sellers = Seller::all();

        foreach ($sellers as $seller) {

            /*
            |--------------------------------
            | Store Count
            |--------------------------------
            */

            $storeCount = Store::where('seller_id', $seller->id)->count();

            SellerSubscriptionUsage::updateOrCreate(
                [
                    'seller_id' => $seller->id,
                    'key' => SubscriptionPlanKeyEnum::STORE_LIMIT()
                ],
                [
                    'used' => $storeCount
                ]
            );


            /*
            |--------------------------------
            | Product Count
            |--------------------------------
            */

            $productCount = Product::where('seller_id', $seller->id)->count();

            SellerSubscriptionUsage::updateOrCreate(
                [
                    'seller_id' => $seller->id,
                    'key' => SubscriptionPlanKeyEnum::PRODUCT_LIMIT()
                ],
                [
                    'used' => $productCount
                ]
            );


            /*
            |--------------------------------
            | System Users
            |--------------------------------
            */

            $userCount = SellerUser::where('seller_id', $seller->id)->count();

            SellerSubscriptionUsage::updateOrCreate(
                [
                    'seller_id' => $seller->id,
                    'key' => SubscriptionPlanKeyEnum::SYSTEM_USER_LIMIT()
                ],
                [
                    'used' => $userCount
                ]
            );


            /*
            |--------------------------------
            | Roles
            |--------------------------------
            */

            $roleCount = Role::where('guard_name', 'seller')->where('team_id', $seller->id)->count();

            SellerSubscriptionUsage::updateOrCreate(
                [
                    'seller_id' => $seller->id,
                    'key' => SubscriptionPlanKeyEnum::ROLE_LIMIT()
                ],
                [
                    'used' => $roleCount
                ]
            );

        }

        $this->info('Seller usage synced successfully.');
    }
}
