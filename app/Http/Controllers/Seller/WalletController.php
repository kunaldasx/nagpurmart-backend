<?php

namespace App\Http\Controllers\Seller;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\CurrencyService;
use App\Services\WalletService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WalletController extends Controller
{
    use AuthorizesRequests, PanelAware, ChecksPermissions;

    protected WalletService $walletService;
    protected $seller;
    protected bool $requestWithdrawPermission = false;

    protected CurrencyService $currencyService;

    public function __construct(WalletService $walletService, CurrencyService $currencyService)
    {
        $user = auth()->user();
        $this->seller = $user->seller();
        $this->walletService = $walletService;
        $this->currencyService = $currencyService;
        $this->requestWithdrawPermission = $this->hasPermission(SellerPermissionEnum::WITHDRAWAL_REQUEST()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());

    }

    /**
     * Display the wallet balance page.
     */
    public function index(Request $request): View
    {
        // Authorize viewing wallet
        $this->authorize('viewAny', Wallet::class);

        $seller = $this->seller;
        // Get the wallet
        $walletResult = $this->walletService->getWallet($seller->user->id);
        $wallet = $walletResult['success'] ? $walletResult['data'] : null;
        $requestWithdrawPermission = $this->requestWithdrawPermission;

        return view('seller.wallet.index', compact('wallet', 'seller', 'requestWithdrawPermission'));
    }

    /**
     * Display the wallet transactions page.
     */
    public function transactions(): View
    {
        // Authorize viewing wallet transactions (same permission as wallet view)
        $this->authorize('viewAny', Wallet::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'amount', 'name' => 'amount', 'title' => __('labels.amount')],
            ['data' => 'transaction_type', 'name' => 'transaction_type', 'title' => __('labels.transaction_type')],
            ['data' => 'payment_method', 'name' => 'payment_method', 'title' => __('labels.payment_method')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'description', 'name' => 'description', 'title' => __('labels.description')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
        ];

        return view('seller.wallet.transactions', compact('columns'));
    }

    /**
     * Get wallet transactions data for DataTable
     */
    public function getTransactions(Request $request): JsonResponse
    {
        // Authorize viewing wallet transactions (JSON)
        $this->authorize('viewAny', Wallet::class);
        $user = $request->user();
        $seller = $user->seller();

        // Prepare filters
        $filters = $request->only(['query', 'transaction_type', 'status', 'payment_method', 'min_amount', 'max_amount', 'sort', 'order', 'per_page']);

        // Get the transactions
        $result = $this->walletService->getTransactions($seller->user->id, $filters);

        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $result['message'],
                data: $result['data']
            );
        }

        $transactions = $result['data'];

        // Format data for DataTable
        $data = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'amount' => $this->currencyService->format($transaction->amount),
                'transaction_type' => ucfirst(Str::replace("_", " ", $transaction->transaction_type)),
                'payment_method' => ucfirst(Str::replace("_", " ", $transaction->payment_method)),
                'status' => view('partials.status', ['status' => $transaction->status])->render(),
                'description' => $transaction->description ?? 'N/A',
                'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return response()->json([
            'draw' => intval($request->get('draw')),
            'recordsTotal' => $transactions->total(),
            'recordsFiltered' => $transactions->total(),
            'data' => $data,
        ]);
    }
}
