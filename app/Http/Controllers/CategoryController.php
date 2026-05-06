<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\Category\CategoryBackgroundTypeEnum;
use App\Enums\CategoryStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;

    // Define media collections for easier management
    private array $mediaCollections = [
        'image' => SpatieMediaCollectionName::CATEGORY_IMAGE,
        'banner' => SpatieMediaCollectionName::CATEGORY_BANNER,
        'icon' => SpatieMediaCollectionName::CATEGORY_ICON,
        'active_icon' => SpatieMediaCollectionName::CATEGORY_ACTIVE_ICON,
        'background_image' => SpatieMediaCollectionName::CATEGORY_BACKGROUND_IMAGE,
    ];

    public function __construct()
    {
        if ($this->getPanel() === 'admin') {
            $this->editPermission = $this->hasPermission(AdminPermissionEnum::CATEGORY_EDIT());
            $this->deletePermission = $this->hasPermission(AdminPermissionEnum::CATEGORY_DELETE());
            $this->createPermission = $this->hasPermission(AdminPermissionEnum::CATEGORY_CREATE());
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'image', 'name' => 'image', 'title' => __('labels.image')],
            ['data' => 'parent', 'name' => 'parent', 'title' => __('labels.parent')],
            ['data' => 'commission', 'name' => 'commission', 'title' => __('labels.commission')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'requires_approval', 'name' => 'requires_approval', 'title' => __('labels.requires_approval')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $editPermission = $this->editPermission;
        $createPermission = $this->createPermission;

        return view($this->panelView('categories.index'), compact('columns', 'editPermission', 'createPermission'));
    }

    /**
     * Display sorting page for parent categories.
     */
    public function sort(): View
    {
        $this->authorize('viewAny', Category::class);
        // Only show home categories for sorting
        $parentCategories = Category::query()
            ->whereNull('parent_id')
            ->where('is_home_category', true)
            ->ordered()
            ->get();

        return view($this->panelView('categories.sort'), compact('parentCategories'));
    }

    /**
     * Update sort order of parent categories.
     */
    public function updateSort(Request $request): JsonResponse
    {
        try {
            // Ensure user has edit permission similar to update
            if (!$this->editPermission) {
                throw new AuthorizationException(__('labels.permission_denied'));
            }

            $request->validate([
                'categories' => 'required|array',
                'categories.*' => 'required|integer|exists:categories,id',
            ]);

            DB::beginTransaction();
            foreach ($request->categories as $index => $categoryId) {
                Category::where('id', $categoryId)->whereNull('parent_id')->update([
                    'sort_order' => $index + 1,
                ]);
            }
            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.sort_order_updated_successfully'),
                data: []
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_failed'),
                data: $e->errors()
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: []
            );
        }
    }

    /**
     * Update the list of parent categories marked as home categories.
     */
    public function updateHomeCategories(Request $request): JsonResponse
    {
        try {
            if (!$this->editPermission) {
                throw new AuthorizationException(__('labels.permission_denied'));
            }

            $validated = $request->validate([
                'home_category_ids' => 'array',
                'home_category_ids.*' => [
                    'integer',
                    'exists:categories,id',
                    function ($attribute, $value, $fail) {
                        $category = Category::find($value);
                        if ($category && !is_null($category->parent_id)) {
                            $fail(__('validation.home_category_must_be_root'));
                        }
                    }
                ],
            ]);

            $selectedIds = collect($validated['home_category_ids'] ?? [])->unique()->values();

            DB::beginTransaction();

            // Work only with parent categories - already validated above
            $parentIds = Category::query()->whereNull('parent_id')->pluck('id');

            // Set is_home_category=true for selected parent IDs, false for others
            Category::query()
                ->whereIn('id', $parentIds)
                ->update(['is_home_category' => false]);

            if ($selectedIds->isNotEmpty()) {
                Category::query()
                    ->whereIn('id', $parentIds->intersect($selectedIds))
                    ->update(['is_home_category' => true]);
            }

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.updated_successfully'),
                data: [
                    'updated_ids' => $selectedIds,
                ]
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_failed'),
                data: $e->errors()
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: []
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Category::class);
            $validated = $request->validated();

            // Set default values
            if (empty($request->status)) {
                $validated['status'] = CategoryStatusEnum::INACTIVE()();
            }
            if (empty($request->requires_approval)) {
                $validated['requires_approval'] = false;
            }

            $category = Category::create($validated);

            // Handle file uploads for creation
            $this->handleFileUploadsForStore($request, $category);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.category_created_successfully',
                data: $category,
                status: 201
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed',
                data: $e->errors(),
                status: 422
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
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
            $category = Category::findOrFail($id);
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.category_retrieved_successfully',
                data: $category->load('parent')
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.category_not_found'
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            $this->authorize('update', $category);
            $validated = $request->validated();

            // Set default values
            if (isset($validated['status']) && $validated['status'] === CategoryStatusEnum::INACTIVE()) {
//                if ($category->products()->exists()) {
//                    return ApiResponseType::sendJsonResponse(
//                        success: false,
//                        message: 'messages.category_cannot_be_deactivated_with_products',
//                    );
//                }
//                // Prevent deletion if any direct child category has products assigned
//                if ($category->children()->whereHas('products')->exists()) {
//                    return ApiResponseType::sendJsonResponse(
//                        success: false,
//                        message: 'messages.category_cannot_be_deactivated_with_products',
//                    );
//                }
            }
            if (empty($request->status)) {
                $validated['status'] = CategoryStatusEnum::INACTIVE();
            }
            if (empty($request->requires_approval)) {
                $validated['requires_approval'] = false;
            }

            // Handle background type logic
            $this->handleBackgroundTypeLogic($validated, $category);

            $category->update($validated);

            // Handle file uploads and removals for update
            $this->handleFileUploadsForUpdate($request, $category);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.category_updated_successfully',
                data: $category
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.category_not_found',
                data: [],
                status: 404
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed',
                data: $e->errors(),
                status: 422
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
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
            DB::beginTransaction();
            $category = Category::findOrFail($id);
            $this->authorize('delete', $category);
            // Prevent deletion if category has any products assigned
            if ($category->products()->exists()) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'messages.category_cannot_be_deleted_with_products',
                );
            }
            // Prevent deletion if any direct child category has products assigned
            if ($category->children()->whereHas('products')->exists()) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'messages.category_cannot_be_deleted_with_products',
                );
            }

            $category->delete();
            $category->clearMediaCollection('category');
            $category->clearMediaCollection('banner');
            $category->clearMediaCollection('icon');
            $category->clearMediaCollection('active_icon');
            $category->clearMediaCollection('background_image');
            $category->children()->update(['parent_id' => null]);
            DB::commit();
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.category_deleted_successfully',
            );
        } catch (ModelNotFoundException) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.category_not_found',
                status: 404
            );
        } catch (AuthorizationException) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: [],
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: $e->getMessage(),
                data: ['error' => $e->getMessage()],
            );
        }
    }

    /**
     * Handle file uploads during category creation
     */
    private function handleFileUploadsForStore(StoreCategoryRequest $request, Category $category): void
    {
        foreach ($this->mediaCollections as $requestField => $collectionName) {
            if ($request->hasFile($requestField)) {
                $collectionValue = is_callable($collectionName) ? $collectionName() : $collectionName;
                $category->addMediaFromRequest($requestField)->toMediaCollection($collectionValue);
            }
        }
    }

    /**
     * Handle file uploads and removals during category update
     */
    private function handleFileUploadsForUpdate(UpdateCategoryRequest $request, Category $category): void
    {
        foreach ($this->mediaCollections as $requestField => $collectionName) {
            $collectionValue = is_callable($collectionName) ? $collectionName() : $collectionName;

            if ($request->hasFile($requestField)) {
                // File exists in request, handle upload
                $this->handleSingleFileUpload($request, $category, $requestField, $collectionValue);
            } else {
                // File doesn't exist in request, remove from media library
                $this->removeMediaFromCollection($category, $collectionValue);
            }
        }
    }

    /**
     * Handle single file upload with duplicate check
     */
    private function handleSingleFileUpload(UpdateCategoryRequest $request, Category $category, string $requestField, string $collectionName): void
    {
        $newFile = $request->file($requestField);
        $existingMedia = $category->getFirstMedia($collectionName);
        $newFileName = $newFile->getClientOriginalName();

        // Only upload if file doesn't exist or is different
        if (!$existingMedia || $existingMedia->file_name !== $newFileName) {
            $category->addMedia($newFile)->toMediaCollection($collectionName);
        }
    }

    /**
     * Remove media from collection
     */
    private function removeMediaFromCollection(Category $category, string $collectionName): void
    {
        $existingMedia = $category->getFirstMedia($collectionName);
        if ($existingMedia) {
            $existingMedia->delete();
        }
    }

    /**
     * Handle background type logic
     */
    private function handleBackgroundTypeLogic(array &$validated, Category $category): void
    {
        if (isset($validated['background_type'])) {
            if ($validated['background_type'] === CategoryBackgroundTypeEnum::IMAGE()) {
                $validated['background_color'] = null;
            }
            if ($validated['background_type'] === CategoryBackgroundTypeEnum::COLOR()) {
                $this->removeMediaFromCollection($category, SpatieMediaCollectionName::CATEGORY_BACKGROUND_IMAGE());
            }
        }
    }

    public function getCategories(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'title', 'description', 'status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = Category::with('parent');

        $totalRecords = Category::count();
        $filteredRecords = $totalRecords;

        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhereHas('parent', function ($q) use ($searchValue) {
                        $q->where('title', 'like', "%{$searchValue}%");
                    });
            });
            $filteredRecords = $query->count();
        }

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'image' => view('partials.image', ['image' => (!empty($category->image) ? $category->image : asset('assets/images/category-placeholder.png')), 'title' => $category->title])->render(),
                    'status' => view('partials.status', ['status' => $category->status ?? ""])->render(),
                    'requires_approval' => '<span class="badge text-uppercase ' . ($category->requires_approval == 1 ? "bg-info-lt" : "bg-warning-lt") . '">' . ($category->requires_approval == 1 ? __('labels.required') : __('labels.not_required')) . '</span>',
                    'created_at' => $category->created_at->format('Y-m-d'),
                    'parent' => $category->parent ? $category->parent->title : 'N/A',
                    'commission' => (max($category->commission, 0)) . '%',
                    'action' => view('partials.actions', [
                        'modelName' => 'category',
                        'id' => $category->id,
                        'title' => $category->title,
                        'mode' => 'model_view',
                        'editPermission' => $this->editPermission,
                        'deletePermission' => $this->deletePermission
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

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('search'); // Get the search query
        $exceptId = $request->input('exceptId'); // Get the search query
        $findId = $request->input('find_id'); // Specific category ID to find
        $type = $request->input('type'); // Specific category ID to find

        if ($findId) {
            // If find_id is set and not empty, fetch only that category
            $categories = Category::where('id', $findId)
                ->select('id', 'title')
                ->where('status', CategoryStatusEnum::ACTIVE())
                ->get();
        } else if (!empty($type) && $type == 'root') {
            $query = $request->input('q');
            $categories = Category::where('title', 'LIKE', '%' . $query . '%')
                ->select('id', 'title')
                ->where('parent_id', null)
                ->where('status', CategoryStatusEnum::ACTIVE())
                ->get();
        } else {
            // Fetch categories matching the search query
            $categories = Category::where('title', 'like', "%{$query}%")
                ->select('id', 'title') // Fetch only required fields
                ->when($exceptId, function ($q) use ($exceptId) {
                    $q->where('id', '!=', $exceptId);
                })
                ->where('status', CategoryStatusEnum::ACTIVE())
                ->get();
        }

        $results = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'value' => $category->id,
                'text' => $category->title,
            ];
        });

        // Return the categories as JSON
        return response()->json($results);
    }

    /**
     * Show bulk upload page for categories.
     */
    public function bulkUploadPage(): View
    {
        $this->authorize('create', Category::class);
        $editPermission = $this->editPermission;
        $createPermission = $this->createPermission;
        // Reuse the panel-aware path
        return view($this->panelView('categories.bulk_upload'), compact('editPermission', 'createPermission'));
    }

    /**
     * Bulk upload categories via CSV.
     */
    public function bulkUpload(Request $request): JsonResponse
    {
        try {
            $this->authorize('create', Category::class);

            $request->validate([
                'csv-file' => 'required|file|mimes:csv,txt|max:10240', // up to 10MB
            ]);

            $file = $request->file('csv-file');
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                return ApiResponseType::sendJsonResponse(false, 'labels.file_open_error', [], 422);
            }

            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return ApiResponseType::sendJsonResponse(false, 'labels.csv_no_header', [], 422);
            }

            // Normalize headers to lowercase keys
            $normalizedHeaders = array_map(function ($h) {
                return strtolower(trim($h));
            }, $headers);

            $expected = [
                'title', 'parent_id', 'parent_title', 'description', 'status', 'requires_approval', 'commission', 'background_type', 'background_color', 'font_color', 'meta_title', 'meta_keywords', 'meta_description'
            ];

            // Ensure required header exists
            if (!in_array('title', $normalizedHeaders, true)) {
                fclose($handle);
                return ApiResponseType::sendJsonResponse(false, 'labels.csv_missing_title', [], 422);
            }

            $successCount = 0;
            $failed = [];
            $rowNumber = 1; // header is row 1

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                // Map row by header
                $data = [];
                foreach ($normalizedHeaders as $idx => $key) {
                    $data[$key] = $row[$idx] ?? null;
                }

                // Basic sanitization
                $title = trim((string)($data['title'] ?? ''));
                if ($title === '') {
                    $failed[] = ['row' => $rowNumber, 'title' => $title, 'error' => __('labels.validation_failed') . ': ' . __('validation.required', ['attribute' => 'title'])];
                    continue;
                }

                try {
                    DB::beginTransaction();

                    // Resolve parent
                    $parentId = null;
                    if (!empty($data['parent_id'])) {
                        $parentId = is_numeric($data['parent_id']) ? (int)$data['parent_id'] : null;
                        if ($parentId && !Category::where('id', $parentId)->exists()) {
                            throw new \Exception(__('labels.invalid_parent'));
                        }
                    } elseif (!empty($data['parent_title'])) {
                        $parent = Category::where('title', trim((string)$data['parent_title']))->first();
                        if ($parent) {
                            $parentId = $parent->id;
                        } else {
                            throw new \Exception(__('labels.invalid_parent'));
                        }
                    }

                    // Map status
                    $statusRaw = strtolower(trim((string)($data['status'] ?? '')));
                    $status = in_array($statusRaw, CategoryStatusEnum::values(), true) ? $statusRaw : CategoryStatusEnum::INACTIVE->value;

                    // Boolean requires_approval
                    $requiresApproval = filter_var($data['requires_approval'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    // Commission numeric 0-100
                    $commission = null;
                    if (($data['commission'] ?? '') !== '') {
                        $commission = (float)$data['commission'];
                        if ($commission < 0 || $commission > 100) {
                            throw new \Exception(__('labels.invalid_commission'));
                        }
                    }

                    // Background fields
                    $backgroundType = null;
                    $bgTypeRaw = strtolower(trim((string)($data['background_type'] ?? '')));
                    if (in_array($bgTypeRaw, ['image', 'color'], true)) {
                        $backgroundType = $bgTypeRaw;
                    }
                    $backgroundColor = $data['background_color'] ?? null;
                    $fontColor = $data['font_color'] ?? null;

                    $categoryData = [
                        'parent_id' => $parentId,
                        'title' => $title,
                        'description' => $data['description'] ?? null,
                        'status' => $status,
                        'requires_approval' => $requiresApproval,
                        'commission' => $commission,
                        'background_type' => $backgroundType,
                        'background_color' => $backgroundColor,
                        'font_color' => $fontColor,
                    ];

                    // Ensure unique title
                    if (Category::where('title', $title)->exists()) {
                        throw new \Exception(__('labels.title_already_exists'));
                    }

                    Category::create($categoryData);

                    DB::commit();
                    $successCount++;
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $failed[] = [
                        'row' => $rowNumber,
                        'title' => $title,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            fclose($handle);

            $result = [
                'success_count' => $successCount,
                'failed_count' => count($failed),
                'failed_rows' => $failed,
            ];

            return ApiResponseType::sendJsonResponse(true, 'labels.bulk_upload_completed', $result, 200);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.validation_failed', $e->errors(), 422);
        }
    }

    /**
     * Download simple CSV template for bulk upload.
     */
    public function downloadTemplate()
    {
        $this->authorize('viewAny', Category::class);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="category_bulk_template.csv"',
        ];

        $columns = ['title','parent_id','parent_title','description','status','requires_approval','commission','background_type','background_color','font_color'];
        $sample = ['Fruits','','','Fresh fruits','active','false','5','color','#FFFFFF','#000000'];

        $callback = function () use ($columns, $sample) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $columns);
            fputcsv($output, $sample);
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
