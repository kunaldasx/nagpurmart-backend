<?php

namespace App\Observers;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Models\DeliveryBoy;
use App\Models\Order;
use App\Notifications\Delivery\OrderReadyForPickupNotification;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        try {
            if ($order->wasChanged('status') && $order->status === OrderStatusEnum::READY_FOR_PICKUP()) {
                // Find eligible delivery boys: verified, available (status = 1), same delivery zone
                $zoneId = $order->delivery_zone_id;
                if (!$zoneId) {
                    return; // Cannot target by zone
                }

                $deliveryBoys = DeliveryBoy::where('delivery_zone_id', $zoneId)
                    ->where('status', true)
                    ->where('verification_status', DeliveryBoyVerificationStatusEnum::VERIFIED())
                    ->with('user')
                    ->get();

                foreach ($deliveryBoys as $db) {
                    $user = $db->user; // Notify user entity for FCM + mail + database
                    Log::debug('OrderObserver READY_FOR_PICKUP notification sent to user: ' . $user->id);
                    if ($user) {
                        // Eager-load items to enrich notification payload image
                        $order->loadMissing('items.product');
                        $user->notify(new OrderReadyForPickupNotification($order));
                    }
                }
            }
        } catch (\Throwable $e) {

            Log::warning('OrderObserver READY_FOR_PICKUP notification failed: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
                'exception' => $e,
                'stack_trace' => $e->getTraceAsString()
            ]);
        }
    }
}
