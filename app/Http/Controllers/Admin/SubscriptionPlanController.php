<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\SubscriptionPlanRequest;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanLimit;
use App\Services\SettingService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Enums\Subscription\SubscriptionPlanKeyEnum;
use App\Models\SellerSubscription;
use App\Traits\ChecksPermissions;
use Illuminate\Support\Facades\Lang;
use App\Services\SubscriptionUsageService;
use Illuminate\Support\Facades\Cache;

class SubscriptionPlanController extends Controller
{
    use ChecksPermissions;

    protected mixed $systemSettings;
    protected bool $subscriptionEnabled = false;
    protected bool $viewPermission = false;
    protected bool $createPermission = false;
    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $subscriberViewPermission = false;

    public function __construct()
    {
        $settingService = app(SettingService::class);
        $resource = $settingService->getSettingByVariable('system');
        $this->systemSettings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];

        $this->subscriptionEnabled = Setting::isSubscriptionEnabled();
        $this->viewPermission = $this->hasPermission(AdminPermissionEnum::SUBSCRIPTION_PLAN_VIEW());
        $this->createPermission = $this->hasPermission(AdminPermissionEnum::SUBSCRIPTION_PLAN_CREATE());
        $this->editPermission = $this->hasPermission(AdminPermissionEnum::SUBSCRIPTION_PLAN_EDIT());
        $this->deletePermission = $this->hasPermission(AdminPermissionEnum::SUBSCRIPTION_PLAN_DELETE());
        $this->subscriberViewPermission = $this->hasPermission(AdminPermissionEnum::SUBSCRIPTION_SUBSCRIBER_VIEW());

