<?php

namespace App\Observers;

use App\Enums\GuardNameEnum;
use App\Enums\Order\OrderItemReturnPickupStatusEnum;
use App\Enums\Order\OrderItemReturnStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Models\DeliveryBoy;
use App\Models\OrderItemReturn;
use App\Models\User;
use App\Notifications\Delivery\ReturnOrderAvailableNotification;
use App\Notifications\OrderItemReturnRequested;
use App\Notifications\OrderItemReturnUpdated;
use Illuminate\Support\Facades\Log;

class OrderItemReturnObserver
{
    /**
     * Handle the OrderItemReturn "created" event.
     * Notify customer, seller, and admins about a new return request.
     */
    public function created(OrderItemReturn $orderReturn): void
    {
        try {
            // Notify customer (request creator)
            if ($orderReturn->user instanceof User) {
                $orderReturn->user->notify(new OrderItemReturnRequested($orderReturn, 'customer'));
            }

            // Notify seller (owner user)
            $sellerUser = $orderReturn->seller?->user;
            if ($sellerUser instanceof User) {
                $sellerUser->notify(new OrderItemReturnRequested($orderReturn, 'seller'));
            }

            // Notify admins
            $this->notifyAdmins(new OrderItemReturnRequested($orderReturn, 'admin'));
        } catch (\Throwable $e) {
            Log::error('OrderItemReturnObserver created notify failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    /**
     * Handle the OrderItemReturn "updated" event.
     * Notify all parties when statuses change meaningfully.
     */
    public function updated(OrderItemReturn $orderReturn): void
    {
        try {
            $interesting = $orderReturn->wasChanged(['return_status', 'pickup_status', 'refund_processed_at', 'seller_approved_at', 'picked_up_at', 'received_at']);
            if (!$interesting) {
                return;
            }

            // If seller has approved the return request, notify available delivery boys in the same zone
            if ($orderReturn->wasChanged('return_status') && (string)$orderReturn->return_status === OrderItemReturnStatusEnum::SELLER_APPROVED()) {
                try {
                    // Ensure relationships for payloads
                    $orderReturn->loadMissing(['order', 'orderItem.product']);
                    $zoneId = $orderReturn->order?->delivery_zone_id;
                    if ($zoneId) {
                        $deliveryBoys = DeliveryBoy::where('delivery_zone_id', $zoneId)
                            ->where('status', true)
                            ->where('verification_status', DeliveryBoyVerificationStatusEnum::VERIFIED())
                            ->with('user')
                            ->get();

                        foreach ($deliveryBoys as $db) {
                            $user = $db->user;
                            if ($user) {
                                $user->notify(new ReturnOrderAvailableNotification($orderReturn));
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed notifying delivery boys for SELLER_APPROVED return: ' . $e->getMessage());
                }
            }

            $changes = [
                'return_status' => [
                    'old' => $orderReturn->getOriginal('return_status'),
                    'new' => (string)$orderReturn->return_status,
                ],
                'pickup_status' => [
                    'old' => $orderReturn->getOriginal('pickup_status'),
                    'new' => (string)$orderReturn->pickup_status,
                ],
            ];

            if ($orderReturn->user instanceof User) {
                $orderReturn->user->notify(new OrderItemReturnUpdated($orderReturn, $changes, 'customer'));
            }

            $sellerUser = $orderReturn->seller?->user;
            if ($sellerUser instanceof User) {
                $sellerUser->notify(new OrderItemReturnUpdated($orderReturn, $changes, 'seller'));
            }

            $this->notifyAdmins(new OrderItemReturnUpdated($orderReturn, $changes, 'admin'));
        } catch (\Throwable $e) {
            Log::error('OrderItemReturnObserver updated notify failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    protected function notifyAdmins($notification): void
    {
        try {
            $admins = User::where('access_panel', GuardNameEnum::ADMIN())->get();
            foreach ($admins as $admin) {
                try {
                    $admin->notify($notification);
                } catch (\Throwable $e) {
                    Log::warning('Failed notifying admin about order item return: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OrderItemReturnObserver failed to fetch admins: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}
