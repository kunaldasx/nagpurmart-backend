<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\Seller\SellerSettlementStatusEnum;
use App\Enums\Seller\SellerSettlementTypeEnum;
use App\Models\Seller;
use App\Models\SellerStatement;
use App\Services\CurrencyService;
use App\Services\WalletService;
use App\Services\SellerStatementService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SellerEarningController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $viewPermission = false;
    protected bool $settlePermission = false;
    protected WalletService $walletService;
    protected CurrencyService $currencyService;
    protected SellerStatementService $statementService;

    public function __construct(WalletService $walletService, CurrencyService $currencyService, SellerStatementService $statementService)
    {
        $this->walletService = $walletService;
        $this->currencyService = $currencyService;
        $this->statementService = $statementService;

        if ($this->getPanel() === 'admin') {
            $this->viewPermission = $this->hasPermission(AdminPermissionEnum::COMMISSION_VIEW());
            $this->settlePermission = $this->hasPermission(AdminPermissionEnum::COMMISSION_SETTLE());
        }
    }

    /**
     * Display a listing of unsettled commissions.
     */
    public function index(): View
    {
        // Authorization: only users with commission view permission can access
        $this->authorize('viewAny', SellerStatement::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'order_details', 'name' => 'order_details', 'title' => __('labels.order_details')],
            ['data' => 'marketplace_fee', 'name' => 'marketplace_fee', 'title' => __('labels.marketplace_fee')],
            ['data' => 'payout_amount', 'name' => 'payout_amount', 'title' => __('labels.payout_amount')],
            ['data' => 'last_updated', 'name' => 'last_updated', 'title' => __('labels.last_updated')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $columnsReturn = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'details', 'name' => 'details', 'title' => __('labels.details')],
            ['data' => 'debit_amount', 'name' => 'debit_amount', 'title' => __('labels.debit_amount')],
            ['data' => 'last_updated', 'name' => 'last_updated', 'title' => __('labels.last_updated')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $settlePermission = $this->settlePermission;

        return view($this->panelView('commissions.index'), compact('columns', 'settlePermission', 'columnsReturn'));
    }

    /**
     * Get unsettled commissions for datatable.
     */
    public function getUnsettledCommissions(Request $request): JsonResponse
    {
        // Authorization: requires commission view permission
        $this->authorize('view', SellerStatement::class);
        $draw = $request->get('draw');
        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'order_id', 'store_id', 'title', 'amount', 'posted_at'];
        $user = Auth::user();
        if ($this->getPanel() === 'seller') {
            if (empty($user->seller())) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.seller_not_found'),
                    data: [],
                );
            }
        }

        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $storeId = $request->get('store_id');

        // Query pending credit statements
        $query = SellerStatement::with(['orderItem.store', 'order'])
            ->where('entry_type', SellerSettlementTypeEnum::CREDIT())
            ->where('settlement_status', SellerSettlementStatusEnum::PENDING());

        if ($this->getPanel() === 'seller') {
            $query->where('seller_id', $user->seller()->id);
        }

        if (!empty($storeId)) {
            $query->whereHas('orderItem', function ($qi) use ($storeId) {
                $qi->where('store_id', $storeId);
            });
        }

        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('reference_id', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhereHas('orderItem', function ($qi) use ($searchValue) {
                        $qi->where('title', 'like', "%{$searchValue}%")
                            ->orWhere('variant_title', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('orderItem.store', function ($qs) use ($searchValue) {
                        $qs->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $totalRecords = (clone $query)->count();
        $filteredRecords = $totalRecords;

        $statements = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $statements->map(function ($st) {
            $orderItem = $st->orderItem;
            $store = $orderItem?->store;
            return [
                'id' => $st->id,
                'order_details' => "<div>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.order_id') . ": {$st->order_id} | " . __('labels.order_item_id') . ": {$st->order_item_id}</p>
                        <p class='m-0'>" . __('labels.title') . ": " . e($st->orderItem->title) . "</p>
                        <p class='m-0'>" . __('labels.variant') . ": " . e($st->orderItem->variant_title) . "</p>
                        <p class='m-0'>" . __('labels.store') . ": " . e($store->name ?? "") . "</p>
                        <p class='m-0'>" . __('labels.amount') . ": " . $this->currencyService->format($st->orderItem->subtotal) . "</p>
                        <p class='m-0'>" . __('labels.order_date') . ": " . optional($st->posted_at ?? $orderItem?->created_at)->format('Y-m-d') . "</p>
                        <p class='m-0'>" . __('labels.order_current_status') . ": " . Str::ucfirst(Str::replace("_", " ", $st->orderItem->status)) . "</p>
                        </div>",
                'marketplace_fee' => "<b>" . $this->currencyService->format($orderItem->admin_commission_amount ?? 0) . "</b>",
                'payout_amount' => "<b class='text-primary'>" . $this->currencyService->format($st->amount) . "</b>",
                'last_updated' => $st->updated_at->format('Y-m-d H:i:s'),
                'action' => view('partials.actions', [
                    'modelName' => 'commission',
                    'id' => $st->id,
                    'title' => $orderItem->title ?? ('Statement #' . $st->id),
                    'mode' => 'settle',
                    'settlePermission' => $this->settlePermission,
                    'orderId' => $st->order_id,
                    'adminCommissionAmount' => $this->currencyService->format($orderItem->admin_commission_amount ?? 0),
                    'amountToPay' => $this->currencyService->format($st->amount),
                    'productTitle' => $orderItem ? ($orderItem->title . ' - ' . $orderItem->variant_title) : ($st->description ?? ''),
                    'settleUrl' => $this->getPanel() === 'admin' ? route('admin.commissions.settle', ['id' => $st->id]) : null
                ])->render(),
            ];
        })->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Settle commission for a specific order item.
     */
    public function settleCommission($id): JsonResponse
    {
        // Authorization: requires commission settle permission
        $this->authorize('processSettle', SellerStatement::class);
        if (!$this->settlePermission) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: [],
                status: 403
            );
        }

        try {
            DB::beginTransaction();

            // Find pending seller statement (credit) by statement id
            $statement = SellerStatement::with(['orderItem.store.seller', 'order'])
                ->where('id', $id)
                ->where('entry_type', SellerSettlementTypeEnum::CREDIT())
                ->where('settlement_status', SellerSettlementStatusEnum::PENDING())
                ->firstOrFail();

            $orderItem = $statement->orderItem;
            $store = $orderItem?->store;
            $seller = $store?->seller;

            if (!$seller) {
                throw new \Exception('Seller not found for this statement');
            }

            // Get or create seller's wallet
            $walletResult = $this->walletService->getWallet($seller->user_id);
            if (!$walletResult['success']) {
                throw new \Exception('Failed to get seller wallet: ' . $walletResult['message']);
            }

            $amount = (float)$statement->amount;

            // Add balance to seller's wallet
            $transactionData = [
                'amount' => $amount,
                'payment_method' => 'commission_settlement',
                'transaction_reference' => 'STMT-' . $statement->id,
                'description' => $statement->description ?? ('Commission settlement for order #' . ($statement->order_id ?? '')),
                'order_id' => $statement->order_id,
                'store_id' => $store->id ?? null,
            ];

            $addBalanceResult = $this->walletService->addBalance($seller->user_id, $transactionData);

            if (!$addBalanceResult['success']) {
                throw new \Exception('Failed to add balance to seller wallet: ' . $addBalanceResult['message']);
            }

            // Mark statement as settled in the unified settlement table
            $this->statementService->markSettled($statement, $addBalanceResult['data']['transaction']['id'] ?? ('STMT-' . $statement->id));

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.commission_settled_successfully',
                data: [
                    'statement' => $statement->fresh(),
                    'transaction' => $addBalanceResult['data']['transaction'] ?? null
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error settling commission: ' . $e->getMessage());

            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $e->getMessage(),
                data: ['error' => $e->getMessage()],
                status: 500
            );
        }
    }

    /**
     * Settle all unsettled commissions.
     */
    public function settleAllCommissions(): JsonResponse
    {
        // Authorization: requires commission settle permission
        $this->authorize('processSettleAll', SellerStatement::class);
        if (!$this->settlePermission) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: [],
                status: 403
            );
        }

        try {
            DB::beginTransaction();

            // Fetch all pending credit statements
            $unsettledStatements = SellerStatement::with(['orderItem.store.seller', 'order'])
                ->where('entry_type', SellerSettlementTypeEnum::CREDIT())
                ->where('settlement_status', SellerSettlementStatusEnum::PENDING())
                ->get();

            if ($unsettledStatements->count() === 0) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.no_unsettled_commissions_found',
                    data: ['settled_count' => 0]
                );
            }

            $settledCount = 0;
            $errors = [];

            foreach ($unsettledStatements as $statement) {
                try {
                    $orderItem = $statement->orderItem;
                    $store = $orderItem?->store;
                    $seller = $store?->seller;

                    if (!$seller) {
                        $errors[] = "Seller not found for statement #{$statement->id}";
                        continue;
                    }

                    // Get or create seller's wallet
                    $walletResult = $this->walletService->getWallet($seller->user_id);
                    if (!$walletResult['success']) {
                        $errors[] = "Failed to get wallet for seller #{$seller->user_id}: {$walletResult['message']}";
                        continue;
                    }

                    $amount = (float)$statement->amount;

                    // Add balance to seller's wallet
                    $transactionData = [
                        'amount' => $amount,
                        'payment_method' => 'commission_settlement',
                        'transaction_reference' => 'STMT-' . $statement->id,
                        'description' => $statement->description ?? ('Commission settlement for order #' . ($statement->order_id ?? '')),
                        'order_id' => $statement->order_id,
                        'store_id' => $store->id ?? null,
                    ];

                    $addBalanceResult = $this->walletService->addBalance($seller->user_id, $transactionData);

                    if (!$addBalanceResult['success']) {
                        $errors[] = "Failed to add balance for statement #{$statement->id}: {$addBalanceResult['message']}";
                        continue;
                    }

                    // Mark statement as settled
                    $this->statementService->markSettled($statement, $addBalanceResult['data']['transaction']['id'] ?? ('STMT-' . $statement->id));

                    $settledCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error processing statement #{$statement->id}: {$e->getMessage()}";
                }
            }

            DB::commit();

            if (count($errors) > 0) {
                return ApiResponseType::sendJsonResponse(
                    success: true,
                    message: "labels.commissions_settled_with_errors",
                    data: [
                        'settled_count' => $settledCount,
                        'error_count' => count($errors),
                        'errors' => $errors
                    ]
                );
            }

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.all_commissions_settled_successfully',
                data: ['settled_count' => $settledCount]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error settling all commissions: ' . $e->getMessage());

            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $e->getMessage(),
                data: ['error' => $e->getMessage()],
                status: 500
            );
        }
    }

    /**
     * Display a listing of settled commissions.
     */
    public function history(): View
    {
        // Authorization: requires commission view permission
        $this->authorize('viewHistory', SellerStatement::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'entry_type', 'name' => 'entry_type', 'title' => __('labels.entry_type')],
            ['data' => 'details', 'name' => 'details', 'title' => __('labels.details')],
            ['data' => 'description', 'name' => 'description', 'title' => __('labels.description')],
            ['data' => 'amount', 'name' => 'amount', 'title' => __('labels.amount')],
            ['data' => 'settlement_date', 'name' => 'updated_at', 'title' => __('labels.settlement_date')],
        ];

        return view($this->panelView('commissions.history'), compact('columns'));
    }

    /**
     * Get settled commissions for datatable.
     */
    public function getSettledCommissions(Request $request): JsonResponse
    {
        // Authorization: requires commission view permission
        $this->authorize('viewSettled', SellerStatement::class);
        $draw = $request->get('draw');
        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'order_id', 'store_id', 'title', 'amount', 'settled_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $user = Auth::user();
        if ($this->getPanel() === 'seller') {
            if (empty($user->seller())) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.seller_not_found',
                    data: [],
                );
            }
        }

        $query = SellerStatement::with(['orderItem.store'])
