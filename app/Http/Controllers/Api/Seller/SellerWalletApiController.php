<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\WalletResource;
use App\Http\Resources\User\WalletTransactionResource;
use App\Models\Wallet;
use App\Services\WalletService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Seller Wallet')]
class SellerWalletApiController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected WalletService $walletService)
    {
    }

    /**
     * Get authenticated seller wallet balance/details
     */
    public function show(): JsonResponse
    {
        try {
            $this->authorize('viewAny', Wallet::class);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied') ?? __('messages.unauthorized_action'), [], 403);
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found') ?? 'Seller not found', null, 404);
        }

        $result = $this->walletService->getWallet($seller->user_id ?? $seller->user->id);
        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(false, $result['message'], $result['data'] ?? []);
        }

        return ApiResponseType::sendJsonResponse(true, $result['message'] ?? __('labels.wallet_retrieved_successfully'), new WalletResource($result['data']));
    }

    /**
     * List seller wallet transactions
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of transactions per page', type: 'int', default: 15, example: 15)]
    public function transactions(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Wallet::class);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied') ?? __('messages.unauthorized_action'), [], 403);
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found') ?? 'Seller not found', null, 404);
        }

        $filters = $request->all();
        $result = $this->walletService->getTransactions($seller->user_id ?? $seller->user->id, $filters);
        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(false, $result['message'], $result['data'] ?? []);
        }

        $transactions = $result['data'];
        $transactions->getCollection()->transform(function ($transaction) {
            return new WalletTransactionResource($transaction);
        });

        return ApiResponseType::sendJsonResponse(true, $result['message'] ?? __('labels.transactions_retrieved_successfully'), [
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
            'data' => $transactions->items(),
        ]);
    }

    /**
     * Get single transaction details
     */
    public function transaction(int $id): JsonResponse
    {
        try {
            $this->authorize('viewAny', Wallet::class);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied') ?? __('messages.unauthorized_action'), [], 403);
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found') ?? 'Seller not found', null, 404);
        }

        $result = $this->walletService->getTransaction($seller->user_id ?? $seller->user->id, $id);
        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(false, $result['message'], $result['data'] ?? []);
        }

        return ApiResponseType::sendJsonResponse(true, $result['message'] ?? __('labels.transaction_retrieved_successfully'), new WalletTransactionResource($result['data']));
    }
}
