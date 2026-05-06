<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Enums\DeliveryBoy\EarningPaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\DeliveryBoyAssignment;
use App\Services\CurrencyService;
use App\Services\WalletService;
use App\Traits\ChecksPermissions;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryBoyEarningController extends Controller
{
    use ChecksPermissions, AuthorizesRequests;

    protected WalletService $walletService;
    protected bool $viewPermission = false;
    protected bool $processPaymentPermission = false;
    protected CurrencyService $currencyService;

    public function __construct(WalletService $walletService, CurrencyService $currencyService)
    {
        $this->walletService = $walletService;
        $this->viewPermission = $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_EARNING_VIEW());
        $this->processPaymentPermission = $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_EARNING_PROCESS_PAYMENT());
        $this->currencyService = $currencyService;
    }

    /**
     * Display a listing of pending earnings.
     */
    public function index(): View
    {
        $this->authorize('viewAny', DeliveryBoyAssignment::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'order_id', 'name' => 'order_id', 'title' => __('labels.order_id')],
            ['data' => 'delivery_boy', 'name' => 'delivery_boy', 'title' => __('labels.delivery_boy')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'total_earnings', 'name' => 'total_earnings', 'title' => __('labels.total_earnings')],
            ['data' => 'payment_status', 'name' => 'payment_status', 'title' => __('labels.payment_status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
        ];
        if ($this->processPaymentPermission ?? false){
            $columns[] = ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false];
        }
        return view('admin.delivery_boy_earnings.index', compact('columns'));
    }

    /**
     * Get delivery boy earnings data for DataTable
     */
    public function getEarnings(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DeliveryBoyAssignment::class);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $deliveryBoyId = $request->get('delivery_boy_id');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'order_id', 'delivery_boy_id', 'status', 'total_earnings', 'payment_status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = DeliveryBoyAssignment::query()
            ->with(['deliveryBoy', 'deliveryBoy.user', 'order'])
            ->where('status', DeliveryBoyAssignmentStatusEnum::COMPLETED())
            ->where('payment_status', EarningPaymentStatusEnum::PENDING());

        $totalRecords = $query->count();

        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('order_id', 'like', "%{$searchValue}%")
                    ->orWhereHas('deliveryBoy', function ($q) use ($searchValue) {
                        $q->whereHas('user', function ($q) use ($searchValue) {
                            $q->where('name', 'like', "%{$searchValue}%");
                        });
                    });
            });
        }

        if (!empty($deliveryBoyId)) {
            $query->where('delivery_boy_id', $deliveryBoyId);
        }

        $filteredRecords = $query->count();
        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'order_id' => $assignment->order_id,
                    'delivery_boy' => ($assignment->deliveryBoy->full_name ?? 'N/A') . " - " . $assignment->deliveryBoy->user->email ?? "",
                    'status' => view('partials.status', ['status' => $assignment->status])->render(),
                    'total_earnings' => $this->currencyService->format($assignment->total_earnings),
                    'payment_status' => view('partials.status', ['status' => $assignment->payment_status])->render(),
                    'created_at' => $assignment->created_at->format('Y-m-d'),
                    'action' => view('admin.delivery_boy_earnings.actions', [
                        'id' => $assignment->id,
                        'order_id' => $assignment->order_id,
                        'delivery_boy_name' => $assignment->deliveryBoy->full_name ?? 'N/A',
                        'delivery_boy_id' => $assignment->delivery_boy_id,
                        'total_earnings' => $this->currencyService->format($assignment->total_earnings),
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
     * Process payment for a delivery boy assignment
     */
    public function processPayment(Request $request, int $id): JsonResponse
    {
        try {
            // Find the assignment
            $assignment = DeliveryBoyAssignment::with(['deliveryBoy.user', 'order'])
                ->where('id', $id)
                ->where('payment_status', EarningPaymentStatusEnum::PENDING())
                ->firstOrFail();

            $this->authorize('processPayment', $assignment);
            DB::beginTransaction();

            // Get the delivery boy user ID
            $userId = $assignment->deliveryBoy->user_id;

            // Add balance to the delivery boy's wallet
            $result = $this->walletService->addBalance($userId, [
                'amount' => $assignment->total_earnings,
                'payment_method' => 'admin',
                'description' => "Earnings for Order #{$assignment->order_id}",
            ]);

            if (!$result['success']) {
                DB::rollBack();
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: $result['message'],
                    data: $result['data']
                );
            }

            // Update the assignment with payment details
            $assignment->payment_status = EarningPaymentStatusEnum::PAID();
            $assignment->paid_at = now();
            $assignment->transaction_id = $result['data']['transaction']->id;
            $assignment->save();

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.payment_processed_successfully'),
                data: [
                    'assignment' => $assignment,
                    'transaction' => $result['data']['transaction']
                ]
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.unauthorized_action'),
                data: ['error' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Display payment history
     */
    public function history(): View
    {
        $this->authorize('viewAny', DeliveryBoyAssignment::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'order_id', 'name' => 'order_id', 'title' => __('labels.order_id')],
            ['data' => 'delivery_boy', 'name' => 'delivery_boy', 'title' => __('labels.delivery_boy')],
            ['data' => 'total_earnings', 'name' => 'total_earnings', 'title' => __('labels.total_earnings')],
            ['data' => 'payment_status', 'name' => 'payment_status', 'title' => __('labels.payment_status')],
            ['data' => 'paid_at', 'name' => 'paid_at', 'title' => __('labels.paid_at')],
            ['data' => 'transaction_id', 'name' => 'transaction_id', 'title' => __('labels.transaction_id')],
        ];

        return view('admin.delivery_boy_earnings.history', compact('columns'));
    }

    /**
     * Get payment history data for DataTable
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DeliveryBoyAssignment::class);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $deliveryBoyId = $request->get('delivery_boy_id');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'order_id', 'delivery_boy_id', 'total_earnings', 'payment_status', 'paid_at', 'transaction_id'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = DeliveryBoyAssignment::query()
            ->with(['deliveryBoy', 'order', 'transaction'])
            ->where('payment_status', EarningPaymentStatusEnum::PAID());

        $totalRecords = $query->count();

        if (!empty($deliveryBoyId)) {
            $query->where('delivery_boy_id', $deliveryBoyId);
        }

        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('order_id', 'like', "%{$searchValue}%")
                    ->orWhere('transaction_id', 'like', "%{$searchValue}%")
                    ->orWhereHas('deliveryBoy', function ($q) use ($searchValue) {
                        $q->whereHas('user', function ($q) use ($searchValue) {
                            $q->where('name', 'like', "%{$searchValue}%");
                        });
                    });
            });
        }

        $filteredRecords = $query->count();
        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'order_id' => $assignment->order_id,
                    'delivery_boy' => ($assignment->deliveryBoy->full_name ?? 'N/A') . " - " . $assignment->deliveryBoy->user->email ?? "",
                    'total_earnings' => $this->currencyService->format($assignment->total_earnings),
                    'payment_status' => view('partials.status', ['status' => $assignment->payment_status])->render(),
                    'paid_at' => $assignment->paid_at ?? 'N/A',
                    'transaction_id' => $assignment->transaction_id ?? 'N/A',
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
}
