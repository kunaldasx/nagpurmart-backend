<?php

namespace App\Http\Controllers\Api;

use App\Enums\HighlightedSection\HighlightedSectionItemTypeEnum;
use App\Enums\HighlightedSection\HighlightedSectionTemplateEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\HighlightedSectionResource;
use App\Models\Category;
use App\Models\HighlightedSection;
use App\Types\Api\ApiResponseType;
use App\Enums\HomePageScopeEnum;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Highlighted Sections')]
class HighlightedSectionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'scope_type' => 'nullable|in:global,category',
            'category_id' => 'nullable|integer|exists:categories,id',
            'scope_category_slug' => 'nullable|string',
            'template' => 'nullable|string|in:' . implode(',', HighlightedSectionTemplateEnum::values()),
            'template_code' => 'nullable|string|in:' . implode(',', HighlightedSectionTemplateEnum::values()),
            'slug' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);
        $query = HighlightedSection::active()->ordered()->with(['items', 'scopeCategory']);
        if ($request->filled('scope_type')) $query->where('scope_type', $request->scope_type);
        if ($request->filled('category_id')) $query->where('scope_id', $request->integer('category_id'));
        if ($request->filled('template')) $query->where('template', $request->template);
        if ($request->filled('template_code')) $query->where('template', $request->template_code);
        if ($request->filled('slug')) $query->where('slug', $request->slug);
        if ($request->filled('category_id') && !$request->filled('scope_type')) $query->where('scope_type', HomePageScopeEnum::CATEGORY());
        if ($request->filled('scope_category_slug')) {
            $category = Category::where('slug', $request->scope_category_slug)->first();
            if (!$category) return ApiResponseType::sendJsonResponse(false, 'Category not found.', [], 404);
            $query->where(fn ($q) => $q->where('scope_type', 'global')->orWhere(fn ($q) => $q->where('scope_type', 'category')->where('scope_id', $category->id)));
        } elseif (!$request->filled('scope_type') && !$request->filled('category_id')) $query->where('scope_type', 'global');
        $sections = $query->paginate($request->integer('per_page', 15));
        return ApiResponseType::sendJsonResponse(true, 'Highlighted sections fetched successfully.', [
            'current_page' => $sections->currentPage(), 'last_page' => $sections->lastPage(), 'per_page' => $sections->perPage(), 'total' => $sections->total(),
            'data' => HighlightedSectionResource::collection($sections->items()),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $section = HighlightedSection::active()->with(['items', 'scopeCategory'])->where('slug', $slug)->first();
        if (!$section) return ApiResponseType::sendJsonResponse(false, 'Highlighted section not found.', [], 404);
        return ApiResponseType::sendJsonResponse(true, 'Highlighted section fetched successfully.', new HighlightedSectionResource($section));
    }

    public function templates(): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(true, 'Highlighted section templates fetched successfully.', collect(HighlightedSectionTemplateEnum::cases())->map(fn ($template) => ['code' => $template->value, 'name' => str($template->name)->headline()]));
    }

    public function itemTypes(): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(true, 'Highlighted section item types fetched successfully.', HighlightedSectionItemTypeEnum::values());
    }
}