<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\Seller\SellerWithdrawalStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\SellerWithdrawalRequest;
use App\Services\CurrencyService;
use App\Services\WithdrawalService;
use App\Traits\ChecksPermissions;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SellerWithdrawalController extends Controller
{
    use ChecksPermissions, AuthorizesRequests;

    protected WithdrawalService $withdrawalService;
    protected bool $viewPermission = false;
    protected bool $processRequestPermission = false;
    protected CurrencyService $currencyService;

    public function __construct(WithdrawalService $withdrawalService, CurrencyService $currencyService)
    {
        $this->withdrawalService = $withdrawalService;
        $this->viewPermission = $this->hasPermission(AdminPermissionEnum::SELLER_WITHDRAWAL_VIEW());
        $this->processRequestPermission = $this->hasPermission(AdminPermissionEnum::SELLER_WITHDRAWAL_PROCESS());
        $this->currencyService = $currencyService;
    }

    /**
     * Display a listing of pending withdrawal requests.
     */
    public function index(): View
    {
        $this->authorize('viewAny', SellerWithdrawalRequest::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'seller', 'name' => 'seller', 'title' => __('labels.seller')],
            ['data' => 'amount', 'name' => 'amount', 'title' => __('labels.amount')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'request_note', 'name' => 'request_note', 'title' => __('labels.request_note')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];
        return view('admin.seller_withdrawals.index', compact('columns'));
    }

    /**
     * Get withdrawal requests data for DataTable
     */
    public function getWithdrawalRequests(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SellerWithdrawalRequest::class);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'seller_id', 'amount', 'status', 'request_note', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = SellerWithdrawalRequest::query()
            ->with(['seller.user'])
            ->where('status', SellerWithdrawalStatusEnum::PENDING());

        $totalRecords = $query->count();
        $filteredRecords = $totalRecords; // Default to total records if no filtering is applied

        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('amount', 'like', "%{$searchValue}%")
                    ->orWhere('request_note', 'like', "%{$searchValue}%")
                    ->orWhereHas('seller', function ($q) use ($searchValue) {
                        $q->whereHas('user', function ($q) use ($searchValue) {
                            $q->where('name', 'like', "%{$searchValue}%");
                        });
                    });
            });
            $filteredRecords = $query->count();
        }

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($withdrawalRequest) {
                return [
                    'id' => $withdrawalRequest->id,
                    'seller' => $withdrawalRequest->seller->user->name ?? 'N/A',
                    'amount' => $this->currencyService->format($withdrawalRequest->amount),
                    'status' => view('partials.status', ['status' => $withdrawalRequest->status])->render(),
                    'request_note' => $withdrawalRequest->request_note ?? 'N/A',
                    'created_at' => $withdrawalRequest->created_at->format('Y-m-d H:i:s'),
                    'action' => view('admin.seller_withdrawals.actions', [
                        'id' => $withdrawalRequest->id,
                        'sellerName' => $withdrawalRequest->seller->user->name ?? 'N/A',
                        'sellerId' => $withdrawalRequest->seller_id,
                        'amount' => $this->currencyService->format($withdrawalRequest->amount),
                    ])->render(),
                ];
            })
            ->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Process a withdrawal request (approve or reject)
     */
    public function processWithdrawalRequest(Request $request, int $id): JsonResponse
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'status' => ['required', new Enum(SellerWithdrawalStatusEnum::class)],
                'remark' => 'nullable|string|max:500',
            ]);

            $this->authorize('processRequest', SellerWithdrawalRequest::class);

            // Process the withdrawal request
            $result = $this->withdrawalService->processWithdrawalRequest(
                $id,
                [
                    'status' => $validated['status'],
                    'remark' => $validated['remark'] ?? null,
                ],
                auth()->id(),
                'seller'
            );

            return ApiResponseType::sendJsonResponse(
                success: $result['success'],
                message: $result['message'],
                data: $result['data']
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.unauthorized_action'),
                data: ['error' => $e->getMessage()]
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $e->getMessage(),
                data: $e->errors()
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Display withdrawal request history
     */
    public function history(): View
    {
        $this->authorize('viewAny', SellerWithdrawalRequest::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'seller', 'name' => 'seller', 'title' => __('labels.seller')],
            ['data' => 'amount', 'name' => 'amount', 'title' => __('labels.amount')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'request_note', 'name' => 'request_note', 'title' => __('labels.request_note')],
            ['data' => 'admin_remark', 'name' => 'admin_remark', 'title' => __('labels.admin_remark')],
            ['data' => 'processed_at', 'name' => 'processed_at', 'title' => __('labels.processed_at')],
            ['data' => 'processed_by', 'name' => 'processed_by', 'title' => __('labels.processed_by')],
        ];

        return view('admin.seller_withdrawals.history', compact('columns'));
    }

    /**
     * Get withdrawal request history data for DataTable
     */
    public function getWithdrawalHistory(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SellerWithdrawalRequest::class);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $status = $request->get('status');
        $sellerIds = $request->get('seller_ids');

        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'seller_id', 'amount', 'status', 'request_note', 'admin_remark', 'processed_at', 'processed_by'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = SellerWithdrawalRequest::query()
            ->with(['seller.user', 'processedBy'])
            ->whereIn('status', [SellerWithdrawalStatusEnum::APPROVED(), SellerWithdrawalStatusEnum::REJECTED()]);

        $totalRecords = $query->count();

        if (!empty($sellerIds)) {
            $sellerIdArray = array_filter(explode(',', $sellerIds ?? ''), function ($value) {
                return is_numeric(trim($value));
            });
            if (!empty($sellerIdArray)) {
                $query->whereIn('seller_id', $sellerIdArray);
            }
        }

        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('amount', 'like', "%{$searchValue}%")
                    ->orWhere('request_note', 'like', "%{$searchValue}%")
                    ->orWhere('admin_remark', 'like', "%{$searchValue}%")
                    ->orWhereHas('seller', function ($q) use ($searchValue) {
                        $q->whereHas('user', function ($q) use ($searchValue) {
                            $q->where('name', 'like', "%{$searchValue}%");
                        });
                    })
                    ->orWhereHas('processedBy', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $filteredRecords = $query->count();
        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($withdrawalRequest) {
                return [
                    'id' => $withdrawalRequest->id,
                    'seller' => $withdrawalRequest->seller->user->name ?? 'N/A',
                    'amount' => $this->currencyService->format($withdrawalRequest->amount),
                    'status' => view('partials.status', ['status' => $withdrawalRequest->status])->render(),
                    'request_note' => $withdrawalRequest->request_note ?? 'N/A',
                    'admin_remark' => $withdrawalRequest->admin_remark ?? 'N/A',
                    'processed_at' => $withdrawalRequest->processed_at ? $withdrawalRequest->processed_at->format('Y-m-d H:i:s') : 'N/A',
                    'processed_by' => $withdrawalRequest->processedBy->name ?? 'N/A',
                ];
            })
            ->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Display details of a withdrawal request
     */
    public function show(int $id): View
    {
        $this->authorize('view', SellerWithdrawalRequest::class);
        $withdrawalRequest = SellerWithdrawalRequest::with(['seller.user', 'processedBy', 'transaction'])
            ->findOrFail($id);
        $processRequestPermission = $this->processRequestPermission;

        return view('admin.seller_withdrawals.show', compact('withdrawalRequest', 'processRequestPermission'));
    }
}
