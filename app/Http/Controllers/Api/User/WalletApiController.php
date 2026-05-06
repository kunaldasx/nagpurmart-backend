<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Wallet\PrepareWalletRechargeRequest;
use App\Http\Requests\User\Wallet\DeductWalletBalanceRequest;
use App\Http\Resources\User\WalletResource;
use App\Http\Resources\User\WalletTransactionResource;
use App\Services\WalletService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

#[Group('Wallet')]
class WalletApiController extends Controller
{
    /**
     * @var WalletService
     */
    protected WalletService $walletService;

    /**
     * WalletApiController constructor.
     *
     * @param WalletService $walletService
     */
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get user wallet balance
     *
     * @return JsonResponse
     */
    public function getWallet(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.user_not_authenticated',
                []
            );
        }

        $result = $this->walletService->getWallet($user->id);

        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(
                false,
                $result['message'],
                $result['data']
            );
        }

        return ApiResponseType::sendJsonResponse(
            true,
            $result['message'],
            new WalletResource($result['data'])
        );
    }

    /**
     * Prepare wallet recharge
     *
     * @param PrepareWalletRechargeRequest $request
     * @return JsonResponse
     */
    public function prepareWalletRecharge(PrepareWalletRechargeRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.user_not_authenticated',
                []
            );
        }

        $data = $request->validated();
        $result = $this->walletService->prepareWalletRecharge($user->id, $data);

        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(
                false,
                $result['message'],
                $result['data']
            );
        }

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: $result['message'],
            data: [
                'wallet' => new WalletResource($result['data']['wallet']),
                'transaction' => new WalletTransactionResource($result['data']['transaction']),
                'payment_response' => $result['data']['payment_response'],
            ]
        );
    }

    /**
     * Deduct balance from the user wallet
     *
     * @param DeductWalletBalanceRequest $request
     * @return JsonResponse
     */
    public function deductBalance(DeductWalletBalanceRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.user_not_authenticated',
                []
            );
        }

        $data = $request->validated();
        $result = $this->walletService->deductBalance($user->id, $data);

        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(
                false,
                $result['message'],
                $result['data']
            );
        }

        return ApiResponseType::sendJsonResponse(
            true,
            $result['message'],
            [
                'wallet' => new WalletResource($result['data']['wallet']),
                'transaction' => new WalletTransactionResource($result['data']['transaction'])
            ]
        );
    }

    /**
     * Get wallet transactions
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of Orders Per Page', type: 'int', default: 1, example: 1)]
    public function getTransactions(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.user_not_authenticated',
                []
            );
        }

        $filters = $request->all();
        $result = $this->walletService->getTransactions($user->id, $filters);

        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(
                false,
                $result['message'],
                $result['data']
            );
        }

        // Transform the paginated data using WalletTransactionResource
        $transactions = $result['data'];
        $transactions->getCollection()->transform(function ($transaction) {
            return new WalletTransactionResource($transaction);
        });

        return ApiResponseType::sendJsonResponse(
            true,
            $result['message'],
            [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'data' => $transactions->items(),
            ]
        );
    }


    /**
     * Get single transaction details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getTransaction(int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.user_not_authenticated',
                []
            );
        }

        $result = $this->walletService->getTransaction($user->id, $id);

        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(
                false,
                $result['message'],
                $result['data']
            );
        }

        return ApiResponseType::sendJsonResponse(
            true,
            $result['message'],
            new WalletTransactionResource($result['data'])
        );
    }
}
