<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\SellerOrderPopupOfferService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SellerOrderPopupDashboardController extends Controller
{
    public function __construct(private SellerOrderPopupOfferService $popupOfferService)
    {
    }

    public function index(): RedirectResponse
    {
        $seller = Auth::user()?->seller();
        abort_unless($seller && (int) $seller->user_id === (int) Auth::id(), 403);

        return redirect()->route('seller.orders.dispatch-center');
    }

    public function data(): JsonResponse
    {
        $seller = Auth::user()?->seller();
        if (!$seller || (int) $seller->user_id !== (int) Auth::id()) {
            return ApiResponseType::sendJsonResponse(false, __('messages.unauthorized_action'), [], 403);
        }

        return ApiResponseType::sendJsonResponse(
            true,
            'Seller order popup user availability fetched successfully.',
            $this->popupOfferService->popupUserDashboard($seller)
        );
    }
}
