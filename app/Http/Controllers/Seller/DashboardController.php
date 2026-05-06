<?php

namespace App\Http\Controllers\Seller;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Product\ProductTypeEnum;
use App\Enums\SellerPermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\SellerOrderItem;
use App\Services\CurrencyService;
use App\Services\DashboardService;
use App\Services\WalletService;
use App\Traits\ChecksPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ChecksPermissions;

    protected WalletService $walletService;
    protected CurrencyService $currencyService;
    protected DashboardService $dashboardService;
    protected bool $viewPermission = true;
    protected $seller;


    public function __construct(
        WalletService    $walletService,
        CurrencyService  $currencyService,
        DashboardService $dashboardService
    )
    {
        $user = auth()->user();
        $this->seller = $user?->seller();
        $this->walletService = $walletService;
        $this->currencyService = $currencyService;
        $this->dashboardService = $dashboardService;
        $this->viewPermission = $this->hasPermission(SellerPermissionEnum::DASHBOARD_VIEW()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
    }

    /**
     * Display the seller dashboard with dynamic data.
     */
    public function index(Request $request): View
    {
        $seller = $this->seller;


        if (!$seller) {
            abort(404, __('messages.seller_not_found'));
        }

        // Get wallet balance
        $walletResult = $this->walletService->getWallet($seller->user->id);
        $wallet = $walletResult['success'] ? $walletResult['data'] : null;
        $walletBalance = $wallet ? $wallet->balance : 0;
        $blockedBalance = $wallet ? $wallet->blocked_balance : 0;

        // Get total orders
        $totalOrders = SellerOrderItem::whereHas('sellerOrder', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id)->whereHas('order', function ($q) {
                $q->where('status', '!=', OrderItemStatusEnum::PENDING());
            });
        })->count();

        // Get delivered orders
        $deliveredOrders = SellerOrderItem::whereHas('sellerOrder', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->whereHas('orderItem', function ($q) {
            $q->where('status', OrderItemStatusEnum::DELIVERED());
        })->count();

        $orderColumns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'order_date', 'name' => 'order_date', 'title' => __('labels.order_date'), 'orderable' => false, 'searchable' => false],
            ['data' => 'product_details', 'name' => 'product_details', 'title' => __('labels.product_details'), 'orderable' => false, 'searchable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status'), 'orderable' => false, 'searchable' => false],
            ['data' => 'actions', 'name' => 'actions', 'title' => __('labels.actions'), 'orderable' => false, 'searchable' => false],
        ];

        // Get statistics from dashboard service
        $salesData = $this->dashboardService->getSalesData($seller->id);
        $productStats = $this->dashboardService->getProductStats($seller->id);
        $revenueData = $this->dashboardService->getRevenueData(days: 30, sellerId: $seller->id);
        $monthlyRevenueData = $this->dashboardService->getRevenueData(days: 30, sellerId: $seller->id);
        $storeOrderTotals = $this->dashboardService->getStoreOrderTotals($seller->id);
        $storeRevenueData = $this->dashboardService->getStoreRevenueData($seller->id, 30);
        $todaysEarning = $this->dashboardService->getTodaysEarning($seller->id);
        $dailyPurchaseHistory = $this->dashboardService->getDailyPurchaseHistory(sellerId: $seller->id);
        $recentFeedback = $this->dashboardService->getRecentSellerFeedback($seller->id);
        $activeCustomersData = $this->dashboardService->getActiveCustomersData($seller->id);
        $conversionRateData = $this->dashboardService->getConversionRateData(sellerId: $seller->id, days: 30);

        // Pass the currency service to the view
        $currencyService = $this->currencyService;
        $viewPermission = $this->viewPermission;

        return view('seller.dashboard', compact(
            'orderColumns',
            'walletBalance',
            'blockedBalance',
            'totalOrders',
            'deliveredOrders',
            'salesData',
            'productStats',
            'revenueData',
            'monthlyRevenueData',
            'storeOrderTotals',
            'storeRevenueData',
            'currencyService',
            'todaysEarning',
            'dailyPurchaseHistory',
            'recentFeedback',
            'activeCustomersData',
            'conversionRateData',
            'viewPermission'
        ));
    }


    /**
     * Get dashboard chart data as JSON for AJAX requests.
     */
    public function getChartData(Request $request): JsonResponse
    {
        $user = $request->user();
        $seller = $user->seller();

        if (!$seller) {
            return response()->json(['error' => 'Seller not found'], 404);
        }

        // Get data from dashboard service
        $monthlyRevenueData = $this->dashboardService->getRevenueData(days: 30, sellerId: $seller->id);
        $storeOrderTotals = $this->dashboardService->getStoreOrderTotals($seller->id);
        $dailyPurchaseHistory = $this->dashboardService->getDailyPurchaseHistory(sellerId: $seller->id);

        return response()->json([
            'revenue_data' => $monthlyRevenueData,
            'store_order_totals' => $storeOrderTotals,
            'daily_purchase_history' => $dailyPurchaseHistory
        ]);
    }

    /**
     * Get dashboard data via AJAX for dynamic updates.
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $user = $request->user();
        $seller = $user->seller();

        if (!$seller) {
            return response()->json(['error' => 'Seller not found'], 404);
        }

        $type = $request->input('type');
        $days = $request->input('days', 7);

        $data = [];

        switch ($type) {
            case 'sales':
                $data = $this->dashboardService->getConversionRateData(sellerId: $seller->id, days: $days);;
                break;
            case 'revenue':
                $data = $this->dashboardService->getRevenueData(days: $days, sellerId: $seller->id);
                break;
            case 'store_revenue':
                $data = $this->dashboardService->getStoreRevenueData($seller->id, $days);
                break;
            case 'seller_orders':
                $data = $this->dashboardService->getDailyPurchaseHistory($days, $seller->id);
                break;
            case 'active_users':
                $data = $this->dashboardService->getActiveCustomersData($seller->id);
                break;
            default:
                return response()->json(['error' => 'Invalid data type requested'], 400);
        }

        return response()->json($data);
    }

    /**
     * Format order data for display (reused from OrderController logic)
     */
    private function getOrderReturnData($sellerOrderItem): array
    {
        $variantTitle = $sellerOrderItem->product->type === ProductTypeEnum::SIMPLE() ? "" : $sellerOrderItem->variant->title;
        $storeName = $sellerOrderItem->orderItem->store ? $sellerOrderItem->orderItem->store->name : 'N/A';

        return [
            'id' => $sellerOrderItem->id,
            'order_date' =>
                "<div><p class='m-0 fw-medium'>" . $sellerOrderItem->created_at->diffForHumans() . "</p>
                        {$sellerOrderItem->created_at->format('Y-m-d H:i:s')}
                        </div>",
            'order_details' => "<div>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.order_id') . ": {$sellerOrderItem->sellerOrder->order_id}</p>
                        <p class='m-0'>" . __('labels.buyer_name') . ": " . e($sellerOrderItem->sellerOrder->order->shipping_name) . "</p>
                        <p class='m-0'>" . __('labels.payment_method') . ": " . e($sellerOrderItem->sellerOrder->order->payment_method) . "</p>
                        <p class='m-0'>" . __('labels.is_rush_order') . ": " . ($sellerOrderItem->sellerOrder->order->is_rush_order ? 'Yes' : 'No') . "</p>
                        <p class='m-0'>" . __('labels.order_status') . ": " . Str::ucfirst(Str::replace("_", " ", $sellerOrderItem->sellerOrder->order->status)) . "</p>
                        </div>",
            'product_details' => "<div>
                        <a href='" . route('seller.products.show', ['id' => $sellerOrderItem->product->id]) . "' class='m-0 fw-medium text-primary'>" . __('labels.product_name') . ": {$sellerOrderItem->product->title}</a>
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
                'panel' => 'seller',
                'uuid' => $sellerOrderItem->sellerOrder->order->uuid,
                'id' => $sellerOrderItem->orderItem->id,
                'hierarchy' => OrderItem::getStatusHierarchy(),
                'route' => route('seller.orders.show', $sellerOrderItem->sellerOrder->id),
                'title' => __('labels.edit_order') . $sellerOrderItem->sellerOrder->id,
                'status' => $sellerOrderItem->orderItem->status,
                'editPermission' => true,
            ])->render(),
        ];
    }
}
