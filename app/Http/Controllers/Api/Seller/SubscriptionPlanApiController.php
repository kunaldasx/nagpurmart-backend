<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\SellerPermissionEnum;
use App\Enums\SettingTypeEnum;
use App\Enums\Subscription\SellerSubscriptionStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\SubscriptionPlanBuyRequest;
use App\Http\Resources\Subscription\SubscriptionPlanResource;
use App\Http\Resources\Subscription\SellerSubscriptionResource;
use App\Models\SubscriptionPlan;
use App\Models\SellerSubscription;
use App\Services\SettingService;
use App\Services\SellerSubscriptionService;
use App\Services\SubscriptionUsageService;
use App\Traits\ChecksPermissions;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

#[Group('Seller Subscription Plans')]
class SubscriptionPlanApiController extends Controller
{
    use ChecksPermissions;

    protected bool $viewPermission = false;
    protected bool $buyPermission = false;

    public function __construct()
    {
        $user = auth()->user();
        $isDefaultSeller = $user?->hasRole(DefaultSystemRolesEnum::SELLER()) ?? false;

        $this->viewPermission = $this->hasPermission(SellerPermissionEnum::SUBSCRIPTION_VIEW()) || $isDefaultSeller;
        $this->buyPermission = $this->hasPermission(SellerPermissionEnum::SUBSCRIPTION_BUY()) || $isDefaultSeller;
    }

    /**
     * Get active subscription plans for sellers with subscription settings.
     */
    public function index(SettingService $settingService): JsonResponse
    {
        $plans = SubscriptionPlan::with('limits')
            ->where('status', true)
            ->orderByDesc('is_default')
            ->orderBy('price')
            ->get();

        $plansResource = SubscriptionPlanResource::collection($plans);

        // Include subscription page settings (heading/description)
        $subscriptionSettings = $settingService->getSettingByVariable(SettingTypeEnum::SUBSCRIPTION());

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.subscription_plans_fetched_successfully'),
            data: [
                'plans' => $plansResource,
                'settings' => $subscriptionSettings->value,
            ]
        );
    }


    /**
     * Check subscription plan eligibility BEFORE purchase.
     */
    public function checkEligibility(Request $request, SubscriptionUsageService $usageService): JsonResponse
    {
        if (!$this->viewPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        $user = auth()->user();
        $seller = $user?->seller();
        $sellerId = $seller?->id;
        if (empty($sellerId)) {
            return ApiResponseType::sendJsonResponse(false, 'labels.unauthorized_access', status: 401);
        }

        $data = $request->validate([
            'plan_id' => 'required|integer|exists:subscription_plans,id',
        ]);

        $result = $usageService->checkEligibilityForPlan((int)$sellerId, (int)$data['plan_id']);

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
     * Buy a subscription plan for the authenticated seller.
     */
    public function buy(SubscriptionPlanBuyRequest $request, SellerSubscriptionService $service): JsonResponse
    {
        if (!$this->buyPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        try {

            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, 'labels.unauthorized_access', status: 401);
            }

            $data = $request->validated();

            $result = $service->buyPlanForSeller(
                (int)$seller->id,
                (int)($seller->user_id ?? $user->id),
                (int)$data['plan_id'],
                (string)$data['payment_type'],
            );

            $success = (bool)($result['success'] ?? false);
            return ApiResponseType::sendJsonResponse(
                success: $success,
                message: $result['message'] ?? ($success ? 'labels.subscription_purchased' : 'labels.something_went_wrong'),
                data: $result['data'] ?? [],
                status: $success ? 200 : 422
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage());
        }
    }

    /**
     * Get the current subscription for the seller
     */
    public function current(): JsonResponse
    {
        if (!$this->viewPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, 'labels.unauthorized_access', status: 401);
        }

        $subscription = SellerSubscription::with(['plan.limits', 'snapshot', 'transactions'])
            ->where('seller_id', $seller->id)
            ->whereIn('status', [
                SellerSubscriptionStatusEnum::ACTIVE(),
                SellerSubscriptionStatusEnum::PENDING(),
            ])
            ->orderByDesc('id')
            ->first();

        if (!$subscription) {
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.no_active_subscription_found',
            );
        }

        return ApiResponseType::sendJsonResponse(true, 'labels.subscription_fetched_successfully', new SellerSubscriptionResource($subscription));
    }

    /**
     * Get a specific subscription status for the seller
     */
    public function status(int $id): JsonResponse
    {
        if (!$this->viewPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, 'labels.unauthorized_access', status: 401);
        }

        $subscription = SellerSubscription::with(['plan.limits', 'snapshot', 'transactions'])
            ->where('seller_id', $seller->id)
            ->where('id', $id)
            ->first();

        if (!$subscription) {
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.no_active_subscription_found',
            );
        }

        return ApiResponseType::sendJsonResponse(true, 'labels.subscription_fetched_successfully', new SellerSubscriptionResource($subscription));
    }

    /**
     * List subscription history for the seller
     */
    /**
     * List subscription history for the seller with pagination
     */
    #[QueryParameter('page', description: 'Page number for pagination', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page', type: 'int', default: 15, example: 15)]
    public function history(Request $request): JsonResponse
    {
        if (!$this->viewPermission) {
            return $this->unauthorizedResponse(__('labels.permission_denied'));
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, 'labels.unauthorized_access', status: 401);
        }

        $perPage = $request->integer('per_page', 15);

        $paginator = SellerSubscription::with(['plan.limits', 'snapshot', 'transactions'])
            ->where('seller_id', $seller->id)
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($item) use ($request) {
            return (new SellerSubscriptionResource($item))->toArray($request);
        });

        $response = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $paginator->items(),
        ];

        return ApiResponseType::sendJsonResponse(true, 'labels.subscription_history_fetched_successfully', $response);
    }
}
