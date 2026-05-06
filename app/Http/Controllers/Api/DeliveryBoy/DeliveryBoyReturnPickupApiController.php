<?php

namespace App\Http\Controllers\Api\DeliveryBoy;

use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyAssignmentTypeEnum;
use App\Enums\Order\OrderItemReturnPickupStatusEnum;
use App\Enums\Order\OrderItemReturnStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryBoy\UpdateReturnPickupStatusRequest;
use App\Http\Resources\DeliveryBoy\DeliveryBoyReturnPickupResource;
use App\Models\DeliveryBoyAssignment;
use App\Models\DeliveryZone;
use App\Models\OrderItemReturn;
use App\Services\DeliveryZoneService;
use App\Services\ReturnPickupService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('DeliveryBoy Return Pickups')]
class DeliveryBoyReturnPickupApiController extends Controller
{
    protected ReturnPickupService $returnPickupService;

    public function __construct(ReturnPickupService $returnPickupService)
    {
        $this->returnPickupService = $returnPickupService;
    }

    /**
     * List available return pickup requests for the courier
     */
    public function getAvailablePickups(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $deliveryBoy = $user->deliveryBoy;
            $perPage = (int) $request->input('per_page', 10);

            $returns = OrderItemReturn::with([
                'order', 'orderItem.product', 'orderItem.variant', 'store', 'user'
            ])
                ->whereNull('delivery_boy_id')
                ->where('pickup_status', OrderItemReturnPickupStatusEnum::PENDING())
                ->where('return_status', OrderItemReturnStatusEnum::SELLER_APPROVED())
                ->whereHas('store.zones', function ($q) use ($deliveryBoy) {
                    $q->where('delivery_zones.id', $deliveryBoy->delivery_zone_id);
                })
                ->orderByDesc('created_at')
                ->paginate($perPage);

            // attach route, earnings and zone info
            foreach ($returns->items() as $ret) {
                $computed = $this->returnPickupService->computeRouteAndEarnings($ret->loadMissing('order'), $deliveryBoy);
                $this->returnPickupService->attachComputedFields($ret, $computed);
            }

            $collection = DeliveryBoyReturnPickupResource::collection($returns);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.orders_fetched_successfully'),
                data: [
                    'current_page' => $returns->currentPage(),
                    'last_page' => $returns->lastPage(),
                    'per_page' => $returns->perPage(),
                    'total' => $returns->total(),
                    'from' => $returns->firstItem(),
                    'to' => $returns->lastItem(),
                    'pickups' => $collection,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Accept a return pickup
     */
    public function acceptPickup(Request $request, string $returnId): JsonResponse
    {
        try {
            DB::beginTransaction();
            $user = $request->user();
            $deliveryBoy = $user->deliveryBoy;

            $orderReturn = OrderItemReturn::with(['order', 'orderItem', 'store', 'user'])
                ->where('id', $returnId)
                ->whereNull('delivery_boy_id')
                ->where('pickup_status', OrderItemReturnPickupStatusEnum::PENDING())
                ->where('return_status', OrderItemReturnStatusEnum::SELLER_APPROVED())
                ->whereHas('store.zones', function ($q) use ($deliveryBoy) {
                    $q->where('delivery_zones.id', $deliveryBoy->delivery_zone_id);
                })
                ->first();

            if (!$orderReturn) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found_or_not_available'), []);
            }

            // use service to accept pickup and build response payload
            $result = $this->returnPickupService->acceptPickup($orderReturn, $deliveryBoy);

            DB::commit();

            return ApiResponseType::sendJsonResponse(true, __('labels.order_accepted_successfully'), [
                'pickup' => new DeliveryBoyReturnPickupResource($result['pickup'])
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), ['error' => $e->getMessage()]);
        }
    }

    /**
     * List my pickups
     */
    public function getMyPickups(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $deliveryBoy = $user->deliveryBoy;
            $perPage = (int) $request->input('per_page', 10);

            $returns = OrderItemReturn::with(['order', 'orderItem.product', 'orderItem.variant', 'store', 'user'])
                ->where('delivery_boy_id', $deliveryBoy->id)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            // attach route, earnings and zone info for my pickups
            foreach ($returns->items() as $ret) {
                $computed = $this->returnPickupService->computeRouteAndEarnings($ret->loadMissing('order'), $deliveryBoy);
                $this->returnPickupService->attachComputedFields($ret, $computed);
            }

            $collection = DeliveryBoyReturnPickupResource::collection($returns);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.orders_fetched_successfully'),
                data: [
                    'current_page' => $returns->currentPage(),
                    'last_page' => $returns->lastPage(),
                    'per_page' => $returns->perPage(),
                    'total' => $returns->total(),
                    'from' => $returns->firstItem(),
                    'to' => $returns->lastItem(),
                    'pickups' => $collection,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Pickup details
     */
    public function getPickupDetails(Request $request, string $returnId): JsonResponse
    {
        try {
            $user = $request->user();
            $deliveryBoy = $user->deliveryBoy;

            $orderReturn = OrderItemReturn::with(['order', 'orderItem.product', 'orderItem.variant', 'store', 'user'])
                ->where('id', $returnId)
                ->where(function ($q) use ($deliveryBoy) {
                    $q->whereNull('delivery_boy_id')
                      ->orWhere('delivery_boy_id', $deliveryBoy->id);
                })
                ->first();

            if (!$orderReturn) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found_or_not_available'), []);
            }

            // compute and attach route/earnings/zone for details response via service
            $computed = $this->returnPickupService->computeRouteAndEarnings($orderReturn->loadMissing('order'), $deliveryBoy);
            $this->returnPickupService->attachComputedFields($orderReturn, $computed);

            return ApiResponseType::sendJsonResponse(true, __('labels.orders_fetched_successfully'), [
                'pickup' => new DeliveryBoyReturnPickupResource($orderReturn)
            ]);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update return pickup delivery status (picked_up | delivered_to_seller)
     */
    public function updatePickupStatus(UpdateReturnPickupStatusRequest $request, string $returnId): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $user = $request->user();
            $deliveryBoy = $user->deliveryBoy;

            // Load the return assigned to this courier
            $orderReturn = OrderItemReturn::with(['order', 'orderItem.product', 'orderItem.variant', 'store', 'user'])
                ->where('id', $returnId)
                ->where('delivery_boy_id', $deliveryBoy->id)
                ->first();

            if (!$orderReturn) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found_or_not_available'), []);
            }

            $newStatus = $request->input('status');

            // Delegate to service for transition and enrichment
            $result = $this->returnPickupService->updatePickupStatus($orderReturn, $newStatus, $deliveryBoy);

            if (!$result['success']) {
                DB::rollBack();
                return ApiResponseType::sendJsonResponse(false, __('labels.invalid_status_transition'), [
                    'current' => $result['current'] ?? null,
                    'requested' => $result['requested'] ?? $newStatus,
                ]);
            }

            DB::commit();

            return ApiResponseType::sendJsonResponse(true, __('labels.status_updated_successfully'), [
                'pickup' => new DeliveryBoyReturnPickupResource($result['pickup'])
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, __('labels.something_went_wrong'), ['error' => $e->getMessage()]);
        }
    }
}
