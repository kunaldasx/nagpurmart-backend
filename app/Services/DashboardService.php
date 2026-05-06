<?php

namespace App\Services;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Seller\SellerVerificationStatusEnum;
use App\Enums\Seller\SellerVisibilityStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Enums\Store\StoreVerificationStatusEnum;
use App\Enums\Store\StoreVisibilityStatusEnum;
use App\Models\Category;
use App\Models\DeliveryBoy;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerOrderItem;
use App\Models\Store;
use App\Models\SellerFeedback;
use App\Models\StoreProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get revenue data for the specified number of days.
     */
    public function getRevenueData(int $days, ?int $sellerId = null): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        if ($sellerId) {
            // Add seller condition only if sellerId is provided
            $query = OrderItem::with(['order', 'store'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', OrderItemStatusEnum::DELIVERED());

            $query->whereHas('store', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            });
        } else {
            $query = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', OrderStatusEnum::DELIVERED());
        }

        $revenueByDay = $query->get()
            ->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            })
            ->map(function ($items) use ($sellerId) {
                $totalRevenue = $items->sum(function ($item) use ($sellerId) {
                    if ($sellerId) {
                        return $item->subtotal - $item->admin_commission_amount;
                    }
                    return $item->total_payable;
                });

                return [
                    'date' => $items->first()->created_at->format('Y-m-d'),
                    'revenue' => $totalRevenue,
                    'formatted_revenue' => $this->currencyService->format($totalRevenue)
                ];
            })
            ->values()
            ->toArray();

        // Fill in missing days with zero revenue
        $result = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $found = false;

            foreach ($revenueByDay as $day) {
                if ($day['date'] === $dateStr) {
                    $result[] = $day;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $result[] = [
                    'date' => $dateStr,
                    'revenue' => 0,
                    'formatted_revenue' => $this->currencyService->format(0)
                ];
            }

            $currentDate->addDay();
        }

        // Calculate total revenue
        $totalRevenue = array_sum(array_column($result, 'revenue'));

        return [
            'daily' => $result,
            'total' => $totalRevenue,
            'formatted_total' => $this->currencyService->format($totalRevenue)
        ];
    }

    /**
     * Get store-wise order totals for the seller.
     */
    public function getStoreOrderTotals(int $sellerId): array
    {
        $stores = Store::where('seller_id', $sellerId)->get();

        $result = [];
        $totalOrders = 0;

        foreach ($stores as $store) {
            $orderCount = SellerOrderItem::whereHas('orderItem', function ($q) use ($store) {
                $q->where('store_id', $store->id);
            })->count();

            $result[] = [
                'id' => $store->id,
                'name' => $store->name,
                'order_count' => $orderCount
            ];

            $totalOrders += $orderCount;
        }

        // Calculate percentages
        if ($totalOrders > 0) {
            foreach ($result as &$store) {
                $store['percentage'] = round(($store['order_count'] / $totalOrders) * 100);
            }
        } else {
            foreach ($result as &$store) {
                $store['percentage'] = 0;
            }
        }

        return [
            'stores' => $result,
            'total' => $totalOrders
        ];
    }

    /**
     * Get store-wise revenue data with date filtering for the seller.
     */
    public function getStoreRevenueData(int $sellerId, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $stores = Store::where('seller_id', $sellerId)->get();

        $result = [];
        $totalRevenue = 0;

        foreach ($stores as $store) {
            $revenue = OrderItem::where('store_id', $store->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', OrderItemStatusEnum::DELIVERED())
                ->sum(DB::raw('subtotal - admin_commission_amount'));

            $result[] = [
                'id' => $store->id,
                'name' => $store->name,
                'revenue' => $revenue,
                'formatted_revenue' => $this->currencyService->format($revenue)
            ];

            $totalRevenue += $revenue;
        }

        // Sort by revenue descending
        usort($result, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        return [
            'stores' => $result,
            'total' => $totalRevenue,
            'formatted_total' => $this->currencyService->format($totalRevenue),
            'days' => $days
        ];
    }

    /**
     * Get today's earning with comparison to yesterday.
     */
    public function getTodaysEarning(?int $sellerId = null): array
    {
        $today = Carbon::now()->format('Y-m-d');
        $yesterday = Carbon::now()->subDay()->format('Y-m-d');

        // Determine earning calculation based on sellerId
        $earningCalculation = $sellerId
            ? 'SUM(subtotal - admin_commission_amount)'
            : 'SUM(admin_commission_amount)';

        $query = OrderItem::selectRaw("
            DATE(created_at) as date,
            {$earningCalculation} as earning
        ")
            ->whereIn(DB::raw('DATE(created_at)'), [$today, $yesterday])
            ->where('status', OrderItemStatusEnum::DELIVERED());

        // Add seller condition only if sellerId is provided
        if ($sellerId) {
            $query->whereHas('store', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            });
        }

        $earnings = $query->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('date');

        // Get today's and yesterday's earnings
        $todaysEarning = $earnings->get($today)?->earning ?? 0;
        $yesterdaysEarning = $earnings->get($yesterday)?->earning ?? 0;

        // Calculate percentage change
        $percentageChange = 0;
        if ($yesterdaysEarning > 0) {
            $percentageChange = (($todaysEarning - $yesterdaysEarning) / $yesterdaysEarning) * 100;
        } elseif ($todaysEarning > 0) {
            $percentageChange = 100; // If yesterday was 0 and today is positive, that's a 100% increase
        }

        return [
            'today' => $todaysEarning,
            'yesterday' => $yesterdaysEarning,
            'formatted_today' => $this->currencyService->format($todaysEarning),
            'formatted_yesterday' => $this->currencyService->format($yesterdaysEarning),
            'percentage_change' => round($percentageChange, 2),
            'is_increase' => $percentageChange >= 0
        ];
    }

    /**
     * Get daily purchase history for the last month.
     */
    public function getDailyPurchaseHistory(int $days = 30, ?int $sellerId = null): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $query = OrderItem::selectRaw('DATE(created_at) as date, COUNT(*) as order_count')
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Add seller condition only if sellerId is provided
        if ($sellerId) {
            $query->whereHas('store', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            });
        }

        $ordersByDay = $query->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        // Fill in missing days with zero orders
        $result = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');

            if (isset($ordersByDay[$dateStr])) {
                $result[] = [
                    'date' => $dateStr,
                    'order_count' => (int)$ordersByDay[$dateStr]['order_count'],
                ];
            } else {
                $result[] = [
                    'date' => $dateStr,
                    'order_count' => 0,
                ];
            }

            $currentDate->addDay();
        }

        // Calculate total orders
        $totalOrders = array_sum(array_column($result, 'order_count'));

        return [
            'daily' => $result,
            'total' => $totalOrders,
            'days' => count($result)
        ];
    }

    /**
     * Get recent seller feedback.
     */
    public function getRecentSellerFeedback(int $sellerId): array
    {
        $feedback = SellerFeedback::where('seller_id', $sellerId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_name' => $item->user->name ?? 'Anonymous',
                    'rating' => $item->rating,
                    'title' => $item->title,
                    'description' => $item->description,
                    'date' => $item->created_at->format('d M Y')
                ];
            })
            ->toArray();

        // Get overall statistics
        $stats = SellerFeedback::getSellerFeedbackStatistics($sellerId);

        return [
            'items' => $feedback,
            'total_reviews' => $stats->total_reviews ?? 0,
            'average_rating' => round($stats->average_rating ?? 0, 1)
        ];
    }

    /**
     * Get total sales and unsettled payments for the seller.
     */
    public function getSalesData(int $sellerId, int $storeId = null): array
    {
        $salesQuery = OrderItem::whereHas('store', function ($query) use ($sellerId) {
            $query->where('seller_id', $sellerId);
        })
            ->when($storeId, function ($query) use ($storeId) {
                $query->where('store_id', $storeId);
            });

        // Get total sales (delivered order items)
        $totalSales = (clone $salesQuery)
            ->where('status', OrderItemStatusEnum::DELIVERED())
            ->count();

        // Get unsettled payments (order items with commission_settled = '0')
        $unsettledPayments = (clone $salesQuery)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'completed');
            })
            ->where('commission_settled', '0')
            ->where('admin_commission_amount', '>', 0)
            ->count();

        return [
            'total_sales' => $totalSales,
            'unsettled_payments' => $unsettledPayments
        ];
    }

    /**
     * Get product statistics for the seller.
     */
    public function getProductStats(int $sellerId, int $storeId = null): array
    {
        $productQuery = Product::where('seller_id', $sellerId)
            ->when($storeId, function ($query) use ($storeId) {
                $query->whereHas('variants.storeProductVariants', function ($storeProductVariantQuery) use ($storeId) {
                    $storeProductVariantQuery->where('store_id', $storeId);
                });
            });

        // Get total number of products
        $totalProducts = (clone $productQuery)->count();

        // Get number of products added in the last 7 days
        $recentProducts = (clone $productQuery)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        return [
            'total_products' => $totalProducts,
            'recent_products' => $recentProducts
        ];
    }

    /**
     * Get active customers data for the seller.
     */
    public function getActiveCustomersData(int $sellerId): array
    {
        // Current period (last 7 days)
        $currentPeriodStart = Carbon::now()->subDays(7)->startOfDay();
        $currentPeriodEnd = Carbon::now()->endOfDay();

        // Previous period (7 days before the current period)
        $previousPeriodStart = Carbon::now()->subDays(14)->startOfDay();
        $previousPeriodEnd = Carbon::now()->subDays(7)->endOfDay();

        // Get unique customer count for current period
        $currentPeriodCustomers = OrderItem::with(['order'])
            ->whereHas('store', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])
            ->get()
            ->pluck('order.user_id')
            ->unique()
            ->count();

        // Get unique customer count for previous period
        $previousPeriodCustomers = OrderItem::with(['order'])
            ->whereHas('store', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->get()
            ->pluck('order.user_id')
            ->unique()
            ->count();

        // Calculate percentage change
        $percentageChange = 0;
        if ($previousPeriodCustomers > 0) {
            $percentageChange = (($currentPeriodCustomers - $previousPeriodCustomers) / $previousPeriodCustomers) * 100;
        } elseif ($currentPeriodCustomers > 0) {
            $percentageChange = 100; // If previous period was 0 and current is positive, that's a 100% increase
        }

        return [
            'count' => $currentPeriodCustomers,
            'previous_count' => $previousPeriodCustomers,
            'percentage_change' => round($percentageChange, 2),
            'is_increase' => $percentageChange >= 0
        ];
    }

    /**
     * Get conversion rate data for the seller.
     * Conversion rate is the percentage of delivered orders out of total orders.
     */
    public function getConversionRateData(int $sellerId, int $days = 7): array
    {
        // Current period (last 7 days)
        $currentPeriodStart = Carbon::now()->subDays($days)->startOfDay();
        $currentPeriodEnd = Carbon::now()->endOfDay();

        // Previous period (7 days before the current period)
        $previousPeriodStart = Carbon::now()->subDays($days * 2)->startOfDay();
        $previousPeriodEnd = Carbon::now()->subDays($days)->endOfDay();

        // Get total orders for the current period
        $currentPeriodTotalOrders = SellerOrderItem::whereHas('sellerOrder', function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        })
            ->whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])
            ->count();

        // Get delivered orders for the current period
        $currentPeriodDeliveredOrders = SellerOrderItem::whereHas('sellerOrder', function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        })
            ->whereHas('orderItem', function ($q) {
                $q->where('status', OrderItemStatusEnum::DELIVERED());
            })
            ->whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])
            ->count();

        // Get total orders for a previous period
        $previousPeriodTotalOrders = SellerOrderItem::whereHas('sellerOrder', function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        })
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        // Get delivered orders for a previous period
        $previousPeriodDeliveredOrders = SellerOrderItem::whereHas('sellerOrder', function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        })
            ->whereHas('orderItem', function ($q) {
                $q->where('status', OrderItemStatusEnum::DELIVERED());
            })
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        // Calculate conversion rates
        $currentPeriodRate = $currentPeriodTotalOrders > 0
            ? round(($currentPeriodDeliveredOrders / $currentPeriodTotalOrders) * 100, 2)
            : 0;

        $previousPeriodRate = $previousPeriodTotalOrders > 0
            ? round(($previousPeriodDeliveredOrders / $previousPeriodTotalOrders) * 100, 2)
            : 0;

        // Calculate percentage change
        $percentageChange = 0;
        if ($previousPeriodRate > 0) {
            $percentageChange = (($currentPeriodRate - $previousPeriodRate) / $previousPeriodRate) * 100;
        } elseif ($currentPeriodRate > 0) {
            $percentageChange = 100; // If previous period was 0 and current is positive, that's a 100% increase
        }

        return [
            'rate' => $currentPeriodRate,
            'previous_rate' => $previousPeriodRate,
            'delivered_orders' => $currentPeriodDeliveredOrders,
            'total_orders' => $currentPeriodTotalOrders,
            'percentage_change' => round($percentageChange, 2),
            'is_increase' => $percentageChange >= 0
        ];
    }

    /**
     * Get admin commission charts data.
     */
    public function getAdminCommissionChartsData(int $days): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $commissionByDay = OrderItem::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', OrderItemStatusEnum::DELIVERED())
            ->get()
            ->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            })
            ->map(function ($items) {
                $totalCommission = $items->sum(function ($item) {
                    return $item->admin_commission_amount;
                });

                return [
                    'date' => $items->first()->created_at->format('Y-m-d'),
                    'revenue' => $totalCommission,
                    'formatted_revenue' => $this->currencyService->format($totalCommission)
                ];
            })
            ->values()
            ->toArray();

        // Fill in missing days with zero revenue
        $result = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $found = false;

            foreach ($commissionByDay as $day) {
                if ($day['date'] === $dateStr) {
                    $result[] = $day;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $result[] = [
                    'date' => $dateStr,
                    'revenue' => 0,
                    'formatted_revenue' => $this->currencyService->format(0)
                ];
            }

            $currentDate->addDay();
        }

        // Calculate total revenue
        $totalCommission = array_sum(array_column($result, 'revenue'));

        return [
            'daily' => $result,
            'total' => $totalCommission,
            'formatted_total' => $this->currencyService->format($totalCommission)
        ];
    }

    public function getAdminInsightsData(): array
    {
        $today = Carbon::now()->format('Y-m-d');

        // Single query for sellers with both conditions
        $totalSellers = Seller::where('verification_status', SellerVerificationStatusEnum::Approved())->where('visibility_status', SellerVisibilityStatusEnum::Visible())->count();

        // Single query for stores with both conditions
        $totalStores = Store::where([
            ['verification_status', StoreVerificationStatusEnum::APPROVED()],
            ['visibility_status', StoreVisibilityStatusEnum::VISIBLE()]
        ])->count();

        // Combined query for delivery boys data
        $deliveryBoysData = DeliveryBoy::where('verification_status', DeliveryBoyVerificationStatusEnum::VERIFIED())
            ->selectRaw('
            COUNT(*) as total_delivery_boys,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_active_delivery_boys
        ', [ActiveInactiveStatusEnum::ACTIVE()])
            ->first();

        // Combined query for orders data
        $ordersData = Order::selectRaw('
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_delivered_orders
    ', [OrderItemStatusEnum::DELIVERED()])
            ->first();

        // Combined query for order items data (sales and commission)
        $orderItemsData = OrderItem::selectRaw('
        COUNT(CASE WHEN status = ? THEN 1 END) as total_product_sales,
        SUM(CASE WHEN DATE(created_at) = ? AND status = ? THEN admin_commission_amount ELSE 0 END) as todays_commission
    ', [
            OrderItemStatusEnum::DELIVERED(),
            $today,
            OrderItemStatusEnum::DELIVERED()
        ])->first();

        // Simple count queries (these are typically fast)
        $totalUsers = User::count();
        $totalProducts = Product::count();

        return [
            'total_sellers' => $totalSellers,
            'total_stores' => $totalStores,
            'total_orders' => $ordersData->total_orders,
            'total_delivered_orders' => $ordersData->total_delivered_orders,
            'total_users' => $totalUsers,
            'total_products' => $totalProducts,
            'total_product_sales' => $orderItemsData->total_product_sales ?? 0,
            'todays_commission' => $orderItemsData->todays_commission ?? 0,
            'formatted_todays_commission' => $this->currencyService->format($orderItemsData->todays_commission ?? 0),
            'total_delivery_boys' => $deliveryBoysData->total_delivery_boys,
            'total_active_delivery_boys' => $deliveryBoysData->total_active_delivery_boys,
        ];
    }

    public function getAdminConversionRateData($days = 7): array
    {
        // Current period (last {$days} days)
        $currentPeriodStart = Carbon::now()->subDays($days)->startOfDay();
        $currentPeriodEnd = Carbon::now()->endOfDay();

        // Previous period ({$days} days before the current period)
        $previousPeriodStart = Carbon::now()->subDays($days * 2)->startOfDay();
        $previousPeriodEnd = Carbon::now()->subDays($days)->endOfDay();

        // Get total orders for the current period
        $currentPeriodTotalOrders = OrderItem::whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])
            ->count();

        // Get delivered orders for the current period
        $currentPeriodDeliveredOrders = OrderItem::where('status', OrderItemStatusEnum::DELIVERED())
            ->whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])
            ->count();

        // Get total orders for a previous period
        $previousPeriodTotalOrders = OrderItem::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        // Get delivered orders for a previous period
        $previousPeriodDeliveredOrders = OrderItem::where('status', OrderItemStatusEnum::DELIVERED())->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        // Calculate conversion rates
        $currentPeriodRate = $currentPeriodTotalOrders > 0
            ? round(($currentPeriodDeliveredOrders / $currentPeriodTotalOrders) * 100, 2)
            : 0;

        $previousPeriodRate = $previousPeriodTotalOrders > 0
            ? round(($previousPeriodDeliveredOrders / $previousPeriodTotalOrders) * 100, 2)
            : 0;

        // Calculate percentage change
        $percentageChange = 0;
        if ($previousPeriodRate > 0) {
            $percentageChange = (($currentPeriodRate - $previousPeriodRate) / $previousPeriodRate) * 100;
        } elseif ($currentPeriodRate > 0) {
            $percentageChange = 100; // If the previous period was 0 and the current is positive, that's a 100% increase
        }

        return [
            'rate' => $currentPeriodRate,
            'previous_rate' => $previousPeriodRate,
            'delivered_orders' => $currentPeriodDeliveredOrders,
            'total_orders' => $currentPeriodTotalOrders,
            'percentage_change' => round($percentageChange, 2),
            'is_increase' => $percentageChange >= 0
        ];
    }

    /**
     * Get category product weightage data for pie chart.
     * Only includes categories that have products.
     */
    public function getCategoryProductWeightage(): array
    {
        // Get all categories with their product counts
        $categories = Category::withCount('products')
            ->having('products_count', '>', 0)
            ->get();

        // Calculate total products
        $totalProducts = $categories->sum('products_count');

        // Prepare data for pie chart
        $series = [];
        $labels = [];

        foreach ($categories as $category) {
            $series[] = $category->products_count;
            $labels[] = $category->title;
        }

        return [
            'series' => $series,
            'labels' => $labels,
            'total' => $totalProducts
        ];
    }

    /**
     * Get new user registrations data for the specified number of days.
     */
    public function getNewUserRegistrationsData(int $days = 7): array
    {
        // Current period (last X days)
        $currentPeriodStart = Carbon::now()->subDays($days)->startOfDay();
        $currentPeriodEnd = Carbon::now()->endOfDay();

        // Previous period (X days before the current period)
        $previousPeriodStart = Carbon::now()->subDays($days * 2)->startOfDay();
        $previousPeriodEnd = Carbon::now()->subDays($days)->endOfDay();

        // Get new user registrations for current period
        $currentPeriodRegistrations = User::whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])
            ->count();

        // Get new user registrations for previous period
        $previousPeriodRegistrations = User::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        // Calculate percentage change
        $percentageChange = 0;
        if ($previousPeriodRegistrations > 0) {
            $percentageChange = (($currentPeriodRegistrations - $previousPeriodRegistrations) / $previousPeriodRegistrations) * 100;
        } elseif ($currentPeriodRegistrations > 0) {
            $percentageChange = 100; // If previous period was 0 and current is positive, that's a 100% increase
        }

        // Get daily registration data for chart
        $registrationsByDay = User::whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])
            ->get()
            ->groupBy(function ($user) {
                return $user->created_at->format('Y-m-d');
            })
            ->map(function ($users) {
                return [
                    'date' => $users->first()->created_at->format('Y-m-d'),
                    'count' => $users->count()
                ];
            })
            ->values()
            ->toArray();

        // Fill in missing days with zero registrations
        $result = [];
        $currentDate = clone $currentPeriodStart;

        while ($currentDate <= $currentPeriodEnd) {
            $dateStr = $currentDate->format('Y-m-d');
            $found = false;

            foreach ($registrationsByDay as $day) {
                if ($day['date'] === $dateStr) {
                    $result[] = $day;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $result[] = [
                    'date' => $dateStr,
                    'count' => 0
                ];
            }

            $currentDate->addDay();
        }

        return [
            'count' => $currentPeriodRegistrations,
            'previous_count' => $previousPeriodRegistrations,
            'percentage_change' => round($percentageChange, 2),
            'is_increase' => $percentageChange >= 0,
            'daily' => $result
        ];
    }

    /**
     * Get top sellers based on revenue for the specified number of days.
     */
    public function getTopSellers(int $days = 7, int $limit = 10): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $topSellers = Seller::select('sellers.*')->with('user')
            ->selectRaw('SUM(seller_order_items.price) as total_revenue')
            ->selectRaw('COUNT(seller_order_items.id) as total_orders')
            ->join('seller_orders', 'sellers.id', '=', 'seller_orders.seller_id')
            ->join('seller_order_items', 'seller_orders.id', '=', 'seller_order_items.seller_order_id')
            ->join('order_items', 'seller_order_items.order_item_id', '=', 'order_items.id')
            ->where('order_items.status', OrderItemStatusEnum::DELIVERED())
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->where('sellers.verification_status', SellerVerificationStatusEnum::Approved())
            ->where('sellers.visibility_status', SellerVisibilityStatusEnum::Visible())
            ->groupBy('sellers.id')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();

        return $topSellers->map(function ($seller) {
            return [
                'id' => $seller->id,
                'name' => $seller->user->name ?? 'N/A',
                'email' => $seller->user->email ?? '',
                'total_revenue' => $this->currencyService->format($seller->total_revenue),
                'total_revenue_raw' => $seller->total_revenue,
                'total_orders' => $seller->total_orders,
                'avatar' => $seller->user->getFirstMediaUrl(SpatieMediaCollectionName::PROFILE_IMAGE()) ?: null,
            ];
        })->toArray();
    }

    /**
     * Get top selling products for the specified number of days.
     */
    public function getTopSellingProducts(int $days = 7, int $limit = 10): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $topProducts = Product::select('products.*')->with('category')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->selectRaw('SUM(order_items.subtotal) as total_revenue')
            ->selectRaw('COUNT(order_items.id) as total_orders')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->where('order_items.status', OrderItemStatusEnum::DELIVERED())
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->where('products.status', ProductStatusEnum::ACTIVE())
            ->groupBy('products.id')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get();

        return $topProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->title,
                'category' => $product->category->title,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'total_quantity' => $product->total_quantity,
                'total_revenue' => $this->currencyService->format($product->total_revenue),
                'total_revenue_raw' => $product->total_revenue,
                'total_orders' => $product->total_orders,
                'image' => $product->getFirstMediaUrl(SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE()) ?: null,
            ];
        })->toArray();
    }

    /**
     * Get top delivery boys based on delivered parcels for the specified number of days.
     */
    public function getTopDeliveryBoys(int $days = 7, int $limit = 10): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $topDeliveryBoys = DeliveryBoy::select('delivery_boys.*')->with('user')
            ->selectRaw('COUNT(order_items.id) as total_deliveries')
            ->selectRaw('SUM(order_items.subtotal) as total_revenue')
            ->join('delivery_boy_assignments', 'delivery_boys.id', '=', 'delivery_boy_assignments.delivery_boy_id')
            ->join('orders', 'delivery_boy_assignments.order_id', '=', 'orders.id')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.status', OrderItemStatusEnum::DELIVERED())
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->where('delivery_boys.verification_status', DeliveryBoyVerificationStatusEnum::VERIFIED())
            ->where('delivery_boys.status', ActiveInactiveStatusEnum::ACTIVE())
            ->groupBy('delivery_boys.id')
            ->orderBy('total_deliveries', 'desc')
            ->limit($limit)
            ->get();

        return $topDeliveryBoys->map(function ($deliveryBoy) {
            if ($deliveryBoy->user) {
                return [
                    'id' => $deliveryBoy->id,
                    'name' => $deliveryBoy->user->name,
                    'email' => $deliveryBoy->user->email,
                    'phone' => $deliveryBoy->user->phone,
                    'total_deliveries' => $deliveryBoy->total_deliveries,
                    'total_revenue' => $this->currencyService->format($deliveryBoy->total_revenue),
                    'total_revenue_raw' => $deliveryBoy->total_revenue,
                    'avatar' => $deliveryBoy->getFirstMediaUrl(SpatieMediaCollectionName::PROFILE_IMAGE()) ?: null,
                ];
            }
        })->toArray();
    }

    /**
     * Get categories with filters and sorting options.
     */
    public function getCategoriesWithFilters(string $sortBy = 'name', string $filterBy = 'all'): array
    {
        $query = Category::withCount(['products' => function ($query) {
            $query->where('status', ProductStatusEnum::ACTIVE());
        }]);

        // Apply filters
        switch ($filterBy) {
            case 'top_selling':
                $query->whereHas('products.orderItems', function ($query) {
                    $query->where('status', OrderItemStatusEnum::DELIVERED());
                })
                    ->withCount(['products as total_sold' => function ($query) {
                        $query->join('order_items', 'products.id', '=', 'order_items.product_id')
                            ->where('order_items.status', OrderItemStatusEnum::DELIVERED())
                            ->selectRaw('SUM(order_items.quantity)');
                    }]);
                break;
            case 'no_products':
                $query->having('products_count', '=', 0);
                break;
            default:
                // Show all categories
                break;
        }

        // Apply sorting
        switch ($sortBy) {
            case 'products_count':
                $query->orderBy('products_count', 'desc');
                break;
            case 'total_sold':
                if ($filterBy === 'top_selling') {
                    $query->orderBy('total_sold', 'desc');
                }
                break;
            case 'name':
            default:
                $query->orderBy('title', 'asc');
                break;
        }

        $categories = $query->limit(12)->get();

        return $categories->map(function ($category) use ($filterBy) {
            $data = [
                'id' => $category->id,
                'title' => $category->title,
                'products_count' => $category->products_count,
                'image' => $category->getFirstMediaUrl('image') ?: null,
            ];

            if ($filterBy === 'top_selling') {
                $data['total_sold'] = $category->total_sold ?? 0;
            }

            return $data;
        })->toArray();
    }

    /**
     * Get enhanced commission data with currency and filters.
     */
    public function getEnhancedCommissionsData(int $days = 30, string $type = 'all'): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $query = SellerOrderItem::join('order_items', 'seller_order_items.order_item_id', '=', 'order_items.id')
            ->where('order_items.status', OrderItemStatusEnum::DELIVERED())
            ->whereBetween('order_items.created_at', [$startDate, $endDate]);

        // Apply type filter
        if ($type !== 'all') {
            if ($type === 'high_commission') {
                $query->where('order_items.admin_commission_amount', '>', 100);
            } elseif ($type === 'low_commission') {
                $query->where('order_items.admin_commission_amount', '<=', 100);
            }
        }

        $commissions = $query->select(
            DB::raw('SUM(order_items.admin_commission_amount) as total_commission'),
            DB::raw('COUNT(seller_order_items.id) as total_orders'),
            DB::raw('AVG(order_items.admin_commission_amount) as avg_commission')
        )->first();

        // Get daily commission data for chart
        $dailyCommissionsRaw = SellerOrderItem::join('order_items', 'seller_order_items.order_item_id', '=', 'order_items.id')
            ->where('order_items.status', OrderItemStatusEnum::DELIVERED())
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(order_items.created_at) as date'),
                DB::raw('SUM(order_items.admin_commission_amount) as daily_commission')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('daily_commission', 'date'); // 👈 key-value pair for easy lookup

// Generate all dates between start and end
        $period = CarbonPeriod::create($startDate, $endDate);

// Map over the entire range
        $dailyCommissions = collect($period)->map(function ($date) use ($dailyCommissionsRaw) {
            $dateStr = $date->format('Y-m-d');
            $commission = $dailyCommissionsRaw[$dateStr] ?? 0;

            return [
                'date' => $dateStr,
                'commission' => $commission,
                'formatted_commission' => app(\App\Services\CurrencyService::class)->format($commission),
            ];
        });

        return [
            'total_commission' => $this->currencyService->format($commissions->total_commission ?? 0),
            'total_commission_raw' => $commissions->total_commission ?? 0,
            'total_orders' => $commissions->total_orders ?? 0,
            'avg_commission' => $this->currencyService->format($commissions->avg_commission ?? 0),
            'avg_commission_raw' => $commissions->avg_commission ?? 0,
            'daily_data' => $dailyCommissions->toArray(),
            'period' => $days,
            'currency_symbol' => $this->currencyService->getSymbol() ?? '$'
        ];
    }
}
