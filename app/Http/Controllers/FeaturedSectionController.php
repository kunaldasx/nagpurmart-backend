<?php

namespace App\Http\Controllers;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\AdminPermissionEnum;
use App\Enums\HomePageScopeEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Http\Requests\FeaturedSection\StoreFeaturedSectionRequest;
use App\Http\Requests\FeaturedSection\UpdateFeaturedSectionRequest;
use App\Http\Resources\FeaturedSectionResource;
use App\Http\Resources\Product\ProductResource;
use App\Models\Category;
use App\Models\FeaturedSection;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FeaturedSectionController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;
    protected bool $sortingModifyPermission = false;

    public function __construct()
    {
        if ($this->getPanel() === 'admin') {
            $this->editPermission = $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_EDIT());
            $this->deletePermission = $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_DELETE());
            $this->createPermission = $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_CREATE());
            $this->sortingModifyPermission = $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_SORTING_MODIFY());
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'slug', 'name' => 'slug', 'title' => __('labels.slug')],
            ['data' => 'scope_type', 'name' => 'scope_type', 'title' => __('labels.scope_type')],
            ['data' => 'section_type', 'name' => 'section_type', 'title' => __('labels.section_type')],
            ['data' => 'sort_order', 'name' => 'sort_order', 'title' => __('labels.sort_order')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $createPermission = $this->createPermission;

        return view($this->panelView('featured-sections.index'), compact('columns', 'editPermission', 'deletePermission', 'createPermission'));
    }

    /**
     * Display the sorting page for featured sections.
     */
    public function sort(): View
    {
        try {
            $this->authorize('sortingView', FeaturedSection::class);
            // Get global sections ordered by sort_order
            $globalSections = FeaturedSection::global()->ordered()->get();

            // Get category sections grouped by category and ordered by sort_order within each category
            $categorySections = FeaturedSection::byCategory()
                ->with('scopeCategory')
                ->ordered()
                ->get()
                ->groupBy('scope_id');

            return view($this->panelView('featured-sections.sort'), compact('globalSections', 'categorySections'));
        } catch (AuthorizationException $e) {
            abort(403, __('labels.permission_denied'));
        }
    }

    /**
     * Update the sort order of featured sections.
     */
    public function updateSort(Request $request): JsonResponse
    {
        try {
            $this->authorize('sorting', FeaturedSection::class);
            $request->validate([
                'global_sections' => 'sometimes|array',
                'global_sections.*' => 'required|integer|exists:featured_sections,id',
                'category_sections' => 'sometimes|array',
                'category_sections.*' => 'sometimes|array',
                'category_sections.*.*' => 'required|integer|exists:featured_sections,id'
            ]);

            DB::beginTransaction();

            // Update global sections sort order
            if (!empty($request->global_sections)) {
                foreach ($request->global_sections as $index => $sectionId) {
                    FeaturedSection::where('id', $sectionId)->update([
                        'sort_order' => $index + 1
                    ]);
                }
            }

            // Update category sections sort order within each category
            if (!empty($request->category_sections)) {
                foreach ($request->category_sections as $categoryId => $sectionIds) {
                    foreach ($sectionIds as $index => $sectionId) {
                        FeaturedSection::where('id', $sectionId)
                            ->where('scope_id', $categoryId)
                            ->update([
                                'sort_order' => $index + 1
                            ]);
                    }
                }
            }

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.sort_order_updated_successfully'),
                data: []
            );
        } catch (ValidationException $e) {
            DB::rollback();
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
            DB::rollback();
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
    public function store(StoreFeaturedSectionRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', FeaturedSection::class);
            $validated = $request->validated();
            if (empty($validated['status'])) {
                $validated['status'] = ActiveInactiveStatusEnum::INACTIVE();
            }
            if (empty($validated['scope_type'])) {
                $validated['scope_type'] = HomePageScopeEnum::GLOBAL();
            }
            // If scope_type is global, set scope_id to null
            if ($validated['scope_type'] === HomePageScopeEnum::GLOBAL()) {
                $validated['scope_id'] = null;
            }
            DB::beginTransaction();
            $featuredSection = FeaturedSection::create($validated);

            // Handle background images upload if background_type is 'image'
            if (($validated['background_type'] ?? null) === 'image') {
                $map = [
                    'desktop_4k_background_image' => SpatieMediaCollectionName::FEATURED_SECTION_BG_DESKTOP_4K(),
                    'desktop_fdh_background_image' => SpatieMediaCollectionName::FEATURED_SECTION_BG_DESKTOP_FHD(),
                    'tablet_background_image' => SpatieMediaCollectionName::FEATURED_SECTION_BG_TABLET(),
                    'mobile_background_image' => SpatieMediaCollectionName::FEATURED_SECTION_BG_MOBILE(),
                ];
                foreach ($map as $field => $collection) {
                    if ($request->hasFile($field)) {
                        $featuredSection->addMediaFromRequest($field)->toMediaCollection($collection);
                    }
                }
            }

            // Sync categories if provided
            if (!empty($validated['categories'])) {
                $featuredSection->categories()->sync($validated['categories']);
            }
            DB::commit();
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.featured_section_created_successfully'),
                data: $featuredSection->load('categories'),
                status: 201
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_failed'),
                data: $e->errors()
            );
        } catch (AuthorizationException) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $featuredSection = FeaturedSection::with('categories')->find($id);

            if (!$featuredSection) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.featured_section_not_found'),
                    data: [],
                );
            }

            $this->authorize('view', $featuredSection);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.featured_section_retrieved_successfully'),
                data: new FeaturedSectionResource($featuredSection)
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFeaturedSectionRequest $request, $id): JsonResponse
    {
        try {
            $featuredSection = FeaturedSection::find($id);

            if (!$featuredSection) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.featured_section_not_found'),
                    data: [],
                );
            }

            $this->authorize('update', $featuredSection);
            $validated = $request->validated();
            if (empty($validated['status'])) {
                $validated['status'] = ActiveInactiveStatusEnum::INACTIVE();
            }
            if (empty($validated['scope_type'])) {
                $validated['scope_type'] = HomePageScopeEnum::GLOBAL();
            }
            // If scope_type is global, set scope_id to null
            if ($validated['scope_type'] === HomePageScopeEnum::GLOBAL()) {
                $validated['scope_id'] = null;
            }
            DB::beginTransaction();
            $featuredSection->update($validated);

            // Handle background images upload if background_type is 'image', clear per collection when provided
            if (($validated['background_type'] ?? null) === 'image') {
                $map = [
                    'desktop_4k_background_image' => SpatieMediaCollectionName::FEATURED_SECTION_BG_DESKTOP_4K(),
                    'desktop_fdh_background_image' => SpatieMediaCollectionName::FEATURED_SECTION_BG_DESKTOP_FHD(),
                    'tablet_background_image' => SpatieMediaCollectionName::FEATURED_SECTION_BG_TABLET(),
                    'mobile_background_image' => SpatieMediaCollectionName::FEATURED_SECTION_BG_MOBILE(),
                ];
                foreach ($map as $field => $collection) {
                    if ($request->hasFile($field)) {
                        $featuredSection->clearMediaCollection($collection);
                        $featuredSection->addMediaFromRequest($field)->toMediaCollection($collection);
                    }
                }
            } elseif (($validated['background_type'] ?? null) === 'color') {
                // Clear all background images if background_type is color
                $featuredSection->clearMediaCollection(SpatieMediaCollectionName::FEATURED_SECTION_BG_DESKTOP_4K());
                $featuredSection->clearMediaCollection(SpatieMediaCollectionName::FEATURED_SECTION_BG_DESKTOP_FHD());
                $featuredSection->clearMediaCollection(SpatieMediaCollectionName::FEATURED_SECTION_BG_TABLET());
                $featuredSection->clearMediaCollection(SpatieMediaCollectionName::FEATURED_SECTION_BG_MOBILE());
            }

            // Sync categories if provided
            if (isset($validated['categories'])) {
                $featuredSection->categories()->sync($validated['categories']);
            }
            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.featured_section_updated_successfully'),
                data: $featuredSection->load('categories')
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_failed'),
                data: $e->errors()
            );
        } catch (AuthorizationException) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $featuredSection = FeaturedSection::find($id);

            if (!$featuredSection) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.featured_section_not_found'),
                    data: [],
                    status: 404
                );
            }

            $this->authorize('delete', $featuredSection);
            $featuredSection->delete();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.featured_section_deleted_successfully'),
                data: []
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        }
    }

    /**
     * Get featured sections for DataTable.
     */
    public function getFeaturedSections(Request $request): JsonResponse
    {
        try {
            $draw = $request->get('draw');
            $start = $request->get('start');
            $length = $request->get('length');
            $searchValue = $request->get('search')['value'] ?? '';
            $type = $request->get('type');
            $visibilityStatus = $request->get('visibility_status');
            $scopeType = $request->get('scope_type');

            $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
            $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

            $columns = ['id', 'title', 'slug', 'section_type', 'sort_order', 'status', 'created_at'];
            $orderColumn = $columns[$orderColumnIndex] ?? 'id';

            $query = FeaturedSection::with('scopeCategory');

            // Apply filters
            if (!empty($type)) {
                $query->where('section_type', $type);
            }

            if (!empty($visibilityStatus)) {
                $query->where('status', $visibilityStatus);
            }

            if (!empty($scopeType)) {
                $query->where('scope_type', $scopeType);
            }

            $totalRecords = FeaturedSection::count();
            $filteredRecords = $totalRecords;
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', "%$searchValue%")
                        ->orWhere('slug', 'like', "%$searchValue%")
                        ->orWhere('section_type', 'like', "%$searchValue%");
                });
                $filteredRecords = $query->count();
            }


            $editPermission = $this->editPermission;
            $deletePermission = $this->deletePermission;

            $data = $query
                ->orderBy($orderColumn, $orderDirection)
                ->skip($start)
                ->take($length)
                ->get()
                ->map(function ($featuredSection) use ($editPermission, $deletePermission) {
                    $scopeDisplay = $featuredSection->scope_type;
                    if ($featuredSection->scope_type === HomePageScopeEnum::CATEGORY() && $featuredSection->scopeCategory) {
                        $scopeDisplay .= ' (' . $featuredSection->scopeCategory->title . ')';
                    }
                    return [
                        'id' => $featuredSection->id,
                        'title' => $featuredSection->title,
                        'slug' => $featuredSection->slug,
                        'scope_type' => view('partials.status', [
                            'status' => $scopeDisplay,
                        ])->render(),
                        'section_type' => ucfirst(Str::replace("_", " ", $featuredSection->section_type)),
                        'sort_order' => $featuredSection->sort_order,
                        'status' => view('partials.status', [
                            'status' => $featuredSection->status,
                        ])->render(),
                        'created_at' => $featuredSection->created_at?->format('Y-m-d H:i:s'),
                        'action' => view('partials.actions', [
                            'modelName' => 'featured-section',
                            'id' => $featuredSection->id,
                            'editPermission' => $editPermission,
                            'deletePermission' => $deletePermission,
                            'title' => $featuredSection->title,
                            'mode' => 'model_view',
                        ])->render(),
                    ];
                });
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (Exception) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: []
            );
        }
    }
}
