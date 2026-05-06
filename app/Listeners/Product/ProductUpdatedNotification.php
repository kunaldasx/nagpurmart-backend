<?php

namespace App\Listeners\Product;

use App\Events\Product\ProductAfterUpdate;
use App\Notifications\ProductUpdated;
use App\Models\User;
use App\Enums\GuardNameEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProductUpdatedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ProductAfterUpdate $event): void
    {
        // Log the product update
        Log::info('Product updated', [
            'product_id' => $event->product->id,
            'product_title' => $event->product->title,
            'seller_id' => $event->product->seller_id,
        ]);

        // Get the seller user
        $seller = $event->product->seller ? $event->product->seller->user : null;

        // Get admin users using access_panel
        $adminUsers = User::where('access_panel', GuardNameEnum::ADMIN)->get();

        // Send notification to seller
        if ($seller && $seller->email) {
            $seller->notify(new ProductUpdated($event, 'seller'));
        } else {
            Log::warning('Could not send notification to seller - missing email', [
                'seller_id' => $event->product->seller_id,
                'seller_user_id' => $seller ? $seller->id : null
            ]);
        }

        // Send notification to admin users
        foreach ($adminUsers as $admin) {
            if ($admin->email) {
                $admin->notify(new ProductUpdated($event, 'admin'));
            }
        }
    }
}
