<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Http\Resources\PopularSearchResource;
use App\Models\Category;
use App\Models\PopularSearch;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PopularSearchController extends Controller
{
    use ChecksPermissions, PanelAware;

    private bool $viewPermission;
    private bool $createPermission;
    private bool $editPermission;

    public function __construct()
    {
        $this->viewPermission = $this->hasPermission(AdminPermissionEnum::POPULAR_SEARCH_VIEW());
        $this->createPermission = $this->hasPermission(AdminPermissionEnum::POPULAR_SEARCH_CREATE());
        $this->editPermission = $this->hasPermission(AdminPermissionEnum::POPULAR_SEARCH_EDIT());
    }

    public function index(): View
    {
        abort_unless($this->viewPermission, 403);

        return view($this->panelView('popular-searches.index'), [
            'editPermission' => $this->editPermission || $this->createPermission,
        ]);
    }

    public function sort(): View
    {
        abort_unless($this->viewPermission, 403);

        return view($this->panelView('popular-searches.sort'), [
            'popularSearches' => PopularSearch::with('category.parent')->ordered()->get(),
            'editPermission' => $this->editPermission,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->createPermission) {
            throw new AuthorizationException(__('labels.permission_denied'));
        }
        return $this->save($request);
    }

    public function show(int $id): JsonResponse
    {
        abort_unless($this->viewPermission, 403);
        $popularSearch = PopularSearch::with('category.parent')->findOrFail($id);

        return ApiResponseType::sendJsonResponse(true, 'labels.popular_search_retrieved_successfully', new PopularSearchResource($popularSearch));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->editPermission) {
            throw new AuthorizationException(__('labels.permission_denied'));
        }
        $popularSearch = PopularSearch::findOrFail($id);
        return $this->save($request, $popularSearch);
    }

    private function save(Request $request, ?PopularSearch $popularSearch = null): JsonResponse
    {
        if (!$this->createPermission && !$this->editPermission) {
            throw new AuthorizationException(__('labels.permission_denied'));
        }

        try {
            $validated = $request->validate([
                'category_id' => [
                    'required',
                    'integer',
                    Rule::exists('categories', 'id')->where(fn ($query) => $query->where('status', 'active')),
                    Rule::unique('popular_searches', 'category_id')->ignore($popularSearch?->id),
                ],
                'sort_order' => 'nullable|integer|min:0',
                'status' => 'nullable|in:active,inactive',
            ]);
            $validated['status'] = $validated['status'] ?? 'inactive';
            $validated['sort_order'] = $validated['sort_order'] ?? ((int) PopularSearch::max('sort_order') + 1);

            $popularSearch = $popularSearch ?: new PopularSearch();
            $popularSearch->fill($validated)->save();

            return ApiResponseType::sendJsonResponse(
                true,
                $popularSearch->wasRecentlyCreated ? 'labels.popular_search_created_successfully' : 'labels.popular_search_updated_successfully',
                new PopularSearchResource($popularSearch->load('category.parent')),
                $popularSearch->wasRecentlyCreated ? 201 : 200
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.validation_failed', $e->errors(), 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->editPermission) {
            throw new AuthorizationException(__('labels.permission_denied'));
        }
        PopularSearch::findOrFail($id)->delete();
        return ApiResponseType::sendJsonResponse(true, 'labels.popular_search_deleted_successfully', []);
    }

    public function updateSort(Request $request): JsonResponse
    {
        if (!$this->editPermission) {
            throw new AuthorizationException(__('labels.permission_denied'));
        }

        $validated = $request->validate([
            'popular_searches' => 'required|array',
            'popular_searches.*' => 'required|integer|exists:popular_searches,id',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['popular_searches'] as $index => $id) {
                PopularSearch::whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });

        return ApiResponseType::sendJsonResponse(true, 'labels.sort_order_updated_successfully', []);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($this->viewPermission, 403);
        $search = $request->input('search.value', '');
        $query = PopularSearch::with('category.parent');
        $total = PopularSearch::count();

        if ($search !== '') {
            $query->whereHas('category', fn ($category) => $category->where('title', 'like', "%{$search}%"));
        }

        $rows = $query->orderBy('sort_order')->orderBy('id')
            ->skip((int) $request->input('start', 0))->take((int) $request->input('length', 10))
            ->get()->map(fn (PopularSearch $item) => [
                'id' => $item->id,
                'category' => $item->category?->title,
                'parent' => $item->category?->parent?->title ?? '-',
                'sort_order' => $item->sort_order,
                'status' => view('partials.status', ['status' => $item->status])->render(),
                'action' => view('partials.actions', [
                    'modelName' => 'popular-search', 'id' => $item->id,
                    'editPermission' => $this->editPermission, 'deletePermission' => $this->editPermission,
                    'title' => $item->category?->title, 'mode' => 'model_view',
                ])->render(),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw'), 'recordsTotal' => $total,
            'recordsFiltered' => $search === '' ? $total : $query->count(), 'data' => $rows,
        ]);
    }
}