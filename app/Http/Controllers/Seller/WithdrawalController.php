<?php

namespace App\Http\Controllers\Seller;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Seller\SellerWithdrawalStatusEnum;
use App\Enums\SellerPermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\CurrencyService;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $requestWithdrawPermission = false;

    protected WithdrawalService $withdrawalService;
    protected WalletService $walletService;

    protected CurrencyService $currencyService;

    public function __construct(WithdrawalService $withdrawalService, WalletService $walletService, CurrencyService $currencyService)
    {
        $user = auth()->user();
        $this->withdrawalService = $withdrawalService;
        $this->walletService = $walletService;
        $this->currencyService = $currencyService;
        $this->requestWithdrawPermission = $this->hasPermission(SellerPermissionEnum::WITHDRAWAL_REQUEST()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
    }

    /**
     * Display the withdrawal request page.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewWithdrawal', Wallet::class);

        $user = $request->user();
        $seller = $user->seller();

        // Get the wallet
        $walletResult = $this->walletService->getWallet($seller->user->id);
        $wallet = $walletResult['success'] ? $walletResult['data'] : null;

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'amount', 'name' => 'amount', 'title' => __('labels.amount')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'request_note', 'name' => 'request_note', 'title' => __('labels.request_note')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
        ];

        $requestWithdrawPermission = $this->requestWithdrawPermission;
        return view('seller.withdrawals.index', compact('wallet', 'seller', 'columns', 'requestWithdrawPermission'));
    }

    /**
     * Store a new withdrawal request.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('requestWithdrawal', Wallet::class);

        // Validate the request
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:500',
        ]);

        // Get the authenticated seller
        $seller = $request->user()->seller();

        // Create the withdrawal request
        $result = $this->withdrawalService->createWithdrawalRequest(
            $seller->id,
            [
                'amount' => $validated['amount'],
                'note' => $validated['note'] ?? null,
            ],
            'seller'
        );
        return ApiResponseType::sendJsonResponse(
            success: $result['success'],
            message: $result['message'],
            data: $result['data']
        );
    }

    /**
     * Display the withdrawal history page.
     */
    public function history(): View
    {
        $this->authorize('viewWithdrawal', Wallet::class);

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'amount', 'name' => 'amount', 'title' => __('labels.amount')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'request_note', 'name' => 'request_note', 'title' => __('labels.request_note')],
            ['data' => 'admin_remark', 'name' => 'admin_remark', 'title' => __('labels.admin_remark')],
            ['data' => 'processed_at', 'name' => 'processed_at', 'title' => __('labels.processed_at')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
        ];

        return view('seller.withdrawals.history', compact('columns'));
    }

    /**
     * Get withdrawal requests data for DataTable
     */
    public function getWithdrawalRequests(Request $request): JsonResponse
    {
        $this->authorize('viewWithdrawal', Wallet::class);

        $seller = $request->user()->seller();
        if (empty($seller)) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.seller_not_found'),
                data: []
            );
        }
        // Prepare filters
        $filters = $request->only(['status', 'from_date', 'to_date', 'per_page', 'sort', 'order']);
        $filters['seller_id'] = $seller->id;
        $filters['status'] = SellerWithdrawalStatusEnum::PENDING();

        // Get the withdrawal requests
        $result = $this->withdrawalService->getWithdrawalRequests($filters, 'seller');

        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $result['message'],
                data: $result['data']
            );
        }

        $withdrawalRequests = $result['data'];

        // Format data for DataTable
        $data = $withdrawalRequests->map(function ($withdrawalRequest) {
            return [
                'id' => $withdrawalRequest->id,
                'amount' => $this->currencyService->format($withdrawalRequest->amount),
                'status' => view('partials.status', ['status' => $withdrawalRequest->status])->render(),
                'request_note' => $withdrawalRequest->request_note ?? 'N/A',
                'created_at' => $withdrawalRequest->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return response()->json([
            'draw' => intval($request->get('draw')),
            'recordsTotal' => $withdrawalRequests->total(),
            'recordsFiltered' => $withdrawalRequests->total(),
            'data' => $data,
        ]);
    }

    /**
     * Get withdrawal history data for DataTable
     */
    public function getWithdrawalHistory(Request $request): JsonResponse
    {
        $this->authorize('viewWithdrawal', Wallet::class);

        $seller = $request->user()->seller();
        if (empty($seller)) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.seller_not_found'),
                data: []
            );
        }

        // Prepare filters
        $filters = $request->only(['status', 'from_date', 'to_date', 'per_page', 'sort', 'order']);
        $filters['seller_id'] = $seller->id;
        $filters['status'] = [SellerWithdrawalStatusEnum::APPROVED(), SellerWithdrawalStatusEnum::REJECTED()];

        // Get the withdrawal requests
        $result = $this->withdrawalService->getWithdrawalRequests($filters, 'seller');

        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $result['message'],
                data: $result['data']
            );
        }

        $withdrawalRequests = $result['data'];

        // Format data for DataTable
        $data = $withdrawalRequests->map(function ($withdrawalRequest) {
            return [
                'id' => $withdrawalRequest->id,
                'amount' => $this->currencyService->format($withdrawalRequest->amount),
                'status' => view('partials.status', ['status' => $withdrawalRequest->status])->render(),
                'request_note' => $withdrawalRequest->request_note ?? 'N/A',
                'admin_remark' => $withdrawalRequest->admin_remark ?? 'N/A',
                'processed_at' => $withdrawalRequest->processed_at ? $withdrawalRequest->processed_at->format('Y-m-d H:i:s') : 'N/A',
                'created_at' => $withdrawalRequest->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return response()->json([
            'draw' => intval($request->get('draw')),
            'recordsTotal' => $withdrawalRequests->total(),
            'recordsFiltered' => $withdrawalRequests->total(),
            'data' => $data,
        ]);
    }
}
