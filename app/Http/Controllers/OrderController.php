<?php

namespace App\Http\Controllers;

use App\Enums\DateRangeFilterEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Order\OrderItemStatusEnum;
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
use App\Services\OrderService;
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
use Illuminate\Support\Str;

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

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';

        $columns = ['id', 'order_id', 'price', 'status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = SellerOrderItem::with(['sellerOrder', 'orderItem', 'orderItem.store', 'variant', 'product'])
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
                                $orderQuery->where('shipping_name', 'like', "%$searchValue%");
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

    private function getOrderReturnData($sellerOrderItem): array
    {
        $variantTitle = $sellerOrderItem->product->type === ProductTypeEnum::SIMPLE() ? "" : ($sellerOrderItem->variant->title ?? "");
        $storeName = $sellerOrderItem->orderItem->store ? $sellerOrderItem->orderItem->store->name : 'N/A';
        $orderNote = !empty($sellerOrderItem->sellerOrder->order->order_note) ? "<textarea class='form-control' rows='1' readonly disabled>order note:- {$sellerOrderItem->sellerOrder->order->order_note}</textarea>" : null;
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
                        <p class='m-0 fw-medium text-primary'>" . __('labels.order_id') . ": {$sellerOrderItem->sellerOrder->order_id}</p>
                        <p class='m-0'>" . __('labels.buyer_name') . ": " . e($sellerOrderItem->sellerOrder->order->shipping_name) . "</p>
                        <p class='m-0'>" . __('labels.payment_method') . ": " . e($sellerOrderItem->sellerOrder->order->payment_method) . "</p>
                        <p class='m-0'>" . __('labels.is_rush_order') . ": " . ($sellerOrderItem->sellerOrder->order->is_rush_order ? 'Yes' : 'No') . "</p>
                        <p class='m-0'>" . __('labels.order_status') . ": " . Str::ucfirst(Str::replace("_", " ", $sellerOrderItem->sellerOrder->order->status)) . "</p>"
                        . $orderNote .
                        "</div></div>",
            'product_details' => "<div>
                        <a href='" . route($this->getPanel() . '.products.show', ['id' => $sellerOrderItem->product->id]) . "' class='m-0 fw-medium text-primary'>" . __('labels.product_name') . ": {$sellerOrderItem->product->title}</a>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.variant_name') . ": $variantTitle</p>
                        <p class='m-0 fw-medium text-capitalize'>" . __('labels.store_name') . ": $storeName</p>
                        <p class='m-0'>" . __('labels.sku') . ": {$sellerOrderItem->orderItem->sku}</p>
                        <p class='m-0 fw-medium'>" . __('labels.quantity') . ": {$sellerOrderItem->orderItem->quantity}</p>
                        <p class='m-0 fw-medium'>" . __('labels.item_sub_total') . ": " . $this->currencyService->format($sellerOrderItem->orderItem->subtotal) . "</p>
                        </div>",
            'status' => view('partials.order-status', [
                'status' => $sellerOrderItem->orderItem->status,
            ])->render(),
            'actions' => view('partials.order-actions', [
                'panel' => $this->getPanel(),
                'uuid' => $sellerOrderItem->sellerOrder->order->uuid,
                'id' => $sellerOrderItem->orderItem->id,
                'hierarchy' => OrderItem::getStatusHierarchy(),
                'route' => route($this->panelView('orders.show'), $this->getPanel() === 'seller' ? $sellerOrderItem->sellerOrder->id : $sellerOrderItem->sellerOrder->order_id),
                'title' => __('labels.edit_order') . $sellerOrderItem->sellerOrder->id,
                'status' => $sellerOrderItem->orderItem->status,
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
                ->with(['order', 'items.product', 'items.variant', 'items.orderItem', 'order.items.store'])
                ->where('seller_id', $seller->id)
                ->firstOrFail();
        } else {
            $order = Order::with(['items', 'items.product', 'items.variant', 'items.store', 'promoLine'])
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
    public function updateStatus(int $id, string $status): JsonResponse
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

            $this->authorize('updateStatus', $orderItem);

            // Use the OrderService to update the status
            $result = $this->orderService->updateOrderStatusBySeller($id, $status, $seller->id);
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
}