//            ->where('entry_type', SellerSettlementTypeEnum::CREDIT())
            ->where('settlement_status', SellerSettlementStatusEnum::SETTLED());

        if ($this->getPanel() === 'seller') {
            $query->where('seller_id', $user->seller()->id);
        }

        $storeId = $request->get('store_id');
        if (!empty($storeId)) {
            $query->whereHas('orderItem', function ($qi) use ($storeId) {
                $qi->where('store_id', $storeId);
            });
        }

        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('reference_id', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhereHas('orderItem', function ($qi) use ($searchValue) {
                        $qi->where('title', 'like', "%{$searchValue}%")
                            ->orWhere('variant_title', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('orderItem.store', function ($qs) use ($searchValue) {
                        $qs->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $totalRecords = (clone $query)->count();
        $filteredRecords = $totalRecords;

        $statements = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $statements->map(function ($st) {
            $orderItem = $st->orderItem;
            $store = $orderItem?->store;
            return [
                'id' => $st->id,
                'entry_type' => "<div class='text-capitalize badge badge-lg "
                    . ($st->entry_type === "debit" ? "bg-danger-lt" : "bg-success-lt")
                    . "'>" . e($st->entry_type) . "</div>",
                'details' => "<div>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.reference_type') . ":  " . Str::ucfirst(Str::replace("_", " ", $st->reference_type)) . "</p>
                        <p class='m-0 fw-medium'>" . __('labels.return_id') . ": {$st->return_id} | " . __('labels.reference_id') . ": {$st->reference_id}</p>
                        <p class='m-0 fw-medium'>" . __('labels.order_id') . ": {$st->order_id} | " . __('labels.order_item_id') . ": {$st->order_item_id}</p>
                        <p class='m-0'>" . __('labels.title') . ": " . e($st->orderItem->title) . "</p>
                        <p class='m-0'>" . __('labels.variant') . ": " . e($st->orderItem->variant_title) . "</p>
                        <p class='m-0'>" . __('labels.store') . ": " . e($store->name) . "</p>
                        <p class='m-0'>" . __('labels.amount') . ": " . $this->currencyService->format($st->orderItem->subtotal) . "</p>
                        <p class='m-0'>" . __('labels.order_date') . ": " . optional($st->posted_at ?? $orderItem?->created_at)->format('Y-m-d') . "</p>
                        <p class='m-0'>" . __('labels.order_current_status') . ": " . Str::ucfirst(Str::replace("_", " ", $st->orderItem->status)) . "</p>
                        </div>",
                'description' => $st->description,
                'amount' => "<b>" . $this->currencyService->format($st->amount) . "</b>",
                'settlement_date' => optional($st->settled_at)->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * List pending debit statements (e.g., return refunds to be debited from seller).
     */
    public function getUnsettledDebits(Request $request): JsonResponse
    {
        // Authorization: requires commission view permission
        $this->authorize('viewUnsettledDebits', SellerStatement::class);
        $draw = $request->get('draw');
        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'order_id', 'store_id', 'title', 'amount', 'posted_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $user = Auth::user();
        if ($this->getPanel() === 'seller') {
            if (empty($user->seller())) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.seller_not_found'),
                    data: [],
                );
            }
        }

        $query = SellerStatement::with(['orderItem.store', 'order'])
            ->where('entry_type', SellerSettlementTypeEnum::DEBIT())
            ->where('settlement_status', SellerSettlementStatusEnum::PENDING());

        if ($this->getPanel() === 'seller') {
            $query->where('seller_id', $user->seller()->id);
        }

        $storeId = $request->get('store_id');
        if (!empty($storeId)) {
            $query->whereHas('orderItem', function ($qi) use ($storeId) {
                $qi->where('store_id', $storeId);
            });
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('reference_id', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhereHas('orderItem', function ($qi) use ($searchValue) {
                        $qi->where('title', 'like', "%{$searchValue}%")
                            ->orWhere('variant_title', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('orderItem.store', function ($qs) use ($searchValue) {
                        $qs->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $totalRecords = (clone $query)->count();
        $filteredRecords = $totalRecords;

        $statements = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $statements->map(function ($st) {
            $orderItem = $st->orderItem; // may be null for some manual debits
            $store = $orderItem?->store;
            return [
                'id' => $st->id,
                'details' => "<div>
                        <p class='m-0 fw-medium text-primary'>" . __('labels.reference_type') . ":  " . Str::ucfirst(Str::replace("_", " ", $st->reference_type)) . "</p>
                        <p class='m-0 fw-medium'>" . __('labels.return_id') . ": {$st->return_id} | " . __('labels.reference_id') . ": {$st->reference_id}</p>
                        <p class='m-0 fw-medium'>" . __('labels.order_id') . ": {$st->order_id} | " . __('labels.order_item_id') . ": {$st->order_item_id}</p>
                        <p class='m-0'>" . __('labels.title') . ": " . e($st->orderItem->title) . "</p>
                        <p class='m-0'>" . __('labels.variant') . ": " . e($st->orderItem->variant_title) . "</p>
                        <p class='m-0'>" . __('labels.store') . ": " . e($store->name) . "</p>
                        <p class='m-0'>" . __('labels.amount') . ": " . $this->currencyService->format($st->orderItem->subtotal) . "</p>
                        <p class='m-0'>" . __('labels.order_date') . ": " . optional($st->posted_at ?? $orderItem?->created_at)->format('Y-m-d') . "</p>
                        <p class='m-0'>" . __('labels.order_current_status') . ": " . Str::ucfirst(Str::replace("_", " ", $st->orderItem->status)) . "</p>
                        </div>",
                'debit_amount' => "<b>" . $this->currencyService->format($st->amount) . "</b>",
                'last_updated' => $st->updated_at->format('Y-m-d H:i:s'),
                'action' => view('partials.actions', [
                    'modelName' => 'commission',
                    'id' => $st->id,
                    'title' => $orderItem->title ?? ('Statement #' . $st->id),
                    'mode' => 'settle',
                    'settlePermission' => $this->settlePermission,
                    'orderId' => $st->order_id,
                    'adminCommissionAmount' => $this->currencyService->format($orderItem->admin_commission_amount ?? 0),
                    'amountToPay' => $this->currencyService->format($st->amount),
                    'productTitle' => $orderItem ? ($orderItem->title . ' - ' . $orderItem->variant_title) : ($st->description ?? ''),
                    'settleUrl' => ($this->getPanel() === 'admin') ? route('admin.commissions.debits.settle', ['id' => $st->id]) : ""
                ])->render(),
            ];
        })->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Settle a single debit statement (admin): deduct from seller wallet and mark settled.
     */
    public function settleDebit($id): JsonResponse
    {
        // Authorization: requires commission settle permission
        $this->authorize('settleDebit', SellerStatement::class);
        if (!$this->settlePermission) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: [],
                status: 403
            );
        }

        try {
            DB::beginTransaction();

            $statement = SellerStatement::with(['orderItem.store.seller', 'order'])
                ->where('id', $id)
                ->where('entry_type', SellerSettlementTypeEnum::DEBIT())
                ->where('settlement_status', SellerSettlementStatusEnum::PENDING())
                ->firstOrFail();

            $seller = $statement->orderItem?->store?->seller;
            if (!$seller) {
                // fallback by seller_id
                $seller = Seller::find($statement->seller_id);
            }
            if (!$seller) {
                throw new \Exception('Seller not found for this statement');
            }

            // Deduct from seller wallet
            $amount = (float)$statement->amount;
            $transactionData = [
                'amount' => $amount,
                'payment_method' => 'commission_debit_settlement',
                'transaction_reference' => 'STMT-DEBIT-' . $statement->id,
                'description' => $statement->description ?? ('Debit settlement for return/order #' . ($statement->order_id ?? '')),
                'order_id' => $statement->order_id,
                'store_id' => $statement->orderItem?->store_id,
            ];

            $deductResult = WalletService::deductBalance($seller->user_id, $transactionData);
            if (!$deductResult['success']) {
                throw new \Exception($deductResult['message'] ?? 'Failed to deduct balance');
            }

            // Mark statement as settled
            $this->statementService->markSettled($statement, $deductResult['data']['transaction']['id'] ?? ('STMT-DEBIT-' . $statement->id));

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.commission_settled_successfully',
                data: [
                    'statement' => $statement->fresh(),
                    'transaction' => $deductResult['data']['transaction'] ?? null
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error settling debit: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $e->getMessage(),
                data: ['error' => $e->getMessage()],
                status: 500
            );
        }
    }

    /**
     * Settle all pending debit statements (admin)
     */
    public function settleAllDebits(): JsonResponse
    {
        // Authorization: requires commission settle permission
        $this->authorize('settleAllDebits', SellerStatement::class);
        if (!$this->settlePermission) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: [],
                status: 403
            );
        }

        try {
            DB::beginTransaction();

            $unsettledStatements = SellerStatement::with(['orderItem.store.seller', 'order'])
                ->where('entry_type', SellerSettlementTypeEnum::DEBIT())
                ->where('settlement_status', SellerSettlementStatusEnum::PENDING())
                ->get();

            if ($unsettledStatements->count() === 0) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.no_unsettled_debit_found',
                    data: ['settled_count' => 0]
                );
            }

            $settledCount = 0;
            $errors = [];

            foreach ($unsettledStatements as $statement) {
                try {
                    $seller = $statement->orderItem?->store?->seller ?? Seller::find($statement->seller_id);
                    if (!$seller) {
                        $errors[] = "Seller not found for statement #{$statement->id}";
                        continue;
                    }

                    $amount = (float)$statement->amount;
                    $transactionData = [
                        'amount' => $amount,
                        'payment_method' => 'commission_debit_settlement',
                        'transaction_reference' => 'STMT-DEBIT-' . $statement->id,
                        'description' => $statement->description ?? ('Debit settlement for return/order #' . ($statement->order_id ?? '')),
                        'order_id' => $statement->order_id,
                        'store_id' => $statement->orderItem?->store_id,
                    ];

                    $deductResult = WalletService::deductBalance($seller->user_id, $transactionData);
                    if (!$deductResult['success']) {
                        $errors[] = "Failed to deduct balance for statement #{$statement->id}: {$deductResult['message']}";
                        continue;
                    }

                    $this->statementService->markSettled($statement, $deductResult['data']['transaction']['id'] ?? ('STMT-DEBIT-' . $statement->id));
                    $settledCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error processing debit statement #{$statement->id}: {$e->getMessage()}";
                }
            }

            DB::commit();

            if (count($errors) > 0) {
                return ApiResponseType::sendJsonResponse(
                    success: true,
                    message: "labels.commissions_settled_with_errors",
                    data: [
                        'settled_count' => $settledCount,
                        'error_count' => count($errors),
                        'errors' => $errors
                    ]
                );
            }

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.all_commissions_settled_successfully',
                data: ['settled_count' => $settledCount]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error settling all debit commissions: ' . $e->getMessage());

            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $e->getMessage(),
                data: ['error' => $e->getMessage()],
                status: 500
            );
        }
    }
}
