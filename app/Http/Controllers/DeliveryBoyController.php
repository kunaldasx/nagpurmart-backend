<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Events\DeliveryBoy\DeliveryBoyVerificationStatusUpdated;
use App\Models\DeliveryBoy;
use App\Models\DeliveryBoyAssignment;
use App\Models\DeliveryFeedback;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class DeliveryBoyController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $viewPermission = false;

    public function __construct()
    {
        if ($this->getPanel() === 'admin') {
            $this->editPermission = $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_EDIT());
            $this->deletePermission = $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_DELETE());
            $this->viewPermission = $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_VIEW());
        }
    }

    /**
     * Display a listing of the delivery boys.
     */
    public function index(): View
    {
        try {
            $this->authorize('viewAny', DeliveryBoy::class);
        } catch (AuthorizationException $e) {
            abort(403, __('labels.unauthorized_access'));
        }

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'full_name', 'name' => 'full_name', 'title' => __('labels.full_name')],
            ['data' => 'email', 'name' => 'email', 'title' => __('labels.email')],
            ['data' => 'mobile', 'name' => 'mobile', 'title' => __('labels.mobile')],
            ['data' => 'delivery_zone', 'name' => 'delivery_zone', 'title' => __('labels.delivery_zone')],
            ['data' => 'vehicle_type', 'name' => 'vehicle_type', 'title' => __('labels.vehicle_type')],
            ['data' => 'verification_status', 'name' => 'verification_status', 'title' => __('labels.verification_status')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $viewPermission = $this->viewPermission;

        $verificationStatuses = DeliveryBoyVerificationStatusEnum::values();

        return view($this->panelView('delivery_boys.index'), compact(
            'columns',
            'editPermission',
            'deletePermission',
            'viewPermission',
            'verificationStatuses'
        ));
    }

    /**
     * Display the specified delivery boy.
     */
    public function show($id): View
    {
        $deliveryBoy = DeliveryBoy::with(['user', 'deliveryZone'])->findOrFail($id);
        $this->authorize('view', $deliveryBoy);

        // Get media URLs
        $driverLicenseUrl = $deliveryBoy->hasMedia('driver_license')
            ? $deliveryBoy->getFirstMediaUrl('driver_license')
            : null;

        $vehicleRegistrationUrl = $deliveryBoy->hasMedia('vehicle_registration')
            ? $deliveryBoy->getFirstMediaUrl('vehicle_registration')
            : null;

        $verificationStatuses = DeliveryBoyVerificationStatusEnum::values();
        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $successDelivery = $deliveryBoy->assignments->where('status', DeliveryBoyAssignmentStatusEnum::COMPLETED())->count();
        $reviewData = DeliveryFeedback::getDeliveryFeedbackStatistics($deliveryBoy->id);

        return view($this->panelView('delivery_boys.view'), compact(
            'deliveryBoy', 'driverLicenseUrl', 'vehicleRegistrationUrl', 'verificationStatuses', 'editPermission', 'deletePermission', 'successDelivery', 'reviewData'
        ));
    }

    /**
     * Update the verification status of the delivery boy.
     */
    // Update the updateVerificationStatus method
    public function updateVerificationStatus(Request $request, $id): JsonResponse
    {
        try {
            $deliveryBoy = DeliveryBoy::findOrFail($id);
            $this->authorize('update', $deliveryBoy);

            $validated = $request->validate([
                'verification_status' => ['required', new Enum(DeliveryBoyVerificationStatusEnum::class)],
                'verification_remark' => 'nullable|string|max:1000'
            ]);

            $previousStatus = $deliveryBoy->verification_status->value;
            if ($previousStatus === DeliveryBoyVerificationStatusEnum::VERIFIED()) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.once_delivery_boy_verified_cant_be_changed'),
                    data: $deliveryBoy
                );
            }

            $deliveryBoy->update([
                'verification_status' => $validated['verification_status'],
                'verification_remark' => $validated['verification_remark'] ?? null
            ]);

            // Dispatch the event
            event(new DeliveryBoyVerificationStatusUpdated(
                $deliveryBoy,
                auth()->user(),
                $previousStatus,
                $validated['verification_status'],
                $validated['verification_remark'] ?? null
            ));

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.verification_status_updated_successfully',
                data: $deliveryBoy
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: []
            );
        }
    }

    /**
     * Remove the specified delivery boy from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $deliveryBoy = DeliveryBoy::findOrFail($id);
            $this->authorize('delete', $deliveryBoy);
            DB::beginTransaction();
            $deliveryBoy->delete();
            $deliveryBoy->user?->delete();
            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.delivery_boy_deleted_successfully',
                data: []
            );
        } catch (AuthorizationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: []
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.error_occurred',
                data: []
            );
        }
    }

    /**
     * Get delivery boys for datatable
     */
    public function getDeliveryBoys(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', DeliveryBoy::class);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied');
        }

        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';
        $verificationStatus = $request->get('verification_status');
        $status = $request->get('status');
        $deliveryBoyId = $request->get('delivery_boy_id');

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'full_name', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = DeliveryBoy::query()->with(['user', 'deliveryZone']);

        $totalRecords = DeliveryBoy::count();

        if (!empty($deliveryBoyId)) {
            $query->where('id', $deliveryBoyId);
        }
        // Filters
        if ($verificationStatus !== null) {
            $query->where('verification_status', $verificationStatus);
        }
        if ($status !== null) {
            $query->where('status', $status);
        }


        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('full_name', 'like', "%{$searchValue}%")
                    ->orWhere('driver_license_number', 'like', "%{$searchValue}%")
                    ->orWhere('vehicle_type', 'like', "%{$searchValue}%")
                    ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                        $userQuery->where('email', 'like', "%{$searchValue}%")
                            ->orWhere('mobile', 'like', "%{$searchValue}%");
                    });
            });
        }
        $filteredRecords = $query->count();


        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($deliveryBoy) {
                return [
                    'id' => $deliveryBoy->id,
                    'full_name' => $deliveryBoy->full_name,
                    'email' => $deliveryBoy->user->email ?? '',
                    'mobile' => $deliveryBoy->user->mobile ?? '',
                    'delivery_zone' => $deliveryBoy->deliveryZone->name ?? '',
                    'vehicle_type' => $deliveryBoy->vehicle_type ?? '',
                    'verification_status' => view('partials.status', [
                        'status' => $deliveryBoy->verification_status->value,
                    ])->render(),
                    'status' => view('partials.status', [
                        'status' => $deliveryBoy->status,
                    ])->render(),
                    'created_at' => $deliveryBoy->created_at->format('Y-m-d'),
                    'action' => view('partials.actions', [
                        'modelName' => 'delivery-boy',
                        'id' => $deliveryBoy->id,
                        'title' => $deliveryBoy->full_name,
                        'mode' => 'page_view',
                        'route' => route('admin.delivery-boys.show', $deliveryBoy->id),
                        'editPermission' => $this->editPermission,
                        'deletePermission' => $this->deletePermission
                    ])->render(),
                ];
            });

        return response()->json([
            'draw' => (int)$draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('search', '');
        $deliveryBoys = DeliveryBoy::whereHas('user', function ($q) use ($query) {
            $q->where(function ($sub) use ($query) {
                $sub->where('name', 'like', "%$query%")
                    ->orWhere('email', 'like', "%$query%")
                    ->orWhere('mobile', 'like', "%$query%");
            });
        })
            ->orWhere('full_name', 'like', "%$query%")
            ->with('user')
            ->limit(20)
            ->get();
        // Format for TomSelect
        $results = $deliveryBoys->map(function ($deliveryBoy) {
            return [
                'id' => $deliveryBoy->id,
                'value' => $deliveryBoy->id,
                'text' => $deliveryBoy->full_name . ' - ' . $deliveryBoy->user->email,
            ];
        });

        return response()->json($results);
    }
}
