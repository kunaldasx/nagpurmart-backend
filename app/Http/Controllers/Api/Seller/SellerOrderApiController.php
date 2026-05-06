<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\DateRangeFilterEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\Product\ProductTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Services\CurrencyService;
use App\Services\OrderService;
use App\Types\Api\ApiResponseType;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Store;

#[Group('Seller Orders')]
class SellerOrderApiController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected OrderService    $orderService,
        protected CurrencyService $currencyService
    )
    {
    }

    /**
     * List seller order items with filters and pagination
     */
    #[QueryParameter('page', description: 'Page number for pagination', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('status', description: 'Filter by order item status', type: 'string', example: 'processing')]
    #[QueryParameter('payment_type', description: 'Filter by payment method', type: 'string', example: 'cod')]
    #[QueryParameter('range', description: 'Date range filter from DateRangeFilterEnum', type: 'string', example: 'LAST_7_DAYS')]
    #[QueryParameter('search', description: 'Search by order id, buyer name, product/variant title', type: 'string', example: 'John')]
    #[QueryParameter('sort_by', description: 'Sort column: id|order_id|price|status|created_at', type: 'string', default: 'id')]
    #[QueryParameter('sort_dir', description: 'Sort direction asc|desc', type: 'string', default: 'desc')]
    #[QueryParameter('store_id', description: 'Filter items by a specific store owned by the seller', type: 'int', example: 123)]
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
        }

        $perPage = (int)$request->integer('per_page', 15);
        $searchValue = trim((string)$request->input('search', ''));
        $status = $request->input('status');
        $paymentType = $request->input('payment_type');
        $dateRange = $request->input('range');
        $sortBy = in_array($request->input('sort_by'), ['id', 'order_id', 'price', 'status', 'created_at']) ? $request->input('sort_by') : 'id';
        $sortDir = strtolower($request->input('sort_dir')) === 'asc' ? 'asc' : 'desc';

        // Optional store filter with ownership validation
        $storeId = $request->integer('store_id');
        if ($storeId) {
            $storeValid = Store::query()->where('id', $storeId)->where('seller_id', $seller->id)->exists();
            if (!$storeValid) {
                return ApiResponseType::sendJsonResponse(false, 'You are not authorized to access this store.', null, 403);
            }
        } else {
            $storeId = null;
        }

        $query = SellerOrderItem::with(['sellerOrder', 'orderItem', 'orderItem.store', 'variant', 'product', 'sellerOrder.order'])
            ->whereHas('sellerOrder', fn($q) => $q->where('seller_id', $seller->id))
            ->whereHas('orderItem', function ($q) {
                $q->where('status', '!=', OrderItemStatusEnum::PENDING());
            })
            ->when($storeId, function ($q) use ($storeId) {
                $q->whereHas('orderItem', function ($qq) use ($storeId) {
                    $qq->where('store_id', $storeId);
                });
            });

        if (!empty($status)) {
            $query->whereHas('orderItem', fn($q) => $q->where('status', $status));
        }

        if (!empty($paymentType)) {
            $query->whereHas('sellerOrder', function ($q) use ($paymentType) {
                $q->whereHas('order', fn($qo) => $qo->where('payment_method', $paymentType));
            });
        }

        if (!empty($dateRange)) {
            $fromDate = $this->getDateRange($dateRange);
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            }
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('seller_order_id', 'like', "%$searchValue%")
                    ->orWhereHas('sellerOrder', function ($orderQuery) use ($searchValue) {
                        $orderQuery->where('total_price', 'like', "%$searchValue%")
                            ->orWhereHas('order', function ($oq) use ($searchValue) {
                                $oq->where('shipping_name', 'like', "%$searchValue%")
                                    ->orWhere('order_id', 'like', "%$searchValue%");
                            });
                    })
                    ->orWhereHas('orderItem', function ($orderItemQuery) use ($searchValue) {
                        $orderItemQuery->where('status', 'like', "%$searchValue%")
                            ->orWhere('sku', 'like', "%$searchValue%");
                    })
                    ->orWhereHas('product', function ($productQuery) use ($searchValue) {
                        $productQuery->where('title', 'like', "%$searchValue%");
                    })
                    ->orWhereHas('variant', function ($variantQuery) use ($searchValue) {
                        $variantQuery->where('title', 'like', "%$searchValue%");
                    });
            });
        }

        $paginator = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        $paginator->getCollection()->transform(function ($item) {
            return $this->transformOrderItem($item);
        });

        $response = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $paginator->items(),
        ];

        return ApiResponseType::sendJsonResponse(true, __('labels.orders_fetched_successfully') ?? 'Orders fetched successfully', $response);
    }

    /**
     * Show a seller order (not a global Order). ID is seller_orders.id
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
        }

        $sellerOrder = SellerOrder::where('id', $id)
            ->with(['order', 'items.product', 'items.variant', 'items.orderItem', 'order.items.store'])
            ->where('seller_id', $seller->id)
            ->first();

        if (!$sellerOrder) {
            return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found') ?? 'Order not found', null, 404);
        }

        try {
            $this->authorize('view', $sellerOrder);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied') ?? __('messages.unauthorized_action'), [], 403);
        }

        $resource = new OrderResource($sellerOrder);
        return ApiResponseType::sendJsonResponse(true, __('labels.order_fetched_successfully') ?? 'Order fetched successfully', $resource);
    }

    /**
     * Update the status of a seller's order item.
     * $id refers to global order_items.id to align with existing web route behavior.
     */
    public function updateStatus(int $id, string $status): JsonResponse
    {
        try {
            $seller = auth()->user()->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'));
            }

            $orderItem = SellerOrderItem::where('order_item_id', $id)
                ->whereHas('sellerOrder', function ($q) use ($seller) {
                    $q->where('seller_id', $seller->id);
                })
                ->first();

            if (!$orderItem) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_found') ?? 'Order item not found', []);
            }

            $this->authorize('updateStatus', $orderItem);

            $result = $this->orderService->updateOrderStatusBySeller($id, $status, $seller->id);
            if (!$result['success']) {
                return ApiResponseType::sendJsonResponse(false, $result['message'], $result['data'] ?? []);
            }

            return ApiResponseType::sendJsonResponse(true, $result['message'], $result['data'] ?? []);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('messages.unauthorized_action'), []);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('messages.order_status_update_failed') ?? 'Order status update failed', ['error' => $e->getMessage()], 500);
        }
    }

    private function transformOrderItem(SellerOrderItem $sellerOrderItem): array
    {
        $variantTitle = (!empty($sellerOrderItem->product->type) && $sellerOrderItem->product->type === ProductTypeEnum::SIMPLE()) ? '' : ($sellerOrderItem->variant->title ?? '');
        $storeName = $sellerOrderItem->orderItem->store ? $sellerOrderItem->orderItem->store->name : 'N/A';

        return [
            'order_item_id' => $sellerOrderItem->order_item_id,
            'seller_order_id' => $sellerOrderItem->seller_order_id,
            'created_at' => $sellerOrderItem->created_at,
            'order' => [
                'id' => $sellerOrderItem->sellerOrder->order_id,
                'image' => !empty($sellerOrderItem->variant->image) ? $sellerOrderItem->variant->image : $sellerOrderItem->product?->main_image,
                'uuid' => $sellerOrderItem->sellerOrder->order->uuid ?? null,
                'buyer_name' => $sellerOrderItem->sellerOrder->order->shipping_name ?? null,
                'payment_method' => $sellerOrderItem->sellerOrder->order->payment_method ?? null,
                'is_rush_order' => (bool)($sellerOrderItem->sellerOrder->order->is_rush_order ?? false),
                'status' => $sellerOrderItem->sellerOrder->order->status ?? null,
            ],
            'product' => [
                'id' => $sellerOrderItem->product?->id,
                'title' => $sellerOrderItem->product?->title,
                'variant' => $variantTitle,
            ],
            'store' => [
                'name' => $storeName,
            ],
            'sku' => $sellerOrderItem->orderItem->sku,
            'quantity' => (int)$sellerOrderItem->orderItem->quantity,
            'subtotal' => [
                'raw' => (float)$sellerOrderItem->orderItem->subtotal,
                'formatted' => $this->currencyService->format($sellerOrderItem->orderItem->subtotal),
            ],
            'status' => $sellerOrderItem->orderItem->status,
        ];
    }

    private function getDateRange($dateRange): ?Carbon
    {
        $fromDate = null;
        $now = Carbon::now();
        switch ($dateRange) {
            case DateRangeFilterEnum::LAST_30_MINUTES():
                $fromDate = $now->copy()->subMinutes(30);
                break;
            case DateRangeFilterEnum::LAST_1_HOUR():
                $fromDate = $now->copy()->subHour();
                break;
            case DateRangeFilterEnum::LAST_5_HOURS():
                $fromDate = $now->copy()->subHours(5);
                break;
            case DateRangeFilterEnum::LAST_1_DAY():
                $fromDate = $now->copy()->subDay();
                break;
            case DateRangeFilterEnum::LAST_7_DAYS():
                $fromDate = $now->copy()->subDays(7);
                break;
            case DateRangeFilterEnum::LAST_30_DAYS():
                $fromDate = $now->copy()->subDays(30);
                break;
            case DateRangeFilterEnum::LAST_365_DAYS():
                $fromDate = $now->copy()->subDays(365);
                break;
        }
        return $fromDate;
    }

    /**
     * Get all enums for filtering orders.
     */
    public function enums(): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.order_enums_retrieved_successfully',
            data: [
                'status' => OrderItemStatusEnum::values(),
                'range' => DateRangeFilterEnum::values(),
                'payment_type' => PaymentTypeEnum::values()
            ]
        );
    }
}
