<?php

namespace App\Http\Controllers\Api\DeliveryBoy;

use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Events\Order\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryBoy\UpdateOrderItemStatusRequest;
use App\Http\Resources\DeliveryBoy\DeliveryBoyOrderResource;
use App\Models\DeliveryBoyAssignment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\DeliveryZoneService;
use App\Services\OrderService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Group('DeliveryBoy Orders')]
class DeliveryBoyOrderApiController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Get orders available for the delivery boy
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableOrders(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $deliveryBoy = $user->deliveryBoy;

            // Get page size from request or use default
            $perPage = $request->input('per_page', 10);

            // Get orders that are in the delivery boy's zone and are available for delivery
            $orders = Order::where('delivery_zone_id', $deliveryBoy->delivery_zone_id)
                ->whereNull('delivery_boy_id')
                ->where('status', OrderStatusEnum::READY_FOR_PICKUP())
                ->withDeliveryBoyEarnings() // Use the scope to load relationships for earnings calculation
                ->with([
                    'items' => function ($query) {
                        $query->whereNot('status', OrderItemStatusEnum::REJECTED());
                    }
                ])->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Calculate the delivery route for each order
            foreach ($orders->items() as $order) {
                // Get store IDs from order items
                $storeIds = $order->items->pluck('store_id')->unique()->toArray();

                // Calculate delivery route
                $deliveryRoute = DeliveryZoneService::calculateDeliveryRoute(
                    $order->shipping_latitude,
                    $order->shipping_longitude,
                    $storeIds,
                    $order
                );

                // Add delivery route to order
                $order->delivery_route = $deliveryRoute;
            }

            // Create a resource collection
            $resourceCollection = DeliveryBoyOrderResource::collection($orders);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.orders_fetched_successfully'),
                data: [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                    'orders' => $resourceCollection,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Accept an order
     *
     * @param Request $request
     * @param string $orderId
     * @return JsonResponse
     */
    public function acceptOrder(Request $request, string $orderId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $deliveryBoy = $user->deliveryBoy;

            // Find the order
            $order = Order::where('id', $orderId)
                ->where('delivery_zone_id', $deliveryBoy->delivery_zone_id)
                ->whereNull('delivery_boy_id')
                ->where('status', OrderStatusEnum::READY_FOR_PICKUP())
                ->with([
                    'items' => function ($query) {
                        $query->whereNot('status', OrderItemStatusEnum::REJECTED());
                    }
                    , 'sellerOrders','sellerOrders.seller.user', 'user'])
                ->first();

            if (!$order) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.order_not_found_or_not_available'),
                    data: []
                );
            }
            // Update the order with the delivery boy's ID and change status to ASSIGNED
            $order->update([
                'delivery_boy_id' => $deliveryBoy->id,
                'status' => OrderStatusEnum::ASSIGNED()
            ]);

            // Load the order with relationships for earnings calculation
            $order->load('deliveryZone');

            // Calculate delivery route
            $storeIds = $order->items->pluck('store_id')->unique()->toArray();
            $deliveryRoute = DeliveryZoneService::calculateDeliveryRoute(
                $order->shipping_latitude,
                $order->shipping_longitude,
                $storeIds,
                $order
            );

            // Add delivery route to order
            $order->delivery_route = $deliveryRoute;

            // Calculate earnings
            $earnings = $order->delivery_boy_earnings;

            // Create a new entry in the delivery_boy_assignments table with earnings
            DeliveryBoyAssignment::create([
                'order_id' => $order->id,
                'delivery_boy_id' => $deliveryBoy->id,
                'assigned_at' => now(),
                'status' => DeliveryBoyAssignmentStatusEnum::ASSIGNED(),
                'base_fee' => $earnings['breakdown']['base_fee'] ?? 0,
                'per_store_pickup_fee' => $earnings['breakdown']['per_store_pickup_fee'] ?? 0,
                'distance_based_fee' => $earnings['breakdown']['distance_based_fee'] ?? 0,
                'per_order_incentive' => $earnings['breakdown']['per_order_incentive'] ?? 0,
                'total_earnings' => $earnings['total'] ?? 0
            ]);
            $orderItem = $order->items->first();
            if (
                !$orderItem ||
                !$orderItem->store ||
                !$orderItem->store->seller
            ) {
                DB::rollBack();

                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.seller_not_found'),
                    data: []
                );
            }
            $orderItem->sellerOrder = $order->sellerOrders;
            $orderItem->order->user = $order->user;
            $orderItem->store->seller->user = $order->user;
            event(new OrderStatusUpdated(
                orderItem: $orderItem,
                oldStatus: $order->status,
                newStatus: DeliveryBoyAssignmentStatusEnum::ASSIGNED()
            ));
            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.order_accepted_successfully'),
                data: [
                    'order' => new DeliveryBoyOrderResource($order)
                ]
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_error') . ": " . $e->getMessage(),
                data: []
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get Delivery Boy's Orders
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyOrders(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $deliveryBoy = $user->deliveryBoy;

            // Get page size from request or use default
            $perPage = $request->input('per_page', 10);

            // Get status filter from request if provided
            $status = $request->input('status');

            // Validate status if provided
            if ($status && !in_array($status, DeliveryBoyAssignmentStatusEnum::values())) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.invalid_status_provided'),
                    data: ['valid_statuses' => DeliveryBoyAssignmentStatusEnum::values()]
                );
            }

            // Get orders assigned to the delivery boy using the delivery_boy_assignments pivot table
            $orders = Order::whereHas('deliveryBoyAssignments', function ($query) use ($deliveryBoy, $status) {
                $query->where('delivery_boy_id', $deliveryBoy->id);

                if ($status) {
                    $query->where('status', $status);
                }
            })
                ->withDeliveryBoyEarnings()
                ->with([
                    'deliveryBoyAssignments' => function ($query) use ($deliveryBoy) {
                        $query->where('delivery_boy_id', $deliveryBoy->id);
                    },
                    'items' => function ($query) {
                        $query->whereNot('status', OrderItemStatusEnum::REJECTED());
                    }
                ])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
            // Calculate delivery route for each order
            foreach ($orders->items() as $order) {
                // Get store IDs from order items
                $storeIds = $order->items->pluck('store_id')->unique()->toArray();

                // Calculate delivery route
                $deliveryRoute = DeliveryZoneService::calculateDeliveryRoute(
                    $order->shipping_latitude,
                    $order->shipping_longitude,
                    $storeIds,
                    $order
                );

                // Add delivery route to order
                $order->delivery_route = $deliveryRoute;
            }

            // Create a resource collection
            $resourceCollection = DeliveryBoyOrderResource::collection($orders);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.orders_fetched_successfully'),
                data: [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                    'filters' => [
                        'status' => $status ?: 'all',
                        'available_statuses' => DeliveryBoyAssignmentStatusEnum::values()
                    ],
                    'orders' => $resourceCollection,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get order details
     *
     * @param Request $request
     * @param int $orderId
     * @return JsonResponse
     */
    public function getOrderDetails(Request $request, int $orderId): JsonResponse
    {
        try {
            $user = $request->user();
            $deliveryBoy = $user->deliveryBoy;

            // Find the order
            $order = Order::where('id', $orderId)
                ->where('delivery_boy_id', $deliveryBoy->id)
                ->withDeliveryBoyEarnings()
                ->with(['deliveryBoyAssignments' => function ($query) use ($deliveryBoy) {
                    $query->where('delivery_boy_id', $deliveryBoy->id);
                }, 'items' => function ($query) {
                    $query->whereNot('status', OrderItemStatusEnum::REJECTED());
                }])->orderBy('created_at', 'desc')
                ->first();

            if (!$order) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.order_not_found_or_not_available'),
                    data: []
                );
            }

            // Calculate delivery route
            $storeIds = $order->items->pluck('store_id')->unique()->toArray();
            $deliveryRoute = DeliveryZoneService::calculateDeliveryRoute(
                $order->shipping_latitude,
                $order->shipping_longitude,
                $storeIds,
                $order
            );

            // Add delivery route to order
            $order->delivery_route = $deliveryRoute;

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.order_fetched_successfully'),
                data: [
                    'order' => new DeliveryBoyOrderResource($order)
                ]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Update order item status
     *
     * @param UpdateOrderItemStatusRequest $request
     * @param int $orderItemId
     * @return JsonResponse
     */
    public function updateOrderItemStatus(UpdateOrderItemStatusRequest $request, int $orderItemId): JsonResponse
    {
        try {

            // If status is 'delivered', check if OTP is required
            if ($request->input('status') === OrderItemStatusEnum::DELIVERED()) {
                // Find the order item to check if it requires OTP
                $orderItem = OrderItem::with('product')->findOrFail($orderItemId);

                // If the product requires OTP, validate the OTP
                if ($orderItem->product?->requires_otp && !$request->filled('otp')) {
                    throw ValidationException::withMessages([
                        'otp' => [__('validation.otp_required')],
                    ]);
                }
            }

            $user = $request->user();
            $deliveryBoy = $user->deliveryBoy;
            $status = $request->input('status');
            $otp = $request->input('otp');

            // Update the order item status
            $result = $this->orderService->updateOrderItemStatusByDeliveryBoy(
                $orderItemId,
                $status,
                $deliveryBoy->id,
                $otp ?? null
            );

            if (!$result['success']) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: $result['message'],
                    data: $result['data']
                );
            }

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: $result['message'],
                data: $result['data']
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_error') . ":- " . $e->getMessage(),
                data: ['errors' => $e->errors()]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong') . ":- " . $e->getMessage(),
                data: ['error' => $e->getMessage()]
            );
        }
    }
}
