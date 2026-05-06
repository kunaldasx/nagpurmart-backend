<?php

namespace App\Services;

use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Http\Resources\DeliveryBoy\DeliveryBoyLocationResource;
use App\Http\Resources\DeliveryFeedbackResource;
use App\Models\DeliveryBoyAssignment;
use App\Models\DeliveryBoyLocation;
use App\Models\DeliveryFeedback;

class DeliveryBoyService
{
    public static function validatePendingOrders($delivery_boy_id): array
    {
        $pendingOrders = DeliveryBoyAssignment::where('delivery_boy_id', $delivery_boy_id)->whereNotIn('status', [DeliveryBoyAssignmentStatusEnum::COMPLETED(), DeliveryBoyAssignmentStatusEnum::CANCELED()])->get();
        if ($pendingOrders->isNotEmpty()) {
            return [
                'success' => false,
                'message' => __('labels.pending_orders_exist'),
                'data' => $pendingOrders
            ];
        }
        return [
            'success' => true,
            'message' => __('labels.no_pending_orders_exist'),
            'data' => []
        ];
    }

    public static function getLastLocation($delivery_boy_id): array
    {
        $data = DeliveryBoyLocation::with('deliveryBoy')->where('delivery_boy_id', $delivery_boy_id)->get()->first();
        if ($data != null) {
            return [
                'success' => true,
                'message' => __('labels.last_location_retrieved_successfully'),
                'data' => new DeliveryBoyLocationResource($data)
            ];
        }
        return [
            'success' => false,
            'message' => __('labels.no_location_found'),
            'data' => []
        ];
    }

    public static function checkDeliveryBoyFeedbackByOrderId($orderId, $deliveryBoyId): bool
    {
        return DeliveryFeedback::where(['order_id' => $orderId, 'delivery_boy_id' => $deliveryBoyId])->exists();
    }

    public static function getDeliveryBoyFeedbackByOrderId($orderId, $deliveryBoyId): DeliveryFeedbackResource|null
    {
        $feedback =  DeliveryFeedback::where(['order_id' => $orderId, 'delivery_boy_id' => $deliveryBoyId])->get()->first();
        if ($feedback) {
            return new DeliveryFeedbackResource($feedback);
        }
        return null;

    }
}
