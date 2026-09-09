<?php

namespace App\Http\Controllers;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\HighlightedSection\HighlightedSectionItemTypeEnum;
use App\Enums\HomePageScopeEnum;
use App\Http\Requests\HighlightedSection\StoreHighlightedSectionRequest;
use App\Http\Requests\HighlightedSection\UpdateHighlightedSectionRequest;
use App\Http\Resources\HighlightedSectionResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HighlightedSection;
use App\Models\Product;
use App\Enums\SpatieMediaCollectionName;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HighlightedSectionController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => 'ID'],
            ['data' => 'title', 'name' => 'title', 'title' => 'Title'],
            ['data' => 'template', 'name' => 'template', 'title' => 'Template'],
            ['data' => 'scope', 'name' => 'scope', 'title' => 'Scope'],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false],
        ];
        $this->authorize('viewAny', HighlightedSection::class);
        $createPermission = $this->hasPermission('highlighted_section.create');
        $editPermission = $this->hasPermission('highlighted_section.edit');
        $deletePermission = $this->hasPermission('highlighted_section.delete');
        return view($this->panelView('highlighted-sections.index'), compact('columns', 'createPermission', 'editPermission', 'deletePermission'));
    }

    public function store(StoreHighlightedSectionRequest $request): JsonResponse
    {
        return $this->save($request->validated());
    }

    public function show(int $id): JsonResponse
    {
        $section = HighlightedSection::with(['items', 'scopeCategory'])->find($id);
        if (!$section) return ApiResponseType::sendJsonResponse(false, 'Highlighted section not found.', [], 404);
        $this->authorize('view', $section);
        return ApiResponseType::sendJsonResponse(true, 'Highlighted section retrieved successfully.', new HighlightedSectionResource($section));
    }

    public function update(UpdateHighlightedSectionRequest $request, int $id): JsonResponse
    {
        $section = HighlightedSection::find($id);
        if (!$section) return ApiResponseType::sendJsonResponse(false, 'Highlighted section not found.', [], 404);
        $this->authorize('update', $section);
        return $this->save($request->validated(), $section);
    }

    public function destroy(int $id): JsonResponse
    {
        $section = HighlightedSection::find($id);
        if (!$section) return ApiResponseType::sendJsonResponse(false, 'Highlighted section not found.', [], 404);
        $this->authorize('delete', $section);
        $section->delete();
        return ApiResponseType::sendJsonResponse(true, 'Highlighted section deleted successfully.', []);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', HighlightedSection::class);
        $query = HighlightedSection::with('scopeCategory');
        $search = $request->input('search.value');
        if ($search) $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
        $total = HighlightedSection::count();
        $filtered = (clone $query)->count();
        $rows = $query->orderBy('id', 'desc')->skip((int) $request->input('start', 0))->take((int) $request->input('length', 10))->get();
        return response()->json([
            'draw' => (int) $request->input('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered,
            'data' => $rows->map(fn ($section) => [
                'id' => $section->id, 'title' => $section->title,
                'template' => Str::headline($section->template?->value ?? $section->template),
                'scope' => Str::headline($section->scope_type) . ($section->scopeCategory ? ' (' . $section->scopeCategory->title . ')' : ''),
                'status' => view('partials.status', ['status' => $section->status])->render(),
                'action' => view('partials.actions', ['modelName' => 'highlighted-section', 'id' => $section->id, 'editPermission' => $this->hasPermission('highlighted_section.edit'), 'deletePermission' => $this->hasPermission('highlighted_section.delete'), 'title' => $section->title, 'mode' => 'model_view'])->render(),
            ]),
        ]);
    }

    private function save(array $validated, ?HighlightedSection $section = null): JsonResponse
    {
        $validated['status'] ??= ActiveInactiveStatusEnum::INACTIVE();
        $validated['scope_type'] ??= HomePageScopeEnum::GLOBAL();
        if ($validated['scope_type'] === HomePageScopeEnum::GLOBAL()) $validated['scope_id'] = null;
        $items = $validated['items'];
        unset($validated['items']);
        DB::transaction(function () use (&$section, $validated, $items) {
            $section = $section ?: new HighlightedSection();
            $section->fill($validated);
            $section->save();
            $existingItems = $section->items()->get()->keyBy('id');
            $keptItemIds = [];
            foreach ($items as $index => $item) {
                $image = $item['image'] ?? null;
                $itemId = $item['id'] ?? null;
                unset($item['id']);
                unset($item['image']);
                $model = match ($item['item_type']) {
                    HighlightedSectionItemTypeEnum::PRODUCT() => Product::find($item['item_id']),
                    HighlightedSectionItemTypeEnum::CATEGORY() => Category::find($item['item_id']),
                    HighlightedSectionItemTypeEnum::BRAND() => Brand::find($item['item_id']),
                    default => null,
                };
                if (!$model) abort(422, "Selected {$item['item_type']} does not exist.");
                if ($itemId && !$existingItems->has($itemId)) abort(422, 'The selected highlighted item does not belong to this section.');
                $sectionItem = $itemId && $existingItems->has($itemId)
                    ? $existingItems->get($itemId)
                    : $section->items()->make();
                $sectionItem->fill($item + ['sort_order' => $item['sort_order'] ?? $index]);
                $sectionItem->save();
                $keptItemIds[] = $sectionItem->id;
                if ($image) {
                    $sectionItem->clearMediaCollection(SpatieMediaCollectionName::HIGHLIGHTED_SECTION_ITEM_IMAGE());
                    $sectionItem->addMedia($image)->toMediaCollection(SpatieMediaCollectionName::HIGHLIGHTED_SECTION_ITEM_IMAGE());
                }
            }
            $section->items()->whereNotIn('id', $keptItemIds)->get()->each->delete();
        });
        return ApiResponseType::sendJsonResponse(true, $section->wasRecentlyCreated ? 'Highlighted section created successfully.' : 'Highlighted section updated successfully.', new HighlightedSectionResource($section->load(['items', 'scopeCategory'])), $section->wasRecentlyCreated ? 201 : 200);
    }
}