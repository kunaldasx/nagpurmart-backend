<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\Order\OrderItemStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\SellerFeedback;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Services\CurrencyService;
use App\Services\DashboardService;
use App\Types\Api\ApiResponseType;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Store;

#[Group('Seller Dashboard')]
class SellerDashboardApiController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected CurrencyService  $currencyService
    )
    {
    }

    /**
     * Combined dashboard endpoint: returns both chart and summary payloads together.
     */
    public function overview(Request $request): JsonResponse
    {
        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found') ?? 'Seller not found', null, 404);
        }

        $sellerId = $seller->id;

        // Optional store filter with ownership validation
        $storeId = $request->integer('store_id');
        if ($storeId) {
            $storeValid = Store::query()->where('id', $storeId)->where('seller_id', $sellerId)->exists();
            if (!$storeValid) {
                return ApiResponseType::sendJsonResponse(false, 'You are not authorized to access this store.', null, 403);
            }
        } else {
            $storeId = null;
        }

        // Build chart payload (reuse logic from chart())
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $weeklyData = [];
        $cursor = $startOfWeek->copy();
        while ($cursor->lte($endOfWeek)) {
            [$earnings, $orders] = $this->aggregateDay($sellerId, $cursor, $storeId);
            $weeklyData[] = [
                'day' => $cursor->format('D'),
                'earnings' => (float)$earnings,
                'orders' => (int)$orders,
            ];
            $cursor->addDay();
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $monthlyBuckets = [
            ['label' => 'Week 1', 'start' => $startOfMonth->copy(), 'end' => $startOfMonth->copy()->addDays(6)],
            ['label' => 'Week 2', 'start' => $startOfMonth->copy()->addDays(7), 'end' => $startOfMonth->copy()->addDays(13)],
            ['label' => 'Week 3', 'start' => $startOfMonth->copy()->addDays(14), 'end' => $startOfMonth->copy()->addDays(20)],
            ['label' => 'Week 4', 'start' => $startOfMonth->copy()->addDays(21), 'end' => $endOfMonth->copy()],
        ];
        $monthlyData = [];
        foreach ($monthlyBuckets as $bucket) {
            [$earnings, $orders] = $this->aggregateRange($sellerId, $bucket['start']->copy()->startOfDay(), $bucket['end']->copy()->endOfDay(), $storeId);
            $monthlyData[] = [
                'week' => $bucket['label'],
                'earnings' => (float)$earnings,
                'orders' => (int)$orders,
            ];
        }

        $year = Carbon::now()->year;
        $yearlyTotals = [];
        $yearlySum = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfDay();
            $end = Carbon::create($year, $m, 1)->endOfMonth()->endOfDay();
            [$earnings, $orders] = $this->aggregateRange($sellerId, $start, $end, $storeId);
            $yearlyTotals[$m] = ['earnings' => (float)$earnings, 'orders' => (int)$orders];
            $yearlySum += (float)$earnings;
        }
        $yearlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $earnings = $yearlyTotals[$m]['earnings'];
            $orders = $yearlyTotals[$m]['orders'];
            $percentage = $yearlySum > 0 ? (int)round(($earnings / $yearlySum) * 100) : 0;
            $yearlyData[] = [
                'month' => Carbon::create($year, $m, 1)->format('M'),
                'earnings' => (float)$earnings,
                'orders' => (int)$orders,
                'percentage' => $percentage,
            ];
        }

        $chart = [
            'weekly' => [
                'period' => 'Week',
                'data' => $weeklyData,
            ],
            'monthly' => [
                'period' => 'Month',
                'data' => $monthlyData,
            ],
            'yearly' => [
                'period' => 'Year',
                'data' => $yearlyData,
            ],
        ];

        // Build summary payload (reuse logic from summary())
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();
        [$todayEarnings] = $this->aggregateRange($sellerId, $todayStart, $todayEnd, $storeId);

        $yesterdayStart = Carbon::yesterday()->startOfDay();
        $yesterdayEnd = Carbon::yesterday()->endOfDay();
        [$yesterdayEarnings] = $this->aggregateRange($sellerId, $yesterdayStart, $yesterdayEnd, $storeId);

        $change = 0.0;
        if ($yesterdayEarnings > 0) {
            $change = (($todayEarnings - $yesterdayEarnings) / $yesterdayEarnings) * 100;
        } elseif ($todayEarnings > 0) {
            $change = 100.0;
        }
        $changeRounded = (int)round($change);
        $sign = $changeRounded > 0 ? '+' : '';

        $productStats = $this->dashboardService->getProductStats(sellerId: $sellerId, storeId: $storeId);
        $salesStats = $this->dashboardService->getSalesData(sellerId: $sellerId, storeId: $storeId);

        $totalOrders = SellerOrderItem::whereHas('sellerOrder', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id)->whereHas('order', function ($q) {
                $q->where('status', '!=', OrderItemStatusEnum::PENDING());
            });
        })
            ->when($storeId, function ($q) use ($storeId) {
                $q->whereHas('orderItem', function ($qq) use ($storeId) {
                    $qq->where('store_id', $storeId);
                });
            })
            ->count();
        $deliveredOrders = SellerOrderItem::whereHas('sellerOrder', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })
            ->whereHas('orderItem', function ($q) use ($storeId) {
                $q->where('status', OrderItemStatusEnum::DELIVERED())
                  ->when($storeId, function ($qq) use ($storeId) {
                      $qq->where('store_id', $storeId);
                  });
            })
            ->count();

        $summary = [
            'todays_revenue' => [
                'title' => "Today's Revenue",
                'amount' => $this->currencyService->format($todayEarnings),
                'change' => $changeRounded,
                'message' => sprintf('%s%d%% from yesterday', $sign, abs($changeRounded)),
            ],
            'total_orders' => [
                'title' => 'Total Orders',
                'count' => (string)$totalOrders,
                'message' => $deliveredOrders . ' Delivered',
            ],
            'total_products' => [
                'title' => 'Total Products',
                'count' => (string)($productStats['total_products'] ?? 0),
                'message' => ($productStats['recent_products'] ?? 0) . ' new this week',
            ],
            'sales' => [
                'title' => 'Items Sold',
                'solds' => (int)($salesStats['total_sales'] ?? 0),
                'message' => ($salesStats['unsettled_payments'] ?? 0) . ' unsettled payments',
            ],
        ];

        return ApiResponseType::sendJsonResponse(true, 'Seller dashboard data fetched', [
            'chart' => $chart,
            'summary' => $summary,
        ]);
    }

    /**
     * Chart data: weekly (Mon-Sun of current week), monthly (Week 1-4 of current month), yearly (Jan-Dec of current year)
     */
    public function chart(Request $request): JsonResponse
    {
        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found') ?? 'Seller not found', null, 404);
        }

        $sellerId = $seller->id;

        // Optional store filter with ownership validation
        $storeId = $request->integer('store_id');
        if ($storeId) {
            $storeValid = Store::query()->where('id', $storeId)->where('seller_id', $sellerId)->exists();
            if (!$storeValid) {
                return ApiResponseType::sendJsonResponse(false, 'You are not authorized to access this store.', null, 403);
            }
        } else {
            $storeId = null;
        }

        // Weekly: current week Monday-Sunday
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $weeklyData = [];
        $cursor = $startOfWeek->copy();
        while ($cursor->lte($endOfWeek)) {
            [$earnings, $orders] = $this->aggregateDay($sellerId, $cursor, $storeId);
            $weeklyData[] = [
                'day' => $cursor->format('D'),
                'earnings' => (float)$earnings,
                'orders' => (int)$orders,
            ];
            $cursor->addDay();
        }

        // Monthly: 4 weeks buckets of current month (1-7, 8-14, 15-21, 22-end)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $monthlyBuckets = [
            ['label' => 'Week 1', 'start' => $startOfMonth->copy(), 'end' => $startOfMonth->copy()->addDays(6)],
            ['label' => 'Week 2', 'start' => $startOfMonth->copy()->addDays(7), 'end' => $startOfMonth->copy()->addDays(13)],
            ['label' => 'Week 3', 'start' => $startOfMonth->copy()->addDays(14), 'end' => $startOfMonth->copy()->addDays(20)],
            ['label' => 'Week 4', 'start' => $startOfMonth->copy()->addDays(21), 'end' => $endOfMonth->copy()],
        ];
        $monthlyData = [];
        foreach ($monthlyBuckets as $bucket) {
            [$earnings, $orders] = $this->aggregateRange($sellerId, $bucket['start']->copy()->startOfDay(), $bucket['end']->copy()->endOfDay(), $storeId);
            $monthlyData[] = [
                'week' => $bucket['label'],
                'earnings' => (float)$earnings,
                'orders' => (int)$orders,
            ];
        }

        // Yearly: each month of current year with percentage of total
        $year = Carbon::now()->year;
        $yearlyTotals = [];
        $yearlySum = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfDay();
            $end = Carbon::create($year, $m, 1)->endOfMonth()->endOfDay();
            [$earnings, $orders] = $this->aggregateRange($sellerId, $start, $end, $storeId);
            $yearlyTotals[$m] = ['earnings' => (float)$earnings, 'orders' => (int)$orders];
            $yearlySum += (float)$earnings;
        }
        $yearlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $earnings = $yearlyTotals[$m]['earnings'];
            $orders = $yearlyTotals[$m]['orders'];
            $percentage = $yearlySum > 0 ? (int)round(($earnings / $yearlySum) * 100) : 0;
            $yearlyData[] = [
                'month' => Carbon::create($year, $m, 1)->format('M'),
                'earnings' => (float)$earnings,
                'orders' => (int)$orders,
                'percentage' => $percentage,
            ];
        }

        $payload = [
            'weekly' => [
                'period' => 'Week',
                'data' => $weeklyData,
            ],
            'monthly' => [
                'period' => 'Month',
                'data' => $monthlyData,
            ],
            'yearly' => [
                'period' => 'Year',
                'data' => $yearlyData,
            ],
        ];

        return ApiResponseType::sendJsonResponse(true, 'Seller dashboard chart data fetched', $payload);
    }

    /**
     * Summary/analysis data for seller dashboard.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found') ?? 'Seller not found', null, 404);
        }

        $sellerId = $seller->id;

        // Optional store filter with ownership validation
        $storeId = $request->integer('store_id');
        if ($storeId) {
            $storeValid = Store::query()->where('id', $storeId)->where('seller_id', $sellerId)->exists();
            if (!$storeValid) {
                return ApiResponseType::sendJsonResponse(false, 'You are not authorized to access this store.', null, 403);
            }
        } else {
            $storeId = null;
        }

        // Today's revenue (seller net)
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();
        [$todayEarnings] = $this->aggregateRange($sellerId, $todayStart, $todayEnd, $storeId);

        // Yesterday's revenue for percentage change
        $yesterdayStart = Carbon::yesterday()->startOfDay();
        $yesterdayEnd = Carbon::yesterday()->endOfDay();
        [$yesterdayEarnings] = $this->aggregateRange($sellerId, $yesterdayStart, $yesterdayEnd, $storeId);

        $change = 0.0;
        if ($yesterdayEarnings > 0) {
            $change = (($todayEarnings - $yesterdayEarnings) / $yesterdayEarnings) * 100;
        } elseif ($todayEarnings > 0) {
            $change = 100.0;
        }
        $changeRounded = (int)round($change);
        $sign = $changeRounded > 0 ? '+' : '';

        // Totals using existing dashboard service helpers
        $productStats = $this->dashboardService->getProductStats(sellerId: $sellerId, storeId: $storeId);
        $salesStats = $this->dashboardService->getSalesData(sellerId: $sellerId, storeId: $storeId);

        // Total orders (all) and delivered orders for this seller
        $totalOrders = SellerOrderItem::whereHas('sellerOrder', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id)->whereHas('order', function ($q) {
                $q->where('status', '!=', OrderItemStatusEnum::PENDING());
            });
        })
            ->when($storeId, function ($q) use ($storeId) {
                $q->whereHas('orderItem', function ($qq) use ($storeId) {
                    $qq->where('store_id', $storeId);
                });
            })
            ->count();
        $deliveredOrders = SellerOrderItem::whereHas('sellerOrder', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })
            ->whereHas('orderItem', function ($q) use ($storeId) {
                $q->where('status', OrderItemStatusEnum::DELIVERED())
                  ->when($storeId, function ($qq) use ($storeId) {
                      $qq->where('store_id', $storeId);
                  });
            })
            ->count();

        $payload = [
            'todays_revenue' => [
                'title' => "Today's Revenue",
                'amount' => $this->currencyService->format($todayEarnings),
                'change' => $changeRounded,
                'message' => sprintf('%s%d%% from yesterday', $sign, abs($changeRounded)),
            ],
            'total_orders' => [
                'title' => 'Total Orders',
                'count' => (string)$totalOrders,
                'message' => $deliveredOrders . ' Delivered',
            ],
            'total_products' => [
                'title' => 'Total Products',
                'count' => (string)($productStats['total_products'] ?? 0),
                'message' => ($productStats['recent_products'] ?? 0) . ' new this week',
            ],
            'sales' => [
                'title' => 'Items Sold',
                'solds' => (int)($salesStats['total_sales'] ?? 0),
                'message' => ($salesStats['unsettled_payments'] ?? 0) . ' unsettled payments',
            ],
        ];

        return ApiResponseType::sendJsonResponse(true, 'Seller dashboard summary fetched', $payload);
    }

    /**
     * Aggregate earnings (seller net) and orders count for a specific day.
     */
    private function aggregateDay(int $sellerId, Carbon $date, ?int $storeId = null): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        return $this->aggregateRange($sellerId, $start, $end, $storeId);
    }

    /**
     * Aggregate earnings (subtotal - admin_commission_amount) and orders count in range.
     */
    private function aggregateRange(int $sellerId, Carbon $start, Carbon $end, ?int $storeId = null): array
    {
        // Earnings based on delivered order items belonging to seller's stores
        $earnings = (float)(OrderItem::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', OrderItemStatusEnum::DELIVERED())
            ->whereHas('store', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->when($storeId, function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->select(DB::raw('COALESCE(SUM(subtotal - admin_commission_amount), 0) AS earnings'))
            ->value('earnings') ?? 0);

        // Orders count for the seller, optionally filtered by store
        if ($storeId) {
            // Count distinct orders that have items from the specified store
            $orders = (int)OrderItem::query()
                ->whereBetween('created_at', [$start, $end])
                ->whereHas('store', function ($q) use ($sellerId) {
                    $q->where('seller_id', $sellerId);
                })
                ->where('store_id', $storeId)
                ->distinct('order_id')
                ->count('order_id');
        } else {
            // Original logic: count seller orders by created_at
            $orders = (int)SellerOrder::query()
                ->where('seller_id', $sellerId)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        return [$earnings, $orders];
    }

    private function avgRating(int $sellerId, ?Carbon $start, ?Carbon $end): float
    {
        $query = SellerFeedback::query()->where('seller_id', $sellerId);
        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }
        $avg = (float)$query->avg('rating');
        // Round to one decimal like examples (e.g., 4.8)
        return round($avg, 1);
    }
}
