<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\PromoStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Promo\StorePromoRequest;
use App\Http\Requests\Promo\UpdatePromoRequest;
use App\Models\Promo;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PromoController extends Controller
{
    use AuthorizesRequests, ChecksPermissions, PanelAware;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;

    public function __construct()
    {
        if ($this->getPanel() === 'admin') {
            $this->editPermission = $this->hasPermission(AdminPermissionEnum::PROMO_EDIT());
            $this->deletePermission = $this->hasPermission(AdminPermissionEnum::PROMO_DELETE());
            $this->createPermission = $this->hasPermission(AdminPermissionEnum::PROMO_CREATE());
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'code', 'name' => 'code', 'title' => __('labels.promo_code')],
            ['data' => 'promo_mode', 'name' => 'promo_mode', 'title' => __('labels.promo_mode')],
            ['data' => 'discount_type', 'name' => 'discount_type', 'title' => __('labels.discount_type')],
            ['data' => 'discount_amount', 'name' => 'discount_amount', 'title' => __('labels.discount_amount')],
            ['data' => 'start_date', 'name' => 'start_date', 'title' => __('labels.start_date')],
            ['data' => 'end_date', 'name' => 'end_date', 'title' => __('labels.end_date')],
            ['data' => 'usage_count', 'name' => 'usage_count', 'title' => __('labels.usage_count')],
            ['data' => 'max_total_usage', 'name' => 'max_total_usage', 'title' => __('labels.max_usage')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $editPermission = $this->editPermission;
        $createPermission = $this->createPermission;

        return view($this->panelView('promos.index'), compact('columns', 'editPermission', 'createPermission'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromoRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Promo::class);
            $validated = $request->validated();

            // Set default values if not provided
            if (empty($validated['usage_count'])) {
                $validated['usage_count'] = 0;
            }

            $promo = Promo::create($validated);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.promo_created_successfully'),
                data: $promo,
                status: 201
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_failed'),
                data: $e->errors(),
                status: 422
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: [],
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $promo = Promo::findOrFail($id);
//            $promo->start_date = $promo->start_date ? $promo->start_date->format('Y-m-d') : null;
//            $promo->format_end_date = $promo->end_date ? $promo->end_date->format('Y-m-d') : null;
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.promo_retrieved_successfully'),
                data: $promo
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.promo_not_found')
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromoRequest $request, $id): JsonResponse
    {
        try {
            $promo = Promo::findOrFail($id);
            $this->authorize('update', $promo);
            $validated = $request->validated();

            $promo->update($validated);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.promo_updated_successfully'),
                data: $promo
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.promo_not_found')
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_failed'),
                data: $e->errors(),
                status: 422
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: [],
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $promo = Promo::findOrFail($id);
            $this->authorize('delete', $promo);
            $promo->delete();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.promo_deleted_successfully')
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.promo_not_found')
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: [],
            );
        }
    }

    /**
     * Get promos for DataTable.
     */
    public function datatable(Request $request): JsonResponse
    {
        $query = Promo::query();

        $totalRecords = Promo::count();
        $filteredRecords = $totalRecords;
        // Handle search
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('code', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhere('discount_type', 'like', "%{$searchValue}%");
            });
            $filteredRecords = $query->count();
        }

        // Handle ordering
        if ($request->has('order')) {
            $orderColumn = $request->columns[$request->order[0]['column']]['data'];
            $orderDirection = $request->order[0]['dir'];
            $query->orderBy($orderColumn, $orderDirection);
        }


        // Handle pagination
        if ($request->has('start') && $request->has('length')) {
            $query->skip($request->start)->take($request->length);
        }

        $promos = $query->get();

        $data = $promos->map(function ($promo) {
            return [
                'id' => $promo->id,
                'code' => $promo->code,
                'promo_mode' => ucfirst($promo->promo_mode),
                'discount_type' => ucfirst(Str::replace("_", " ", $promo->discount_type)),
                'discount_amount' => $promo->discount_amount,
                'start_date' => $promo->start_date ? $promo->start_date->format('Y-m-d') : '',
                'end_date' => $promo->end_date ? $promo->end_date->format('Y-m-d') : '',
                'usage_count' => $promo->usage_count,
                'max_total_usage' => $promo->max_total_usage,
                'status' => "<div class='text-capitalize badge "
                    . ($promo->status === PromoStatusEnum::ACTIVE() ? 'bg-success-lt' : 'bg-danger-lt')
                    . "'>"
                    . $promo->status
                    . "</div>",
                'action' => $this->generateActionButtons($promo),
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Generate action buttons for DataTable.
     */
    private function generateActionButtons($promo): string
    {
        $buttons = '';

        // View button
        $buttons .= '<button class="btn me-2 p-1 btn-outline-primary me-1" data-bs-toggle="offcanvas" data-bs-target="#view-promo-offcanvas" onclick="viewPromo(' . $promo->id . ')" title="' . __('labels.view') . '">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>';

        // Edit button
        if ($this->editPermission) {
            $buttons .= '<button class="btn me-2 p-1 btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#promo-modal" onclick="editPromo(' . $promo->id . ')" title="' . __('labels.edit') . '">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>';
        }

        // Delete button
        if ($this->deletePermission) {
            $buttons .= '<button class="btn me-2 p-1 btn-outline-danger delete-promo-code"  title="' . __('labels.delete') . '" data-id="' . $promo->id . '">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3,6 5,6 21,6"></polyline>
                                <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2V6"></path>
                            </svg>
                        </button>';
        }

        return $buttons;
    }
}