        if (Setting::isSystemVendorTypeSingle()) {
            redirect()->route('seller.dashboard')->send();
            exit;
        }
    }

    protected function ensureAllowed(bool $allowed): void
    {
        abort_unless($allowed, 403, __('labels.permission_denied'));
    }

    /**
     * Show a single subscription purchase detail and configuration usage
     */
    public function subscriberShow(int $id, SubscriptionUsageService $usageService): View
    {
        $this->ensureAllowed($this->subscriberViewPermission);

        $subscription = SellerSubscription::with(['seller.user', 'plan.limits', 'snapshot', 'transactions'])
            ->findOrFail($id);

        // Build plan limits map (prefer snapshot if available, else plan->limits)
        $limitMap = [];
        if ($subscription->snapshot && is_array($subscription->snapshot->limits_json)) {
            $limitMap = $subscription->snapshot->limits_json;
        } else {
            $limitMap = $subscription->plan?->limits?->pluck('value', 'key')->toArray() ?? [];
        }

        // Build usage map for the seller
        $usage = [];
        foreach (\App\Enums\Subscription\SubscriptionPlanKeyEnum::values() as $key) {
            $used = $usageService->getUsage((int)$subscription->seller_id, $key);
            $limit = $limitMap[$key] ?? null; // null => unlimited
            $remaining = $limit === null ? null : max(0, ((int)$limit - (int)$used));
            $usage[$key] = [
                'limit' => $limit,
                'used' => (int)$used,
                'remaining' => $remaining,
            ];
        }

        return view('admin.subscription-plans.subscriber-show', [
            'subscription' => $subscription,
            'usage' => $usage,
            'currencySymbol' => $this->systemSettings['currencySymbol'] ?? '$',
        ]);
    }

    public function index(): View
    {
        $this->ensureAllowed($this->viewPermission);

        // Define datatable columns similar to other admin modules
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'name', 'name' => 'name', 'title' => __('labels.name')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'config', 'name' => 'config', 'title' => __('labels.plan_configurations')],
            ['data' => 'subscribers_count', 'name' => 'subscribers_count', 'title' => __('labels.subscribers_count')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        // Fetch active plans to render pricing component dynamically
        $plans = SubscriptionPlan::with('limits')
            ->where('status', true)
            ->orderByDesc('is_default')
            ->orderBy('price')
            ->get();


        $settingService = app(SettingService::class);
        $resource = $settingService->getSettingByVariable(SettingTypeEnum::SUBSCRIPTION());
        $subscriptionSettings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
        $subscriptionEnabled = $this->subscriptionEnabled;
        $createPermission = $this->createPermission;
        $editPermission = $this->editPermission;
        $subscriberViewPermission = $this->subscriberViewPermission;

        return view('admin.subscription-plans.index', compact(
            'columns',
            'plans',
            'subscriptionSettings',
            'subscriptionEnabled',
            'createPermission',
            'editPermission',
            'subscriberViewPermission'
        ));
    }

    public function create(): View
    {
        $this->ensureAllowed($this->createPermission);

        $plan = new SubscriptionPlan();
        return view('admin.subscription-plans.form', compact('plan'));
    }

    public function store(SubscriptionPlanRequest $request): JsonResponse
    {
        if (!$this->createPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        try {
            $data = $request->validated();

            // Never allow setting is_default from form
            unset($data['is_default']);

            DB::beginTransaction();

            $plan = SubscriptionPlan::create($data);

            // Collect limits from form inputs based on enum keys
            $limits = [];
            foreach (SubscriptionPlanKeyEnum::values() as $key) {
                $field = $key . '_value';
                $value = $request->input($field);
                $limits[] = [
                    'plan_id' => $plan->id,
                    'key' => $key,
                    'value' => !empty($value) ? (int)$value : $value,
                ];
            }

            if (!empty($limits)) {
                SubscriptionPlanLimit::insert($limits);
            }

            DB::commit();

            // Invalidate caches related to subscription plans
            Cache::forget('subscription:plans:active:list');
            Cache::forget("subscription:plan:{$plan->id}");

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.subscription_plan_created_successfully'),
                data: [
                    'redirect_url' => route('admin.subscription-plans.index'),
                    'id' => $plan->id,
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong') . ' ' . $e->getMessage(),
                data: []
            );
        }
    }

    public function edit(Request $request, int $id): View|JsonResponse
    {
        $this->ensureAllowed($this->editPermission);

        $plan = SubscriptionPlan::with('limits')->findOrFail($id);

        return view('admin.subscription-plans.form', compact('plan'));
    }

    public function update(SubscriptionPlanRequest $request, int $id): JsonResponse
    {
        if (!$this->editPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        try {
            $data = $request->validated();
            $plan = SubscriptionPlan::findOrFail($id);
            unset($data['is_default']);

            DB::beginTransaction();

            $plan->update($data);

            // Sync limits: simple approach - delete then recreate from request
            $plan->limits()->delete();

            $limits = [];
            foreach (SubscriptionPlanKeyEnum::values() as $key) {
                $field = $key . '_value';
                $value = $request->input($field);
                $limits[] = [
                    'plan_id' => $plan->id,
                    'key' => $key,
                    'value' => !empty($value) ? (int)$value : $value,
                ];
            }

            if (!empty($limits)) {
                SubscriptionPlanLimit::insert($limits);
            }

            DB::commit();

            // Invalidate caches related to subscription plans
            Cache::forget('subscription:plans:active:list');
            Cache::forget("subscription:plan:{$plan->id}");

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.subscription_plan_updated_successfully'),
                data: [
                    'redirect_url' => route('admin.subscription-plans.index'),
                    'id' => $plan->id,
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong') . ' ' . $e->getMessage(),
                data: []
            );
        }
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->deletePermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        $plan = SubscriptionPlan::with('subscriptions')->findOrFail($id);
        if ($plan->is_default) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.default_plan_cannot_be_deleted'),
                data: []
            );
        }

        if ($plan->subscriptions()->exists()) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.subscription_plan_has_active_subscription'),
                data: []
            );
        }

        $plan->delete();

        // Invalidate caches related to subscription plans
        Cache::forget('subscription:plans:active:list');
        Cache::forget("subscription:plan:{$id}");

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.subscription_plan_deleted_successfully'),
            data: [
                'redirect_url' => route('admin.subscription-plans.index'),
            ]
        );
    }

    /**
     * Datatable endpoint for subscription plans
     */
    public function datatable(Request $request): JsonResponse
    {
        if (!$this->viewPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        $draw = (int)$request->input('draw');
        $start = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 10);

        $searchValue = $request->input('search.value');

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'asc');

        $columns = ['id', 'name', 'price', 'duration_type', 'status', 'is_default', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = SubscriptionPlan::query()->with('limits', 'subscriptions')
            ->select([
                'id',
                'name',
                'description',
                'price',
                'duration_type',
                'duration_days',
                'status',
                'is_default',
                'is_free',
                'is_recommended'
            ])
            ->when($searchValue, function ($q) use ($searchValue) {
                $q->where(function ($sub) use ($searchValue) {
                    $sub->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('description', 'like', "%{$searchValue}%");
                });
            });

        $totalRecords = SubscriptionPlan::count();
        $filteredRecords = $query->count();

        $plans = $query
            ->orderBy($orderColumn, $orderDirection)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $plans->map(function ($plan) {

            $duration = $plan->duration_type === 'unlimited'
                ? __('labels.unlimited')
                : $plan->duration_days . ' ' . __('labels.days');

            $limitMap = $plan->limits?->pluck('value', 'key')->toArray() ?? [];

            $configHtml = '<ol class="">';
            foreach (SubscriptionPlanKeyEnum::values() as $key) {
                $configHtml .= '<li class="">' . e(Str::replace("_", " ", $key)) . ': ' . e($limitMap[$key] ?? __('labels.unlimited')) . '</li>';
            }
            $configHtml .= '</ol>';

            $price = $plan->is_free
                ? __('labels.is_free')
                : $this->systemSettings['currencySymbol'] . ' ' . number_format($plan->price, 2);

            $statusHtml = view('partials.status', [
                'status' => $plan->status ? 'active' : 'inactive'
            ])->render();

            $recommendedHtml = $plan->is_recommended
                ? '<span class="badge bg-info-lt fs-6">' . e(__('labels.recommended')) . '</span>'
                : '';

            $actionHtml = view('partials.actions', [
                'modelName' => 'subscription-plan',
                'id' => $plan->id,
                'title' => $plan->name,
                'mode' => 'route_view',
                'route' => route('admin.subscription-plans.edit', $plan->id),
                'editPermission' => $this->editPermission,
                'deletePermission' => $this->deletePermission && !$plan->is_default,
            ])->render();

            $subscribersCount = $plan->subscriptions->count();
            $subscriberLink = $this->subscriberViewPermission
                ? "<a href='" . route('admin.subscription-plans.subscribers', ['plan_id' => $plan->id]) . "'>{$subscribersCount}</a>"
                : (string)$subscribersCount;

            return [
                'id' => $plan->id,
                'name' => "
                <div class='d-flex justify-content-start align-items-center'>
                    <div>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.name') . ": {$plan->name}</p>
                        <p class='m-0'>{$price}</p>
                        <p class='m-0'>" . __('labels.duration') . ": {$duration}</p>
                        <p class='m-0'>{$recommendedHtml}</p>
                        " . ($plan->is_default ? "<p class='m-0'>" . __('labels.default_plan') . "</p>" : "") . "
                    </div>
                </div>
            ",
                'status' => $statusHtml,
                'config' => $configHtml,
                'subscribers_count' => $subscriberLink,
                'action' => $actionHtml,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Show subscribers (sellers who purchased any subscription plan)
     */
    public function subscribers(Request $request): View
    {
        $this->ensureAllowed($this->subscriberViewPermission);

        $planId = $request->input('plan_id');
        $sellerId = $request->input('seller_id');
        $status = $request->input('status');
        $plans = SubscriptionPlan::select('id', 'name')
            ->get()
            ->map(function ($plan) use ($planId) {
                $plan->is_selected = $plan->id === (int)$planId;
                return $plan;
            });
        // Pre-resolve selected seller for TomSelect prefill (if passed via URL)
        $selectedSeller = null;
        if (!empty($sellerId)) {
            $selectedSeller = \App\Models\Seller::with('user:id,name,email')
                ->select('id', 'user_id')
                ->find($sellerId);
        }
        $statuses = \App\Enums\Subscription\SellerSubscriptionStatusEnum::values();
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'seller', 'name' => 'seller', 'title' => __('labels.seller')],
            ['data' => 'plan', 'name' => 'plan', 'title' => __('labels.plan')],
            ['data' => 'price_paid', 'name' => 'price_paid', 'title' => __('labels.price_paid')],
            ['data' => 'period', 'name' => 'period', 'title' => __('labels.period')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
        ];

        return view('admin.subscription-plans.subscribers', compact('columns', 'planId', 'plans', 'statuses', 'sellerId', 'status', 'selectedSeller'));
    }

    /**
     * Datatable endpoint for subscribers list
     */
    public function subscribersDatatable(Request $request): JsonResponse
    {
        if (!$this->subscriberViewPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        $draw = (int)$request->input('draw');
        $start = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 10);
        $searchValue = $request->input('search.value');
        $planId = $request->input('plan_id');
        $sellerId = $request->input('seller_id');
        $status = $request->input('status');

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');

        $columns = ['id', 'seller', 'plan', 'price_paid', 'start_date', 'status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        // Base query with relations
        $query = SellerSubscription::query()
            ->with(['seller.user', 'plan'])
            ->select(['id', 'seller_id', 'plan_id', 'status', 'start_date', 'end_date', 'price_paid', 'created_at']);

        if (!empty($planId)) {
            $query->where('plan_id', (int)$planId);
        }
        if (!empty($sellerId)) {
            $query->where('seller_id', (int)$sellerId);
        }
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Searching on seller name/email or plan name
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('seller.user', function ($u) use ($searchValue) {
                    $u->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%");
                })->orWhereHas('plan', function ($p) use ($searchValue) {
                    $p->where('name', 'like', "%{$searchValue}%");
                });
            });
        }

        $totalRecords = SellerSubscription::count();
        $filteredRecords = $query->count();

        // Sorting - for computed columns map safely
        if (in_array($orderColumn, ['id', 'price_paid', 'start_date', 'created_at'])) {
            $query->orderBy($orderColumn, $orderDirection);
        } else {
            // Fallback
            $query->orderBy('id', 'desc');
        }

        $items = $query->offset($start)->limit($length)->get();

        $currency = $this->systemSettings['currencySymbol'] ?? '$';

        $data = $items->map(function (SellerSubscription $sub) use ($currency) {
            $sellerName = $sub->seller?->user?->name ?? ('#' . $sub->seller_id);
            $sellerEmail = $sub->seller?->user?->email ?? '';
            $planName = $sub->plan?->name ?? '';

            $statusHtml = view('partials.status', [
                'status' => (string)$sub->status,
            ])->render();

            return [
                'id' => $sub->id,
                'seller' => "<div><div class='fw-medium'><a href='" . e(route('admin.subscription-plans.subscribers.show', $sub->id)) . "' class='text-primary'>{$sellerName}</a></div><div class='text-muted'>{$sellerEmail}</div></div>",
                'plan' => e($planName),
                'price_paid' => $currency . ' ' . number_format((float)$sub->price_paid, 2),
                'period' => ($sub->start_date?->format('Y-m-d') ?? '-') . ' / ' . ($sub->end_date?->format('Y-m-d') ?? Lang::get('labels.unlimited')),
                'status' => $statusHtml,
                'created_at' => $sub->created_at?->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }
}
