<?php

namespace App\Http\Controllers\Api;

use App\Enums\Payment\PaymentTypeEnum;
use App\Http\Controllers\Controller;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Payments')]
class PaymentController extends Controller
{
    /**
     * Get available payment variables
     *
     * Retrieves the list of available payment types for the application.
     */
    public function paymentVariables(): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(
            success: true, message: 'labels.payment_variables_fetched_successfully', data: PaymentTypeEnum::values()
        );
    }
}
