<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\DeliveryBoyAssignment;
use App\Models\DeliveryBoyCashTransaction;
use App\Services\CurrencyService;
use App\Traits\ChecksPermissions;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryBoyCashCollectionController extends Controller
{
    use ChecksPermissions, AuthorizesRequests;

    protected bool $viewPermission = true;
    protected bool $processSubmissionPermission = true;
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->viewPermission = $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_CASH_COLLECTION_VIEW());
        $this->processSubmissionPermission = $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_CASH_COLLECTION_PROCESS());
        $this->currencyService = $currencyService;
    }

    /**
     * Display a listing of pending cash collections.
     */
    public function index(): View
    {
        $this->authorize('viewAny', DeliveryBoyAssignment::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'order_id', 'name' => 'order_id', 'title' => __('labels.order_id')],
            ['data' => 'delivery_boy', 'name' => 'delivery_boy', 'title' => __('labels.delivery_boy')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'cod_cash_collected', 'name' => 'cod_cash_collected', 'title' => __('labels.cash_collected')],
            ['data' => 'cod_cash_submitted', 'name' => 'cod_cash_submitted', 'title' => __('labels.cash_submitted')],
            ['data' => 'cod_cash_remaining', 'name' => 'cod_cash_remaining', 'title' => __('labels.cash_remaining')],
            ['data' => 'cod_submission_status', 'name' => 'cod_submission_status', 'title' => __('labels.submission_status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
        ];
        if ($this->processSubmissionPermission ?? false) {
            $columns[] = ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false];
        }

        return view('admin.delivery_boy_cash_collections.index', compact('columns'));
    }

    /**
     * Get delivery boy cash collections data for DataTable
     */
    public function getCashCollections(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DeliveryBoyAssignment::class);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $deliveryBoyId = $request->get('delivery_boy_id');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'order_id', 'delivery_boy_id', 'status', 'cod_cash_collected', 'cod_cash_submitted', 'cod_submission_status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = DeliveryBoyAssignment::query()
            ->with(['deliveryBoy', 'order'])
            ->where('status', DeliveryBoyAssignmentStatusEnum::COMPLETED())
            ->whereIn('cod_submission_status', ['pending', 'partially_submitted'])
            ->where('cod_cash_collected', '>', 0);

        $totalRecords = $query->count();
        $filteredRecords = $totalRecords; // Default to total records if no filtering is applied

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

        // Delivery boy filter
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
                    'delivery_boy' => $assignment->deliveryBoy->full_name ?? 'N/A',
                    'status' => view('partials.status', ['status' => $assignment->status])->render(),
                    'cod_cash_collected' => $this->currencyService->format($assignment->cod_cash_collected),
                    'cod_cash_submitted' => $this->currencyService->format($assignment->cod_cash_submitted),
                    'cod_cash_remaining' => $this->currencyService->format($assignment->cod_cash_collected - $assignment->cod_cash_submitted),
                    'cod_submission_status' => view('partials.status', ['status' => $assignment->cod_submission_status])->render(),
                    'created_at' => $assignment->created_at->format('Y-m-d'),
                    'action' => view('admin.delivery_boy_cash_collections.actions', [
                        'id' => $assignment->id,
                        'order_id' => $assignment->order_id,
                        'delivery_boy_id' => $assignment->delivery_boy_id,
                        'delivery_boy_name' => $assignment->deliveryBoy->full_name ?? 'N/A',
                        'cod_cash_collected' => $this->currencyService->format($assignment->cod_cash_collected),
                        'cod_cash_submitted' => $this->currencyService->format($assignment->cod_cash_submitted),
                        'remaining_amount' => $this->currencyService->format($assignment->cod_cash_collected - $assignment->cod_cash_submitted),
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
     * Process cash submission for a delivery boy assignment
     */
    public function processCashSubmission(Request $request, int $id): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'amount' => 'required|numeric|min:0.01',
            ]);

            // Find the assignment
            $assignment = DeliveryBoyAssignment::with(['deliveryBoy.user', 'order'])
                ->where('id', $id)
                ->whereIn('cod_submission_status', ['pending', 'partially_submitted'])
                ->firstOrFail();

            $this->authorize('processPayment', $assignment);

            $submittedAmount = (float)$request->input('amount');
            $remainingAmount = $assignment->cod_cash_collected - $assignment->cod_cash_submitted;

            // Check if the submitted amount is valid
            if ($submittedAmount > $remainingAmount) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.invalid_amount'),
                    data: ['error' => 'Submitted amount cannot be greater than the remaining amount.']
                );
            }

            DB::beginTransaction();

            // Update the assignment with submission details
            $newSubmittedTotal = $assignment->cod_cash_submitted + $submittedAmount;
            $newStatus = $newSubmittedTotal >= $assignment->cod_cash_collected
                ? 'submitted'
                : 'partially_submitted';

            $assignment->cod_cash_submitted = $newSubmittedTotal;
            $assignment->cod_submission_status = $newStatus;
            $assignment->save();

            // Create a transaction record for the cash submission using Eloquent
            DeliveryBoyCashTransaction::create([
                'delivery_boy_assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'delivery_boy_id' => $assignment->delivery_boy_id,
                'amount' => $submittedAmount,
                'transaction_type' => 'submitted',
                'transaction_date' => now()
            ]);

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.cash_submission_processed_successfully'),
                data: [
                    'assignment' => $assignment->fresh()
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
     * Display cash submission history
     */
    public function history(): View
    {
        $this->authorize('viewAny', DeliveryBoyAssignment::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'order_id', 'name' => 'order_id', 'title' => __('labels.order_id')],
            ['data' => 'delivery_boy', 'name' => 'delivery_boy', 'title' => __('labels.delivery_boy')],
            ['data' => 'cod_cash_collected', 'name' => 'cod_cash_collected', 'title' => __('labels.cash_collected')],
            ['data' => 'cod_cash_submitted', 'name' => 'cod_cash_submitted', 'title' => __('labels.cash_submitted')],
            ['data' => 'cod_submission_status', 'name' => 'cod_submission_status', 'title' => __('labels.submission_status')],
            ['data' => 'updated_at', 'name' => 'updated_at', 'title' => __('labels.submitted_at')],
        ];

        return view('admin.delivery_boy_cash_collections.history', compact('columns'));
    }

    /**
     * Get cash submission history data for DataTable
     */
    public function getCashSubmissionHistory(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DeliveryBoyAssignment::class);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $deliveryBoyId = $request->get('delivery_boy_id');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'order_id', 'delivery_boy_id', 'cod_cash_collected', 'cod_cash_submitted', 'cod_submission_status', 'updated_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = DeliveryBoyAssignment::query()
            ->with(['deliveryBoy', 'order'])
            ->where('status', DeliveryBoyAssignmentStatusEnum::COMPLETED())
            ->where('cod_submission_status', 'submitted')
            ->where('cod_cash_collected', '>', 0);

        $totalRecords = $query->count();
        $filteredRecords = $totalRecords; // Default to total records if no filtering is applied

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

        // Delivery boy filter
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
                    'delivery_boy' => $assignment->deliveryBoy->full_name ?? 'N/A',
                    'cod_cash_collected' => $this->currencyService->format($assignment->cod_cash_collected),
                    'cod_cash_submitted' => $this->currencyService->format($assignment->cod_cash_submitted),
                    'cod_submission_status' => view('partials.status', ['status' => $assignment->cod_submission_status])->render(),
                    'updated_at' => $assignment->updated_at->format('Y-m-d H:i:s'),
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
