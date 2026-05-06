<?php

namespace App\Services;

use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyAssignmentTypeEnum;
use App\Enums\Order\OrderItemReturnPickupStatusEnum;
use App\Enums\Order\OrderItemReturnStatusEnum;
use App\Events\Order\OrderStatusUpdated;
use App\Enums\Seller\SellerSettlementTypeEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Models\DeliveryBoy;
use App\Models\DeliveryBoyAssignment;
use App\Models\OrderItemReturn;
use App\Models\SellerStatement;
use Illuminate\Support\Facades\Log;

class ReturnPickupService
{
    protected OrderService $orderService;
    public function __construct(protected SellerStatementService $sellerStatementService, OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    /**
     * Compute delivery route and earnings for a return pickup for the given delivery boy
     * Returns array: ['route' => array, 'earnings' => array, 'zone' => array|null]
     */
    public function computeRouteAndEarnings(OrderItemReturn $orderReturn, DeliveryBoy $deliveryBoy): array
    {
        $order = $orderReturn->order; // expect loaded
        $storeId = $orderReturn->store_id ? [$orderReturn->store_id] : [];

        $route = DeliveryZoneService::calculateDeliveryRoute(
            (float) $order->shipping_latitude,
            (float) $order->shipping_longitude,
            $storeId,
            $order
        );

        $deliveryBoy->loadMissing('deliveryZone');
        $zone = $deliveryBoy->deliveryZone;

        $baseFee = $zone->delivery_boy_base_fee ?? 0;
        $perStorePickupFee = ($zone->delivery_boy_per_store_pickup_fee ?? 0) * 1; // single store for return pickup
        $distanceBasedFee = ($zone->delivery_boy_distance_based_fee ?? 0) * ($route['total_distance'] ?? 0);
        $perOrderIncentive = $zone->delivery_boy_per_order_incentive ?? 0;
        $total = round($baseFee + $perStorePickupFee + $distanceBasedFee + $perOrderIncentive, 2);

        return [
            'route' => $route,
            'earnings' => [
                'total' => $total,
                'breakdown' => [
                    'base_fee' => round($baseFee, 2),
                    'per_store_pickup_fee' => round($perStorePickupFee, 2),
                    'distance_based_fee' => round($distanceBasedFee, 2),
                    'per_order_incentive' => round($perOrderIncentive, 2),
                ],
            ],
            'zone' => $zone ? [
                'id' => $zone->id,
                'name' => $zone->name,
            ] : null,
        ];
    }

    /**
     * Attach computed values onto the model instance (transient, not saved)
     */
    public function attachComputedFields(OrderItemReturn $orderReturn, array $computed): OrderItemReturn
    {
        $orderReturn->delivery_route = $computed['route'] ?? null;
        $orderReturn->earnings = $computed['earnings'] ?? null;
        $orderReturn->delivery_zone = $computed['zone'] ?? null;
        return $orderReturn;
    }

    /**
     * Accept a return pickup: assign delivery boy, set pickup status, create assignment and attach computed fields
     */
    public function acceptPickup(OrderItemReturn $orderReturn, DeliveryBoy $deliveryBoy): array
    {
        // assign and mark as assigned
        $orderReturn->update([
            'delivery_boy_id' => $deliveryBoy->id,
            'pickup_status' => OrderItemReturnPickupStatusEnum::ASSIGNED(),
        ]);

        // compute route + earnings
        $computed = $this->computeRouteAndEarnings($orderReturn, $deliveryBoy);

        // persist earnings snapshot in assignment
        DeliveryBoyAssignment::create([
            'delivery_boy_id' => $deliveryBoy->id,
            'order_id' => $orderReturn->order_id,
            'order_item_id' => $orderReturn->order_item_id,
            'return_id' => $orderReturn->id,
            'assignment_type' => DeliveryBoyAssignmentTypeEnum::RETURN_PICKUP(),
            'assigned_at' => now(),
            'status' => DeliveryBoyAssignmentStatusEnum::ASSIGNED(),
            'base_fee' => $computed['earnings']['breakdown']['base_fee'] ?? 0,
            'per_store_pickup_fee' => $computed['earnings']['breakdown']['per_store_pickup_fee'] ?? 0,
            'distance_based_fee' => $computed['earnings']['breakdown']['distance_based_fee'] ?? 0,
            'per_order_incentive' => $computed['earnings']['breakdown']['per_order_incentive'] ?? 0,
            'total_earnings' => $computed['earnings']['total'] ?? 0,
        ]);

        // refresh with relations and attach computed
        $orderReturn = $orderReturn->fresh(['order','orderItem.product','orderItem.variant','store','user']);
        $this->attachComputedFields($orderReturn, $computed);

        return [
            'success' => true,
            'pickup' => $orderReturn,
        ];
    }

    /**
     * Validate status transition
     */
    public function isValidTransition(string $current, string $requested): bool
    {
        if ($requested === OrderItemReturnPickupStatusEnum::PICKED_UP()) {
            return in_array($current, [OrderItemReturnPickupStatusEnum::ASSIGNED()], true);
        }
        if ($requested === OrderItemReturnPickupStatusEnum::DELIVERED_TO_SELLER()) {
            return in_array($current, [OrderItemReturnPickupStatusEnum::PICKED_UP()], true);
        }
        return false;
    }

    /**
     * Update pickup status and related assignment; attach computed fields for response
     */
    public function updatePickupStatus(OrderItemReturn $orderReturn, string $newStatus, DeliveryBoy $deliveryBoy): array
    {
        $current = $orderReturn->pickup_status;
        if (!$this->isValidTransition($current, $newStatus)) {
            return [
                'success' => false,
                'error' => 'invalid_transition',
                'current' => $current,
                'requested' => $newStatus,
            ];
        }

        $update = ['pickup_status' => $newStatus ];
        if ($newStatus === OrderItemReturnPickupStatusEnum::PICKED_UP()) {
            $update['picked_up_at'] = now();
            $update['return_status'] = OrderItemReturnStatusEnum::PICKED_UP();
        }
        if ($newStatus === OrderItemReturnPickupStatusEnum::DELIVERED_TO_SELLER()) {
            // Process refund to customer wallet and finalize statuses
            try {
                $orderReturn->loadMissing('orderItem.order');
                $orderItem = $orderReturn->orderItem;

                if ($orderItem) {
                    $refundResult = $this->orderService->processOrderItemRefund(orderItem: $orderItem, type: 'return_pickup');

                    if (!($refundResult['success'] ?? false)) {
                        // If refund fails, mark as received by seller but do not complete
                        $update['received_at'] = now();
                        $update['return_status'] = OrderItemReturnStatusEnum::RECEIVED_BY_SELLER();
                        Log::warning('Return refund failed on delivery to seller', [
                            'return_id' => $orderReturn->id,
                            'order_item_id' => $orderItem->id,
                            'error' => $refundResult['message'] ?? 'unknown',
                        ]);
                    } else {

                        // Refund succeeded: mark return completed and order item refunded
                        $update['received_at'] = now();
                        $update['return_status'] = OrderItemReturnStatusEnum::COMPLETED();
                        $oldStatus = $orderItem->status;
                        $orderItem->update(['status' => OrderItemStatusEnum::REFUNDED()]);
                        // Notify listeners (e.g., stock restock on return)
                        event(new OrderStatusUpdated(
                            orderItem: $orderItem,
                            oldStatus: $oldStatus,
                            newStatus: OrderItemStatusEnum::REFUNDED()
                        ));
                    }
                } else {
                    // Fallback if order item missing
                    $update['received_at'] = now();
                    $update['return_status'] = OrderItemReturnStatusEnum::RECEIVED_BY_SELLER();
                    Log::warning('Order item missing while processing return delivery to seller', [
                        'return_id' => $orderReturn->id,
                    ]);
                }
            } catch (\Throwable $e) {
                $update['received_at'] = now();
                $update['return_status'] = OrderItemReturnStatusEnum::RECEIVED_BY_SELLER();
                Log::error('Exception during return refund processing', [
                    'return_id' => $orderReturn->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        $orderReturn->update($update);

        // Update assignment status
        $assignment = DeliveryBoyAssignment::where('delivery_boy_id', $deliveryBoy->id)
            ->where('return_id', $orderReturn->id)
            ->where('assignment_type', DeliveryBoyAssignmentTypeEnum::RETURN_PICKUP())
            ->latest('id')
            ->first();
        if ($assignment) {
            if ($newStatus === OrderItemReturnPickupStatusEnum::PICKED_UP()) {
                $assignment->update(['status' => DeliveryBoyAssignmentStatusEnum::IN_PROGRESS()]);
            } elseif ($newStatus === OrderItemReturnPickupStatusEnum::DELIVERED_TO_SELLER()) {
                $assignment->update(['status' => DeliveryBoyAssignmentStatusEnum::COMPLETED()]);

                // Create seller settlement debit for refund on return delivered to seller
                try {
                    $exists = SellerStatement::where('return_id', $orderReturn->id)
                        ->where('entry_type', SellerSettlementTypeEnum::DEBIT())
                        ->where('reference_type', 'order_item_return')
                        ->exists();
                    if (!$exists) {
                        $this->sellerStatementService->recordReturnRefund(
                            $orderReturn,
                            amount: (float) ($orderReturn->refund_amount ?? 0),
                            isCredit: false,
                            currency: $orderReturn->order?->currency_code
                        );
                    }
                } catch (\Throwable $e) {
                    // Do not block status update if posting fails; log for later reconciliation
                    Log::warning('Failed to create seller refund debit on return delivered', [
                        'return_id' => $orderReturn->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // compute and attach for response
        $computed = $this->computeRouteAndEarnings($orderReturn->loadMissing('order'), $deliveryBoy);
        $orderReturn = $orderReturn->fresh(['order','orderItem.product','orderItem.variant','store','user']);
        $this->attachComputedFields($orderReturn, $computed);

        return [
            'success' => true,
            'pickup' => $orderReturn,
        ];
    }
}
