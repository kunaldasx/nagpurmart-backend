<?php

namespace App\Http\Controllers\Api\DeliveryBoy;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ActiveDeliveryBoy;
use App\Http\Middleware\VerifiedDeliveryBoy;
use App\Services\WithdrawalService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

#[Group('DeliveryBoy Withdrawal')]
class DeliveryBoyWithdrawalApiController extends Controller
{
    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    protected WithdrawalService $withdrawalService;

    /**
     * Create a withdrawal request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createWithdrawalRequest(Request $request): JsonResponse
    {
        try {

            // Validate the request
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1',
                'note' => 'nullable|string|max:500',
            ]);

            // Get the authenticated delivery boy
            $deliveryBoy = $request->user()->deliveryBoy;

            // Create the withdrawal request
            $result = $this->withdrawalService->createWithdrawalRequest(
                $deliveryBoy->id,
                [
                    'amount' => $validated['amount'],
                    'note' => $validated['note'] ?? null,
                ]);

            return ApiResponseType::sendJsonResponse(
                success: $result['success'],
                message: $result['message'],
                data: $result['data']
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_error'),
                data: $e->errors()
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.error_occurred'),
                data: [],
            );
        }
    }

    /**
     * Get withdrawal requests for the authenticated delivery boy
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWithdrawalRequests(Request $request): JsonResponse
    {
        // Get the authenticated delivery boy
        $deliveryBoy = $request->user()->deliveryBoy;

        // Prepare filters
        $filters = $request->only(['status', 'from_date', 'to_date', 'per_page', 'sort', 'order']);
        $filters['delivery_boy_id'] = $deliveryBoy->id;

        // Get the withdrawal requests
        $result = $this->withdrawalService->getWithdrawalRequests($filters);

        return ApiResponseType::sendJsonResponse(
            success: $result['success'],
            message: $result['message'],
            data: $result['data']
        );
    }

    /**
     * Get a single withdrawal request
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getWithdrawalRequest(Request $request, int $id): JsonResponse
    {
        // Get the authenticated delivery boy
        $deliveryBoy = $request->user()->deliveryBoy;

        // Get the withdrawal request
        $result = $this->withdrawalService->getWithdrawalRequest($id);

        // Check if the request belongs to the authenticated delivery boy
        if ($result['success'] && $result['data']->delivery_boy_id !== $deliveryBoy->id) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.unauthorized_action'),
                data: []
            );
        }

        return ApiResponseType::sendJsonResponse(
            success: $result['success'],
            message: $result['message'],
            data: $result['data']
        );
    }
}
