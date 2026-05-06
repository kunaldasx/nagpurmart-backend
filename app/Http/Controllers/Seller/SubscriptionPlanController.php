<?php

namespace App\Http\Controllers\Seller;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\SubscriptionPlanBuyRequest;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTransaction;
use App\Services\SettingService;
use App\Services\SubscriptionUsageService;
use App\Services\SellerSubscriptionService;
use App\Models\SellerSubscription;
use App\Enums\Subscription\SellerSubscriptionStatusEnum;
use App\Enums\Payment\PaymentTypeEnum;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class SubscriptionPlanController extends Controller
{
    use PanelAware, ChecksPermissions;

    protected bool $subscriptionEnabled = false;
    protected bool $viewPermission = false;
    protected bool $buyPermission = false;

    public function __construct()
    {
        $this->subscriptionEnabled = Setting::isSubscriptionEnabled();
        $user = auth()->user();
        $isDefaultSeller = $user?->hasRole(DefaultSystemRolesEnum::SELLER()) ?? false;

        $this->viewPermission = $this->hasPermission(SellerPermissionEnum::SUBSCRIPTION_VIEW()) || $isDefaultSeller;
        $this->buyPermission = $this->hasPermission(SellerPermissionEnum::SUBSCRIPTION_BUY()) || $isDefaultSeller;

        if (Setting::isSystemVendorTypeSingle() || !Setting::isSubscriptionEnabled()) {
            redirect()->route('seller.dashboard')->send();
            exit;
        }
    }

    protected function ensureAllowed(bool $allowed): void
    {
        abort_unless($allowed, 403, __('labels.permission_denied'));
    }

    /**
     * List all active subscription plans for sellers.
     */
    public function index(SettingService $settingService): Factory|View
    {
        $this->ensureAllowed($this->viewPermission);

        $plans = Cache::remember('subscription:plans:active:list', now()->addMinutes(10), function () {
            return SubscriptionPlan::with('limits')
                ->where('status', true)
                ->orderByDesc('is_default')
                ->orderBy('price')
                ->get();
        });

        $subscriptionSettings = $settingService->getSettingByVariable(SettingTypeEnum::SUBSCRIPTION());

        return view('seller.subscription-plans.index', [
            'plans' => $plans,
            'subscriptionSettings' => $subscriptionSettings?->value ?? [],
            'panel' => $this->getPanel()
        ]);
    }

    /**
     * Show a subscription plan details page for seller with configuration and actions.
     */
    public function show(int $planId, SettingService $settingService): Factory|View|JsonResponse
    {
        $this->ensureAllowed($this->viewPermission);

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => __('labels.unauthorized_access')], 401);
        }
        $plan = Cache::remember("subscription:plan:{$planId}", now()->addMinutes(10), function () use ($planId) {
            return SubscriptionPlan::with('limits')->where('status', true)->findOrFail($planId);
        });
        $usageService = new SubscriptionUsageService();
        $eligible = $usageService->checkEligibilityForPlan((int)$seller->id, $planId);

        $subscriptionSettings = $settingService->getSettingByVariable(SettingTypeEnum::SUBSCRIPTION());

        return view('seller.subscription-plans.show', [
            'plan' => $plan,
            'subscriptionSettings' => $subscriptionSettings?->value ?? [],
            'eligible' => $eligible,
            'buyPermission' => $this->buyPermission,
        ]);
    }

    /**
     * Check plan eligibility for the authenticated seller (web route).
     */
    public function checkEligibility(Request $request, SubscriptionUsageService $usageService): JsonResponse
    {
        if (!$this->viewPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.unauthorized_access'),
                data: [],
            );
        }

        $data = $request->validate([
            'plan_id' => 'required|integer|exists:subscription_plans,id',
        ]);

        $result = $usageService->checkEligibilityForPlan((int)$seller->id, (int)$data['plan_id']);

        $message = $result['eligible']
            ? __('labels.subscription_plan_eligibility_ok')
            : __('labels.subscription_plan_eligibility_failed');

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: $message,
            data: $result
        );
    }

    /**
     * Buy a subscription plan (web route for seller panel AJAX via axios).
     */
    public function buy(SubscriptionPlanBuyRequest $request, SellerSubscriptionService $service): JsonResponse
    {
        if (!$this->buyPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.unauthorized_access'),
                data: [],
            );
        }

        $data = $request->validated();

        $result = $service->buyPlanForSeller(
            (int)$seller->id,
            (int)($seller->user_id ?? $user->id),
            (int)$data['plan_id'],
            (string)$data['payment_type'],
        );

        $success = (bool)($result['success'] ?? false);
        if (!empty($result['data']['payment_url'])) {
            $result['data']['redirect_url'] = $result['data']['payment_url'];
        }
        return ApiResponseType::sendJsonResponse(
            success: $success,
            message: $result['message'] ?? ($success ? __('labels.subscription_purchased') : __('labels.something_went_wrong')),
            data: $result['data'] ?? [],
        );
    }

    public function paymentStatus($transaction): Factory|View
    {
        $txn = SubscriptionTransaction::with('subscription')
            ->where('uuid', $transaction)
            ->firstOrFail();

        return view('payments.subscription-status', ['transaction' => $txn]);
    }

    /**
     * Skip the subscription prompt for the current session.
     */
    public function skip(): JsonResponse
    {
        session(['subscription_prompt_shown' => true]);

        return ApiResponseType::sendJsonResponse(true, __('labels.subscription_prompt_skipped'));
    }

    /**
     * Show current active/pending subscription for the authenticated seller.
     */
    public function current(SettingService $settingService): View|Factory
    {
        $this->ensureAllowed($this->viewPermission);

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            abort(401);
        }

        // Use cached helper to fetch current subscription
        $subscription = SellerSubscription::currentForSeller((int)$seller->id);

        $subscriptionSettings = $settingService->getSettingByVariable(SettingTypeEnum::SUBSCRIPTION());

        // Prepare usage details per configuration key for the current plan
        $usageDetails = [];
        if ($subscription) {
            $limitsMap = (array)($subscription->snapshot->limits_json ?? []);
            $usageService = new SubscriptionUsageService();

            foreach (\App\Enums\Subscription\SubscriptionPlanKeyEnum::values() as $key) {
                // In snapshot, missing key implies unlimited
                $limit = array_key_exists($key, $limitsMap) ? $limitsMap[$key] : null; // null => unlimited
                $used = (int)$usageService->getUsage((int)$seller->id, $key);

                if ($limit === null) {
                    $remaining = null; // unlimited
                } else {
                    $limitInt = (int)$limit;
                    $remaining = max(0, $limitInt - $used);
                    $limit = $limitInt;
                }

                $usageDetails[$key] = [
                    'limit' => $limit,
                    'used' => $used,
                    'remaining' => $remaining,
                ];
            }
        }

        return view('seller.subscription-plans.current', [
            'subscription' => $subscription,
            'subscriptionSettings' => $subscriptionSettings?->value ?? [],
            'usageDetails' => $usageDetails,
        ]);
    }

    /**
     * History page listing all subscriptions in a datatable for the seller.
     */
    public function history(): View|Factory
    {
        $this->ensureAllowed($this->viewPermission);

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'plan', 'name' => 'plan', 'title' => __('labels.plan')],
            ['data' => 'price_paid', 'name' => 'price_paid', 'title' => __('labels.price_paid')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'start_date', 'name' => 'start_date', 'title' => __('labels.start_date')],
            ['data' => 'end_date', 'name' => 'end_date', 'title' => __('labels.end_date')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
        ];

        return view('seller.subscription-plans.history', compact('columns'));
    }

    /**
     * Datatable JSON for seller subscription history.
     */
    public function historyDatatable(Request $request)
    {
        if (!$this->viewPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.unauthorized_access'));
        }

        $draw = $request->get('draw');
        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';
        $columns = ['id', 'plan', 'price_paid', 'status', 'start_date', 'end_date', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = SellerSubscription::with(['plan', 'transactions'])
            ->where('seller_id', $seller->id);

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%$searchValue%")
                    ->orWhere('price_paid', 'like', "%$searchValue%")
                    ->orWhere('status', 'like', "%$searchValue%")
                    ->orWhereHas('plan', function ($q2) use ($searchValue) {
                        $q2->where('name', 'like', "%$searchValue%");
                    });
            });
        }

        $totalRecords = SellerSubscription::where('seller_id', $seller->id)->count();
        $filteredRecords = $query->count();

        $data = $query
            ->orderBy($orderColumn === 'plan' ? 'plan_id' : $orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function (SellerSubscription $sub) {
                return [
                    'id' => $sub->id,
                    'plan' => $sub->plan?->name ?? '-',
                    'price_paid' => (string)($sub->price_paid ?? '0.00'),
                    'status' => view('partials.status', ['status' => (string)$sub->status])->render() . "<a class='mt-2' href='" . route('subscription-payment.status', ['transaction' => $sub->transactions?->first()->uuid]) . "'>check status</a>",
                    'start_date' => optional($sub->start_date)->format('Y-m-d') ?? '-',
                    'end_date' => optional($sub->end_date)->format('Y-m-d') ?? '-',
                    'created_at' => optional($sub->created_at)->format('Y-m-d') ?? '-',
                ];
            })
            ->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }
}
