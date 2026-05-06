<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Wallet\WalletTransactionStatusEnum;
use App\Enums\Wallet\WalletTransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CurrencyService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletTransactionController extends Controller
{
    protected WalletService $walletService;
    protected CurrencyService $currencyService;

    public function __construct(WalletService $walletService, CurrencyService $currencyService)
    {
        $this->walletService = $walletService;
        $this->currencyService = $currencyService;
    }

    // All transactions page
    public function transactions(): View
    {
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'user', 'name' => 'user', 'title' => __('labels.customer')],
            ['data' => 'amount', 'name' => 'amount', 'title' => __('labels.amount')],
            ['data' => 'transaction_type', 'name' => 'transaction_type', 'title' => __('labels.type')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'payment_method', 'name' => 'payment_method', 'title' => __('labels.payment_method')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
        ];
        return view('admin.wallet.transactions', compact('columns'));
    }

    // Pending deposits page
    public function deposits(): View
    {
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'user', 'name' => 'user', 'title' => __('labels.customer')],
            ['data' => 'amount', 'name' => 'amount', 'title' => __('labels.amount')],
            ['data' => 'payment_method', 'name' => 'payment_method', 'title' => __('labels.payment_method')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];
        return view('admin.wallet.deposits', compact('columns'));
    }

    // Datatable for all transactions
    public function transactionsDatatable(Request $request): JsonResponse
    {
        $draw = $request->get('draw');
        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';
        $columns = ['id', 'user_id', 'amount', 'transaction_type', 'status', 'payment_method', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = WalletTransaction::query()->with('user');

        $totalRecords = $query->count();

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('amount', 'like', "%{$searchValue}%")
                    ->orWhere('payment_method', 'like', "%{$searchValue}%")
                    ->orWhere('transaction_reference', 'like', "%{$searchValue}%")
                    ->orWhereHas('user', function ($uq) use ($searchValue) {
                        $uq->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $filteredRecords = $query->count();

        $data = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function (WalletTransaction $t) {
                return [
                    'id' => $t->id,
                    'user' => $t->user->name ?? 'N/A',
                    'amount' => $this->currencyService->format($t->amount) . '<p class="mt-2 mb-0 fs-5">' . __('labels.transaction_reference') . " - " . ($t->transaction_reference ?? "-") . ' </p>',
                    'transaction_type' => $t->transaction_type,
                    'status' => ucfirst($t->status),
                    'payment_method' => $t->payment_method,
                    'created_at' => $t->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    // Datatable for pending deposits
    public function depositsDatatable(Request $request): JsonResponse
    {
        $draw = $request->get('draw');
        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';
        $columns = ['id', 'user_id', 'amount', 'payment_method', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = WalletTransaction::query()
            ->with('user')
            ->where('transaction_type', WalletTransactionTypeEnum::DEPOSIT())
            ->where('status', WalletTransactionStatusEnum::PENDING());

        $totalRecords = $query->count();

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('amount', 'like', "%{$searchValue}%")
                    ->orWhere('payment_method', 'like', "%{$searchValue}%")
                    ->orWhereHas('user', function ($uq) use ($searchValue) {
                        $uq->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $filteredRecords = $query->count();

        $data = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function (WalletTransaction $t) {
                $action = view('admin.wallet.partials.deposit_actions', [
                    'id' => $t->id,
                    'userName' => $t->user->name ?? 'N/A',
                    'amount' => $this->currencyService->format($t->amount),
                ])->render();
                return [
                    'id' => $t->id,
                    'user' => $t->user->name ?? 'N/A',
                    'amount' => $this->currencyService->format($t->amount) . '<p class="mt-2 mb-0 fs-5">' . __('labels.transaction_reference') . ' - ' . ($t->transaction_reference ?? "-") . ' </p>',
                    'payment_method' => $t->payment_method,
                    'created_at' => $t->created_at?->format('Y-m-d H:i:s'),
                    'action' => $action,
                ];
            });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    // Process a pending deposit (approve or reject)
    public function processDeposit(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:500',
        ]);

        $approve = $request->input('action') === 'approve';
        $note = $approve ? __('labels.deposit_approve_by_admin') : __('labels.deposit_rejected_by_admin');

        $result = $this->walletService->finalizeDeposit($id, $approve, $note);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
