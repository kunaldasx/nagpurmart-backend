<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductCondition\StoreProductConditionRequest;
use App\Http\Requests\ProductCondition\UpdateProductConditionRequest;
use App\Models\ProductCondition;
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

class ProductConditionController extends Controller
{
    use PanelAware, AuthorizesRequests;

    /**
     * Display a listing of the product conditions.
     */
    public function index(): View
    {
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'category.name', 'name' => 'category.name', 'title' => __('labels.category')],
            ['data' => 'alignment', 'name' => 'alignment', 'title' => __('labels.alignment')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];
        return view($this->panelView('product_conditions.index'), compact('columns'));
    }

    /**
     * Store a newly created product condition in storage.
     */
    public function store(StoreProductConditionRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', ProductCondition::class);
            $validated = $request->validated();

            $productCondition = ProductCondition::create($validated);

            return ApiResponseType::sendJsonResponse(
                true,
                'Product condition created successfully.',
                $productCondition
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'You do not have permission to create product conditions.',
                [],
                403
            );
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Failed to create product condition.',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Display the specified product condition.
     */
    public function show($id): JsonResponse
    {
        try {
            $productCondition = ProductCondition::findOrFail($id);
            return ApiResponseType::sendJsonResponse(
                true,
                'Product condition fetched successfully.',
                $productCondition
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Product condition not found.'
            );
        }
    }

    /**
     * Show the form for editing the specified product condition.
     */
    public function edit($id): JsonResponse
    {
        try {
            $productCondition = ProductCondition::findOrFail($id);
            return ApiResponseType::sendJsonResponse(
                true,
                'Product condition fetched successfully.',
                $productCondition
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Product condition not found.'
            );
        }
    }

    /**
     * Update the specified product condition in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $productCondition = ProductCondition::findOrFail($id);
            $this->authorize('update', $productCondition);
            $validated = $request->validated();

            $productCondition->update($validated);

            return ApiResponseType::sendJsonResponse(
                true,
                'Product condition updated successfully.',
                $productCondition
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Product condition not found.'
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'You do not have permission to update this product condition.',
                [],
                403
            );
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Failed to update product condition.',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Remove the specified product condition from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $productCondition = ProductCondition::find($id);

            if (!$productCondition) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'Product condition not found.'
                );
            }

            $this->authorize('delete', $productCondition);

            DB::beginTransaction();
            $productCondition->products()->update(['product_condition_id' => null]);
            $productCondition->delete();
            DB::commit();

            return ApiResponseType::sendJsonResponse(
                true,
                'Product condition deleted successfully.'
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'You do not have permission to delete this product condition.',
                [],
                403
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                false,
                'Failed to delete product condition.',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get product conditions for DataTable.
     */
    public function getProductConditions(Request $request): JsonResponse
    {
        try {
            $draw = $request->get('draw');
            $start = $request->get("start");
            $rowPerPage = $request->get("length");

            $columnIndex_arr = $request->get('order');
            $columnName_arr = $request->get('columns');
            $order_arr = $request->get('order');
            $search_arr = $request->get('search');

            $columnIndex = $columnIndex_arr[0]['column'] ?? 0;
            $columnName = $columnName_arr[$columnIndex]['data'] ?? 'id';
            $columnSortOrder = $order_arr[0]['dir'] ?? 'desc';
            $searchValue = $search_arr['value'] ?? '';

            $totalRecords = ProductCondition::count();
            $totalRecordsWithFilter = ProductCondition::where(function ($query) use ($searchValue) {
                $query->where('title', 'like', "%{$searchValue}%")
                    ->orWhere('alignment', 'like', "%{$searchValue}%");
            })->count();

            $records = ProductCondition::with('category')
                ->where(function ($query) use ($searchValue) {
                    $query->where('title', 'like', "%{$searchValue}%")
                        ->orWhere('alignment', 'like', "%{$searchValue}%");
                })
                ->orderBy($columnName, $columnSortOrder)
                ->skip($start)
                ->take($rowPerPage)
                ->get();

            $data_arr = [];

            foreach ($records as $record) {
                $data_arr[] = [
                    "id" => $record->id,
                    "title" => $record->title,
                    "category" => [
                        "name" => $record->category->title ?? 'N/A'
                    ],
                    "alignment" => $record->alignment,
                    "created_at" => $record->created_at->format('Y-m-d H:i:s'),
                    "action" => view('partials.actions', [
                        'modelName' => 'product-condition',
                        'id' => $record->id,
                        'title' => $record->title,
                        'mode' => 'model_view',
                        'editPermission' => true,
                        'deletePermission' => true
                    ])->render()
                ];
            }

            return response()->json([
                "draw" => intval($draw),
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $totalRecordsWithFilter,
                "data" => $data_arr
            ]);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Failed to fetch product conditions.',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Search product conditions.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('query');
            $productConditions = ProductCondition::where('title', 'like', "%{$query}%")
                ->limit(10)
                ->get(['id', 'title']);

            return ApiResponseType::sendJsonResponse(
                true,
                'Product conditions fetched successfully.',
                $productConditions
            );
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Failed to search product conditions.',
                ['error' => $e->getMessage()]
            );
        }
    }
}
