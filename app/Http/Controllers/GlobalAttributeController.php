<?php

namespace App\Http\Controllers;

use App\Enums\Attribute\AttributeTypesEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Http\Requests\Attribute\StoreAttributeRequest;
use App\Http\Requests\Attribute\UpdateAttributeRequest;
use App\Models\GlobalProductAttribute;
use App\Models\GlobalProductAttributeValue;
use App\Services\AttributeService;
use App\Services\SpatieMediaService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GlobalAttributeController extends Controller
{
    use PanelAware, AuthorizesRequests, ChecksPermissions;

    public float $sellerId;
    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;
    protected AttributeService $attributeService;

    public function __construct(AttributeService $attributeService)
    {
        $this->attributeService = $attributeService;
        $user = auth()->user();
        $seller = $user?->seller();
        $this->sellerId = $seller ? $seller->id : 0;
        if ($this->getPanel() === 'seller') {
            $user = auth()->user();
            $this->editPermission = $this->hasPermission(SellerPermissionEnum::ATTRIBUTE_EDIT()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            $this->deletePermission = $this->hasPermission(SellerPermissionEnum::ATTRIBUTE_DELETE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            $this->createPermission = $this->hasPermission(SellerPermissionEnum::ATTRIBUTE_CREATE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
        }
    }

    // List all global product attributes (with values)
    public function index(): View
    {
        $this->authorize('viewAny', GlobalProductAttribute::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'swatche_type', 'name' => 'swatche_type', 'title' => __('labels.swatche_type')],
            ['data' => 'values_count', 'name' => 'values_count', 'title' => __('labels.values_count')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $valuesColumns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'global_attribute_id', 'name' => 'global_attribute_id', 'title' => __('labels.global_attribute_id')],
            ['data' => 'attribute_title', 'name' => 'attribute_title', 'title' => __('labels.attribute_title')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'swatche_value', 'name' => 'swatche_value', 'title' => __('labels.swatche_value')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];
        $editPermission = $this->editPermission;
        $createPermission = $this->createPermission;
        $attributeTypes = AttributeTypesEnum::values();
        return view($this->panelView('global_product_attributes.index'), compact('columns', 'attributeTypes', 'valuesColumns', 'editPermission', 'createPermission'));
    }

    // Show a single attribute (with values)
    public function edit($id): JsonResponse
    {
        $attribute = GlobalProductAttribute::findOrFail($id);
        return ApiResponseType::sendJsonResponse(success: true, message: __('labels.attribute_fetched_successfully'), data: $attribute);
    }

    public function store(StoreAttributeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->authorize('create', GlobalProductAttribute::class);

            $seller = auth()->user()->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null);
            }

            $attribute = $this->attributeService->createAttribute($validated, $seller->id);
            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.attribute_created_successfully'),
                $attribute
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.no_permission_create_attributes'),
                [],
                403
            );
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.failed_to_save_attribute'),
                ['error' => $e->getMessage()]
            );
        }
    }

    public function update(UpdateAttributeRequest $request, $id): JsonResponse
    {
        try {
            $attribute = GlobalProductAttribute::findOrFail($id);
            $this->authorize('update', $attribute);

            $validated = $request->validated();
            $attribute->update($validated);
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.attribute_updated_successfully'),
                data: $attribute
            );

        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.attribute_not_found'));
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.no_permission_update_attribute'),
                data: [],
                status: 403
            );
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.failed_to_update_attribute'),
                data: ['error' => $e->getMessage()]
            );
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $attribute = GlobalProductAttribute::find($id);

            if (!$attribute) {
                return ApiResponseType::sendJsonResponse(false, __('labels.attribute_not_found'), null);
            }
            $this->authorize('delete', $attribute);

            if ($attribute->productVariantAttribute()->exists()) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.attribute_used_in_variants'), // <-- Create this translation
                    null
                );
            }
            DB::beginTransaction();
            $attribute->values()->delete();
            $attribute->delete();
            DB::commit();
            return ApiResponseType::sendJsonResponse(true, __('labels.attribute_and_values_deleted'), null);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.no_permission_delete_attribute'),
                []
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_delete_attribute_and_values'), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get attributes for datatable
     */
    public function getAttributes(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', GlobalProductAttribute::class);

            $draw = $request->get('draw');
            $start = $request->get('start');
            $length = $request->get('length');
            $searchValue = $request->get('search')['value'] ?? '';

            $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
            $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';
            $swatcheType = $request->get('swatche_type');

            $columns = ['id', 'title', 'swatche_type', 'created_at'];
            $orderColumn = $columns[$orderColumnIndex] ?? 'id';

            $seller = auth()->user()->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(success: false, message: __('labels.seller_not_found'), data: []);
            }
            $query = GlobalProductAttribute::query();
            $query->where('seller_id', $seller->id);
            $totalRecords = $query->count();
            // Search filter
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', "%{$searchValue}%")
                        ->orWhere('swatche_type', 'like', "%{$searchValue}%");
                });
            }
            if (!empty($swatcheType)) {
                $query->where('swatche_type', $swatcheType);
            }
            $filteredRecords = $query->count();

            // Check if the table exists and has records
            try {

                $data = $query
                    ->orderBy($orderColumn, $orderDirection)
                    ->skip($start)
                    ->take($length)
                    ->get()
                    ->map(function ($attribute) {
                        return [
                            'id' => $attribute->id,
                            'title' => $attribute->title,
                            'swatche_type' => $attribute->swatche_type,
                            'values_count' => $attribute->values()->count(),
                            'created_at' => $attribute->created_at->format('Y-m-d'),
                            'action' => view('partials.actions', [
                                'modelName' => 'attribute-create-update',
                                'id' => $attribute->id,
                                'title' => $attribute->title,
                                'mode' => 'model_view',
                                'editPermission' => $this->editPermission,
                                'deletePermission' => $this->deletePermission
                            ])->render(),
                        ];
                    })
                    ->toArray();
            } catch (\Exception $e) {
                // If there's an error (e.g., table doesn't exist), return empty data
                $totalRecords = 0;
                $filteredRecords = 0;
                $data = [];
            }

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        }
        catch (\Exception $e) {
            return response()->json([
                'draw' => intval($request->get('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Search attributes for dropdown
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('search'); // Get the search query
        $exceptId = $request->input('exceptId'); // Get the search query
        $findId = $request->input('find_id'); // Specific category ID to find

        if ($findId) {
            // If find_id is set and not empty, fetch only that category
            $attributes = GlobalProductAttribute::where('id', $findId)
                ->select('id', 'title')
                ->where('seller_id', $this->sellerId)
                ->get();
        } else {
            // Fetch categories matching the search query
            $attributes = GlobalProductAttribute::where('title', 'LIKE', '%' . $query . '%')
                ->select('id', 'title', 'swatche_type') // Fetch only required fields
                ->when($exceptId, function ($q) use ($exceptId) {
                    $q->where('id', '!=', $exceptId);
                })
                ->where('seller_id', $this->sellerId)
                ->get();
        }
        $results = $attributes->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'value' => $attribute->id,
                'text' => $attribute->title,
                'swatche_type' => $attribute->swatche_type,
            ];
        });
        // Return the categories as JSON
        return response()->json($results);
    }
}
