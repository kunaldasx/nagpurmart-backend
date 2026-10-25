<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\DeliveryBoyOrderOfferService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DispatchCenterController extends Controller
{
    public function __construct(private DeliveryBoyOrderOfferService $dispatchService)
    {
    }

    public function index(): View
    {
        return view('seller.orders.dispatch-center', [
            'dataUrl' => route('seller.orders.dispatch-center.data'),
        ]);
    }

    public function data(): JsonResponse
    {
        $seller = Auth::user()?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), [], 404);
        }

        return ApiResponseType::sendJsonResponse(
            true,
            'Dispatch dashboard fetched successfully.',
            $this->dispatchService->sellerDispatchDashboard((int) $seller->id)
        );
    }

    public function selfDeliver(int $id): JsonResponse
    {
        $seller = Auth::user()?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), [], 404);
        }

        $result = $this->dispatchService->selfDeliver($id, (int) $seller->id);

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $result['success'] ? ['order_id' => $result['order_id']] : [],
            $result['status']
        );
    }
}
