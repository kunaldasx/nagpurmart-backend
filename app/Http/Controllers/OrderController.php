<?php

namespace App\Http\Controllers;

use App\Enums\DateRangeFilterEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Product\ProductTypeEnum;
use App\Enums\SellerPermissionEnum;
use App\Http\Resources\OrderResource;
use App\Enums\SpatieMediaCollectionName;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Services\CurrencyService;
use App\Services\DeliveryBoyOrderOfferService;
use App\Services\OrderService;
use App\Services\SellerOrderPopupOfferService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    use PanelAware, AuthorizesRequests, ChecksPermissions;

    public bool $editPermission = false;
    protected OrderService $orderService;
    protected CurrencyService $currencyService;

    public function __construct(OrderService $orderService, CurrencyService $currencyService)
    {
        $this->orderService = $orderService;
        $this->currencyService = $currencyService;
        $user = auth()->user();
        if ($user) {
            $this->editPermission = $this->hasPermission(SellerPermissionEnum::ORDER_EDIT()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
        }
    }

    /**
     * Display a listing of the seller's orders.
     *
     * @return View
     */
    public function index(): View
    {
        $this->authorize('viewAny', SellerOrder::class);

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'order_date', 'name' => 'order_date', 'title' => __('labels.order_date'), 'orderable' => false, 'searchable' => false],
            ['data' => 'order_details', 'name' => 'order_details', 'title' => __('labels.order_details'), 'orderable' => false, 'searchable' => false],
            ['data' => 'product_details', 'name' => 'product_details', 'title' => __('labels.product_details'), 'orderable' => false, 'searchable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'actions', 'name' => 'actions', 'title' => __('labels.actions'), 'orderable' => false, 'searchable' => false],
        ];
        return view($this->panelView('orders.index'), compact('columns'));
    }

    public function getRiderAvailability(): JsonResponse
    {
        $this->authorize('viewAny', SellerOrder::class);
        $seller = auth()->user()?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), [], 404);
        }

        return ApiResponseType::sendJsonResponse(
            true,
            'Rider availability fetched successfully.',
            app(DeliveryBoyOrderOfferService::class)->riderAvailabilityForSeller((int) $seller->id)
        );
    }

    /**
     * Get orders datatable data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrders(Request $request): JsonResponse
    {
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';
        $status = $request->get('status');
        $paymentType = $request->get('payment_type');
        $dateRange = $request->get('range');
        $orderMode = $request->get('order_mode');

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';

        $columns = ['id', 'order_id', 'price', 'status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = SellerOrderItem::with(['sellerOrder.bag', 'sellerOrder.order', 'orderItem', 'orderItem.store', 'variant', 'product'])
            ->whereHas('product', function ($q) {
                $q->whereNotNull('id');
            });

        if ($this->getPanel() === 'seller') {
            $user = auth()->user();
            $seller = $user?->seller();

            if (!$seller) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.seller_not_found'),
                    data: []
                );
            }

            $query->whereHas('sellerOrder', function ($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            });
            $query->whereHas('orderItem', function ($q) {
                $q->where('status', '!=', OrderItemStatusEnum::PENDING());
            });
        }

        if (in_array($orderMode, ['regular', 'wholesale'], true)) {
            $query->whereHas('sellerOrder.order', function ($q) use ($orderMode) {
                $q->where('order_mode', $orderMode);
            });
        }
        $totalRecords = $query->count();

        // Filter by status if provided
        if ($status !== null && $status !== '') {
            $query->whereHas('orderItem', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }
        // Filter by status if provided
        if ($paymentType !== null && $paymentType !== '') {
            $query->whereHas('sellerOrder', function ($q) use ($paymentType) {
                $q->whereHas('order', function ($q) use ($paymentType) {
                    $q->where('payment_method', $paymentType);
                });
            });
        }

        // Filter by date range if provided
        if ($dateRange !== null && $dateRange !== '') {

            $fromDate = $this->getDateRange($dateRange);
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            }
        }

        // Search functionality
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('seller_order_id', 'like', "%$searchValue%")
                    ->orWhereHas('sellerOrder', function ($orderQuery) use ($searchValue) {
                        $orderQuery->where('total_price', 'like', "%$searchValue%")
                            ->orWhereHas('order', function ($orderQuery) use ($searchValue) {
                                $orderQuery->where('shipping_name', 'like', "%$searchValue%")
                                    ->orWhere('order_number', 'like', "%$searchValue%");
                            });
                    })
                    ->orWhereHas('orderItem', function ($orderItemQuery) use ($searchValue) {
                        $orderItemQuery->where('status', 'like', "%$searchValue%");
                    })
                    ->orWhereHas('product', function ($productQuery) use ($searchValue) {
                        $productQuery->where('title', 'like', "%$searchValue%");
                    })
                    ->orWhereHas('variant', function ($variantQuery) use ($searchValue) {
                        $variantQuery->where('title', 'like', "%$searchValue%");
                    });
            });
        }
        $filteredRecords = $query->count();

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($sellerOrderItem) {
                return $this->getOrderReturnData($sellerOrderItem);
            });

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Return orders waiting for the seller's decision.
     */
    public function getPendingRegularOrders(Request $request): JsonResponse
    {
        if (!in_array($this->getPanel(), ['seller', 'admin'], true)) {
            return response()->json(['data' => []]);
        }

        $orderMode = $request->input('order_mode', 'regular');
        if (!in_array($orderMode, ['regular', 'wholesale'], true)) {
            $orderMode = 'regular';
        }

        $items = SellerOrderItem::with([
            'sellerOrder.order.deliveryTimeSlot',
            'sellerOrder.bag',
            'orderItem',
            'product',
            'variant',
        ])->whereHas('sellerOrder', function ($query) use ($orderMode) {
            $query->whereHas('order', fn ($order) => $order->where('order_mode', $orderMode));
        })->whereHas('orderItem', function ($query) {
            $query->whereIn('status', [
                OrderItemStatusEnum::AWAITING_STORE_RESPONSE(),
                    OrderItemStatusEnum::ACCEPTED(),
            ]);
        });

        if ($this->getPanel() === 'seller') {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), []);
            }
            if (!$user->hasRole(DefaultSystemRolesEnum::SELLER()) && !$user->can('order.popup')) {
                return response()->json(['data' => []]);
            }
            app(SellerOrderPopupOfferService::class)->dispatchPendingOrders((int) $seller->id);
            $items->whereHas('sellerOrder', fn ($query) => $query->where('seller_id', $seller->id));
            $items->whereHas('sellerOrder.popupOffers', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereIn('status', \App\Models\SellerOrderPopupOffer::ACTIVE_STATUSES));
        }

        $items = $items->orderBy('created_at')->get();

        if ($orderMode === 'wholesale' && $request->boolean('popup')) {
            $now = Carbon::now();
            $popupWindowEnd = $now->copy()->addMinutes(30);

            $items = $items->filter(function ($item) use ($now, $popupWindowEnd) {
                $order = $item->sellerOrder->order;
                $slot = $order->deliveryTimeSlot;

                if (!$order->delivery_date || !$slot?->end_time) {
                    return false;
                }

                $slotEnd = Carbon::parse($order->delivery_date->format('Y-m-d') . ' ' . $slot->end_time);

                return $slotEnd->betweenIncluded($now, $popupWindowEnd);
            })->values();
        }

        $orders = $items->groupBy('seller_order_id')->values()->map(function ($orderItems) {
            $first = $orderItems->first();
            $order = $first->sellerOrder->order;

            return [
                'seller_order_id' => $first->seller_order_id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_mode' => $order->order_mode,
                'created_at' => $order->created_at?->toISOString(),
                'customer' => [
                    'name' => $order->shipping_name,
                    'phone' => $order->shipping_phone,
                    'address' => collect([
                        $order->shipping_address_1,
                        $order->shipping_address_2,
                        $order->shipping_landmark,
                        $order->shipping_city,
                        $order->shipping_state,
                        $order->shipping_zip,
                    ])->filter()->implode(', '),
                ],
                'payment_method' => $order->payment_method,
                'total' => $first->sellerOrder->total_price,
                'bag' => $first->sellerOrder->bag ? [
                    'id' => $first->sellerOrder->bag->id,
                    'barcode' => $first->sellerOrder->bag->barcode,
                ] : null,
                'delivery' => $order->deliveryTimeSlot ? trim(($order->delivery_date?->format('d M') ?? '') . ' ' . $order->deliveryTimeSlot->start_time . ' - ' . $order->deliveryTimeSlot->end_time) : null,
                'items' => $orderItems->map(fn ($item) => [
                    'order_item_id' => $item->order_item_id,
                    'product_id' => $item->product_id,
                    'product' => $item->product?->title,
                    'rack_number' => $item->product?->rack_number,
                    'variant' => $item->variant?->title,
                    'barcode' => $item->variant?->barcode,
                    'sku' => $item->orderItem?->sku,
                    'variant_weight' => $item->variant?->weight,
                    'variant_dimensions' => $item->variant
                        ? trim($item->variant->length . ' × ' . $item->variant->breadth . ' × ' . $item->variant->height . ' cm')
                        : null,
                    'status' => $item->orderItem->status,
                    'image' => $item->variant?->image ?: $item->product?->main_image,
                    'quantity' => $item->orderItem->quantity,
                    'subtotal' => $item->orderItem->subtotal,
                ])->values(),
            ];
        })->values();

        return response()->json(['data' => $orders, 'count' => $orders->count(), 'order_mode' => $orderMode]);
    }

    public function updateSellerOrderPopupPresence(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ]);
        $user = Auth::user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), [], 404);
        }
        if (!$user->hasRole(DefaultSystemRolesEnum::SELLER()) && !$user->can('order.popup')) {
            return ApiResponseType::sendJsonResponse(false, __('messages.unauthorized_action'), [], 403);
        }

        $presence = app(SellerOrderPopupOfferService::class)->updatePresence(
            $user,
            $seller,
            (bool) $validated['active'],
            isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            isset($validated['longitude']) ? (float) $validated['longitude'] : null,
        );

        return ApiResponseType::sendJsonResponse(true, 'Seller popup presence updated.', [
            'active' => $presence->active,
            'latitude' => $presence->latitude,
            'longitude' => $presence->longitude,
            'last_seen_at' => $presence->last_seen_at?->toIso8601String(),
        ]);
    }

    private function getOrderReturnData($sellerOrderItem): array
    {
        $variantTitle = $sellerOrderItem->product->type === ProductTypeEnum::SIMPLE() ? "" : ($sellerOrderItem->variant->title ?? "");
        $storeName = $sellerOrderItem->orderItem->store ? $sellerOrderItem->orderItem->store->name : 'N/A';
        $orderNote = !empty($sellerOrderItem->sellerOrder->order->order_note) ? "<textarea class='form-control' rows='1' readonly disabled>order note:- {$sellerOrderItem->sellerOrder->order->order_note}</textarea>" : null;
        $order = $sellerOrderItem->sellerOrder->order;
        $deliverySlot = $order->deliveryTimeSlot;
        $deliverySlotText = $deliverySlot
            ? '<p class="m-0">Delivery slot: ' . e(($order->delivery_date?->format('Y-m-d') ?? '') . ' ' . $deliverySlot->start_time . ' - ' . $deliverySlot->end_time) . '</p>'
            : '';
        return [
            'id' => $sellerOrderItem->order_item_id,
            'order_date' =>
                "<div><p class='m-0 fw-medium'>" . $sellerOrderItem->created_at->diffForHumans() . "</p>
                        {$sellerOrderItem->created_at->format('Y-m-d H:i:s')}
                        </div>",
            'order_details' => "<div class='d-flex justify-content-start align-items-center'><div class='pe-2'>" .
                view('partials.image', [
                    'image' => !empty($sellerOrderItem->variant->image) ? $sellerOrderItem->variant->image : $sellerOrderItem->product->main_image,
                ])->render() .
                "</div><div>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.order_number') . ": " . ($sellerOrderItem->sellerOrder->order->order_number ?? $sellerOrderItem->sellerOrder->order_id) . "</p>
                        <p class='m-0 fw-medium text-secondary'>" . __('labels.order_id') . ": {$sellerOrderItem->sellerOrder->order_id}</p>
                        <p class='m-0'>" . __('labels.buyer_name') . ": " . e($sellerOrderItem->sellerOrder->order->shipping_name) . "</p>
                        <p class='m-0'>" . __('labels.payment_method') . ": " . e($sellerOrderItem->sellerOrder->order->payment_method) . "</p>
                        <p class='m-0'>Purchase Type: " . e(ucfirst($sellerOrderItem->sellerOrder->order->order_mode ?? 'regular')) . "</p>
                        " . $deliverySlotText . "
                        <p class='m-0'>" . __('labels.is_rush_order') . ": " . ($sellerOrderItem->sellerOrder->order->is_rush_order ? 'Yes' : 'No') . "</p>
                        <p class='m-0'>" . __('labels.order_status') . ": " . Str::ucfirst(Str::replace("_", " ", $sellerOrderItem->sellerOrder->order->status)) . "</p>"
                        . $orderNote .
                        ( !empty($sellerOrderItem->sellerOrder->order->cancellation_note) ? "<div class='mt-1'><small class='text-danger'>Cancellation reason: " . e($sellerOrderItem->sellerOrder->order->cancellation_note) . "</small></div>" : "") .
                        "</div></div>",
            'product_details' => "<div>
                        <a href='" . route($this->getPanel() . '.products.show', ['id' => $sellerOrderItem->product->id]) . "' class='m-0 fw-medium text-primary'>" . __('labels.product_name') . ": {$sellerOrderItem->product->title}</a>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.variant_name') . ": $variantTitle</p>
                        <p class='m-0 fw-medium text-capitalize'>" . __('labels.store_name') . ": $storeName</p>
                        <p class='m-0'>" . __('labels.sku') . ": {$sellerOrderItem->orderItem->sku}</p>
                        <p class='m-0 fw-medium'>" . __('labels.quantity') . ": {$sellerOrderItem->orderItem->quantity}</p>
                        <p class='m-0 fw-medium'>" . __('labels.item_sub_total') . ": " . $this->currencyService->format($sellerOrderItem->orderItem->subtotal) . "</p>
                        <p class='m-0 fw-medium'>Bag: " . e($sellerOrderItem->sellerOrder->bag?->barcode ?? 'Not assigned') . "</p>
                        </div>",
            'status' => view('partials.order-status', [
                'status' => $sellerOrderItem->orderItem->status,
            ])->render(),
            'actions' => view('partials.order-actions', [
                'panel' => $this->getPanel(),
                'uuid' => $sellerOrderItem->sellerOrder->order->uuid,
                'id' => $sellerOrderItem->orderItem->id,
                'sellerOrderId' => $sellerOrderItem->seller_order_id,
                'orderId' => $sellerOrderItem->sellerOrder->order->id,
                'hierarchy' => OrderItem::getStatusHierarchy(),
                'route' => route($this->panelView('orders.show'), $this->getPanel() === 'seller' ? $sellerOrderItem->sellerOrder->id : $sellerOrderItem->sellerOrder->order_id),
                'title' => __('labels.edit_order') . $sellerOrderItem->sellerOrder->id,
                'status' => $sellerOrderItem->orderItem->status,
                'orderStatus' => $sellerOrderItem->sellerOrder->order->status,
                'editPermission' => $this->getPanel() === 'admin' ? false : $this->editPermission,
            ])->render(),
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
     * Display the specified order.
     *
     * @param int $id
     * @return View
     */
    public function show($id): View
    {
        if ($this->getPanel() === 'seller') {
            $user = auth()->user();
            $seller = $user?->seller();

            if (!$seller) {
                abort(404, __('labels.seller_not_found'));
            }
            $order = SellerOrder::where('id', $id)
                ->with(['order', 'order.deliveryTimeSlot.store', 'bag', 'items.product', 'items.variant', 'items.orderItem', 'order.items.store'])
                ->where('seller_id', $seller->id)
                ->firstOrFail();
        } else {
            $order = Order::with(['items', 'items.product', 'items.variant', 'items.store', 'sellerOrders.bag', 'sellerOrders.seller.user', 'promoLine', 'deliveryTimeSlot.store'])
                ->findOrFail($id);
        }
        $this->authorize('viewAny', $order);
        // Transform the order data using the resource
        $orderData = new OrderResource($order);

        return view($this->panelView('orders.show'), [
            'order' => $orderData->toArray(request()),
        ]);
    }

    /**
     * Update the order status.
     *
     * @param int $id
     * @param string $status
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id, string $status): JsonResponse
    {
        try {
            $seller = auth()->user()->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'));
            }

            // Find the order item to authorize the action
            $orderItem = SellerOrderItem::where('order_item_id', $id)
                ->whereHas('sellerOrder', function ($q) use ($seller) {
                    $q->where('seller_id', $seller->id);
                })
                ->first();

            if (!$orderItem) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.order_item_not_found'),
                    data: []
                );
            }
            if (!app(SellerOrderPopupOfferService::class)->canUserProcessOrder(
                (int) $orderItem->seller_order_id,
                (int) Auth::id()
            )) {
                return ApiResponseType::sendJsonResponse(false, 'This order popup is assigned to another system user.', [
                    'code' => 'seller_order_offer_assigned_to_another_user',
                ], 409);
            }

            $this->authorize('updateStatus', $orderItem);

            // Use the OrderService to update the status
            $result = $this->orderService->updateOrderStatusBySellerWithVerification(
                $id,
                $status,
                $seller->id,
                $request->input('barcode'),
                $request->filled('quantity') ? (int) $request->input('quantity') : null
            );
            if (!$result['success']) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: $result['message'],
                    data: $result['data'],
                );
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.unauthorized_action'),
                data: []
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.order_status_update_failed'),
                data: []
            );
        }
    }

    public function verifyAndPrepare(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer', 'distinct'],
            'items.*.barcode' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $seller = auth()->user()?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), [], 404);
        }

        $sellerOrder = SellerOrder::query()
            ->where('id', $id)
            ->where('seller_id', $seller->id)
            ->with('items')
            ->first();
        if (!$sellerOrder) {
            return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found'), [], 404);
        }
        if (!app(SellerOrderPopupOfferService::class)->canUserProcessOrder($id, (int) Auth::id())) {
            return ApiResponseType::sendJsonResponse(false, 'This order popup is assigned to another system user.', [
                'code' => 'seller_order_offer_assigned_to_another_user',
            ], 409);
        }

        try {
            foreach ($sellerOrder->items as $sellerOrderItem) {
                $this->authorize('updateStatus', $sellerOrderItem);
            }
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('messages.unauthorized_action'), [], 403);
        }

        $result = $this->orderService->verifyAndPrepareSellerOrder($id, $seller->id, $validated['items']);
        if ($result['success']) {
            app(SellerOrderPopupOfferService::class)->markCompleted($id, (int) $seller->id, (int) Auth::id());
        }
        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $result['data'] ?? [],
            $result['success'] ? 200 : 422
        );
    }

    public function assignBag(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['barcode' => ['required', 'string', 'max:255']]);
        $seller = auth()->user()?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), [], 404);
        }

        $sellerOrder = SellerOrder::query()->where('id', $id)->where('seller_id', $seller->id)->with('items')->first();
        if (!$sellerOrder) {
            return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found'), [], 404);
        }
        if (!app(SellerOrderPopupOfferService::class)->canUserProcessOrder($id, (int) Auth::id())) {
            return ApiResponseType::sendJsonResponse(false, 'This order popup is assigned to another system user.', [
                'code' => 'seller_order_offer_assigned_to_another_user',
            ], 409);
        }
        try {
            foreach ($sellerOrder->items as $sellerOrderItem) {
                $this->authorize('updateStatus', $sellerOrderItem);
            }
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('messages.unauthorized_action'), [], 403);
        }

        $result = $this->orderService->assignBagToSellerOrder($id, $seller->id, trim($validated['barcode']));
        return ApiResponseType::sendJsonResponse($result['success'], $result['message'], $result['data'] ?? [], $result['success'] ? 200 : 422);
    }

    public function takeOverPendingPopupOffer(int $id): JsonResponse
    {
        $user = Auth::user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), [], 404);
        }
        if ((int) $seller->user_id !== (int) $user->id) {
            return ApiResponseType::sendJsonResponse(false, __('messages.unauthorized_action'), [], 403);
        }

        $result = app(SellerOrderPopupOfferService::class)->takeOverPendingOffer(
            $id,
            (int) $seller->id,
            (int) $user->id
        );
        $data = $result['data'] ?? (isset($result['code']) ? ['code' => $result['code']] : []);

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'] ?? 'Pending order offer assigned to the main seller.',
            $data,
            $result['status'] ?? 200
        );
    }

    public function acceptSellerOrderItems(int $id): JsonResponse
    {
        $seller = auth()->user()?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), [], 404);
        }

        try {
            $result = DB::transaction(function () use ($id, $seller): array {
                $sellerOrder = SellerOrder::query()
                    ->where('id', $id)
                    ->where('seller_id', $seller->id)
                    ->with('items')
                    ->lockForUpdate()
                    ->first();
                if (!$sellerOrder) {
                    return [
                        'success' => false,
                        'message' => __('labels.order_not_found'),
                        'data' => [],
                        'http_status' => 404,
                    ];
                }

                $offer = \App\Models\SellerOrderPopupOffer::query()
                    ->where('seller_order_id', $id)
                    ->where('seller_id', $seller->id)
                    ->lockForUpdate()
                    ->first();
                if ($offer && (
                    (int) $offer->user_id !== (int) Auth::id()
                    || !in_array($offer->status, \App\Models\SellerOrderPopupOffer::ACTIVE_STATUSES, true)
                )) {
                    return [
                        'success' => false,
                        'message' => 'This order popup is assigned to another system user.',
                        'data' => ['code' => 'seller_order_offer_assigned_to_another_user'],
                        'http_status' => 409,
                    ];
                }

                foreach ($sellerOrder->items as $sellerOrderItem) {
                    $this->authorize('updateStatus', $sellerOrderItem);
                }

                $result = $this->orderService->acceptSellerOrderItems($id, $seller->id);
                if ($result['success'] && $offer?->status === 'pending') {
                    app(SellerOrderPopupOfferService::class)->markAccepted($id, (int) Auth::id());
                }

                return $result;
            });
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('messages.unauthorized_action'), [], 403);
        }

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $result['data'] ?? [],
            $result['http_status'] ?? ($result['success'] ? 200 : 422)
        );
    }

    public function orderInvoice(Request $request): View
    {
        try {
            $orderId = $request->input('id');
            $sellerOrder = sellerOrder::with('order', 'seller', 'order.promoLine', 'items.product', 'items.orderItem.store', 'items.variant', 'items.orderItem')
                ->whereHas('order', function ($q) use ($orderId) {
                    $q->where('uuid', $orderId);
                })
//                ->where('order_id', $orderId)
                ->get();
            if (count($sellerOrder) === 0) {
                abort(404, __('labels.order_not_found'));
            }
            // Attach seller authorized signature image URL for each seller
            foreach ($sellerOrder as $so) {
                if ($so->seller) {
                    $so->seller->authorized_signature = $so->seller->getFirstMediaUrl(SpatieMediaCollectionName::AUTHORIZED_SIGNATURE()) ?? null;
                }
            }
            $orderData = $sellerOrder[0]['order'];
            return view('layouts.order-invoice', [
                'order' => $orderData,
                'sellerOrder' => $sellerOrder,
            ]);
        } catch (AuthorizationException) {
            abort(403, __('messages.unauthorized_action'));
        }
    }

    public function downloadInvoicesForDateRange(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['nullable', \Illuminate\Validation\Rule::in(OrderStatusEnum::values())],
        ]);
        $this->authorize('viewAny', SellerOrder::class);

        $startDate = Carbon::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $validated['end_date'])->endOfDay();
        $sellerOrdersQuery = SellerOrder::query()
            ->with([
                'order',
                'seller',
                'items.product',
                'items.variant',
                'items.orderItem.store',
            ])
            ->whereHas('order', fn ($query) => $query->whereBetween('created_at', [$startDate, $endDate]));

        if (!empty($validated['status'])) {
            $sellerOrdersQuery->whereHas('order', fn ($query) => $query->where('status', $validated['status']));
        }

        if ($this->getPanel() === 'seller') {
            $seller = auth()->user()?->seller();
            abort_if(!$seller, 404, __('labels.seller_not_found'));
            $sellerOrdersQuery->where('seller_id', $seller->id);
        }

        $sellerOrders = $sellerOrdersQuery->orderBy('id')->get();

        $temporaryDirectory = storage_path('app/invoice-exports/' . Str::uuid());
        if (!mkdir($temporaryDirectory, 0700, true) && !is_dir($temporaryDirectory)) {
            throw new \RuntimeException('Unable to prepare a temporary directory for the invoice export.');
        }

        $htmlPath = $temporaryDirectory . '/invoices.html';
        $pdfPath = $temporaryDirectory . '/invoices.pdf';
        $rendererUnavailable = false;

        try {
            $html = view('layouts.order-invoice-batch', [
                'sellerOrders' => $sellerOrders,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'isAdmin' => $this->getPanel() === 'admin',
                'status' => $validated['status'] ?? null,
            ])->render();
            if (file_put_contents($htmlPath, $html, LOCK_EX) === false) {
                throw new \RuntimeException('Unable to prepare the invoice export document.');
            }

            $process = new Process([
                config('invoices.chromium_path', 'chromium'),
                '--headless=new',
                '--no-sandbox',
                '--disable-gpu',
                '--disable-dev-shm-usage',
                '--no-pdf-header-footer',
                '--print-to-pdf=' . $pdfPath,
                'file://' . $htmlPath,
            ]);
            $process->setTimeout((int) config('invoices.timeout', 120));
            $process->run();

            if (!$process->isSuccessful() || !is_file($pdfPath) || filesize($pdfPath) === 0) {
                Log::error('Bulk order invoice PDF generation failed.', [
                    'exit_code' => $process->getExitCode(),
                    'error' => $process->getErrorOutput(),
                    'output' => $process->getOutput(),
                ]);
                $rendererUnavailable = true;
            }
        } catch (ProcessStartFailedException $exception) {
            Log::error('Bulk order invoice PDF renderer could not be started.', [
                'error' => $exception->getMessage(),
            ]);
            $rendererUnavailable = true;
        } catch (\Throwable $exception) {
            @unlink($htmlPath);
            @unlink($pdfPath);
            @rmdir($temporaryDirectory);
            Log::error('Bulk order invoice PDF export failed.', ['error' => $exception->getMessage()]);
            throw $exception;
        }

        if ($rendererUnavailable) {
            @unlink($htmlPath);
            @unlink($pdfPath);
            @rmdir($temporaryDirectory);
            abort(503, 'Invoice PDF generation is unavailable. Please contact the system administrator.');
        }

        @unlink($htmlPath);

        $filename = sprintf(
            'order-invoices-%s-to-%s.pdf',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        return response()->streamDownload(
            static function () use ($pdfPath, $temporaryDirectory): void {
                try {
                    readfile($pdfPath);
                } finally {
                    @unlink($pdfPath);
                    @rmdir($temporaryDirectory);
                }
            },
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Cancel an order by admin
     *
     * @param Request $request
     * @param Order $order
     * @return JsonResponse
     */
    public function cancelOrder(Request $request, Order $order): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'cancellation_note' => 'nullable|string|max:500'
            ]);

            $result = $this->orderService->cancelOrderByAdmin(
                $order,
                $request->input('cancellation_note')
            );

            return ApiResponseType::sendJsonResponse(
                success: $result['success'],
                message: $result['message'],
                data: $result['data']
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
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
