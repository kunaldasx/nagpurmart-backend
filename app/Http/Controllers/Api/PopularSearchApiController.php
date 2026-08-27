<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PopularSearchResource;
use App\Models\PopularSearch;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Popular Searches')]
class PopularSearchApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $popularSearches = PopularSearch::active()
            ->whereHas('category', fn ($query) => $query->where('status', 'active'))
            ->with(['category.parent'])
            ->ordered()
            ->paginate($request->integer('per_page', 15));

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.popular_searches_fetched_successfully',
            data: [
                'current_page' => $popularSearches->currentPage(),
                'last_page' => $popularSearches->lastPage(),
                'per_page' => $popularSearches->perPage(),
                'total' => $popularSearches->total(),
                'data' => PopularSearchResource::collection($popularSearches->items()),
            ]
        );
    }
}