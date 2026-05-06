<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\BrandStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

#[Group('Seller Brands')]
class SellerBrandApiController extends Controller
{
    /**
     * List brands for seller with pagination and optional search.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of brands per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search query for title/slug', type: 'string', example: 'Nestle')]
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $q = trim((string) $request->input('search', ''));

            $query = Brand::query()->where('status', BrandStatusEnum::ACTIVE());
            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%$q%")
                        ->orWhere('slug', 'like', "%$q%");
                });
            }

            $paginator = $query->orderBy('title')->paginate($perPage);
            $paginator->getCollection()->transform(fn($brand) => new BrandResource($brand));

            $response = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'data' => $paginator->items(),
            ];

            return ApiResponseType::sendJsonResponse(true, __('labels.brand_fetched_successfully') ?? 'Brands fetched successfully', $response);
        } catch (\Throwable $e) {
            Log::error('Seller brands index error: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(false, __('labels.error_fetching_brands') ?? 'Error fetching brands', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a single brand by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $brand = Brand::where('status', BrandStatusEnum::ACTIVE())->find($id);
            if (!$brand) {
                return ApiResponseType::sendJsonResponse(false, __('labels.brand_not_found') ?? 'Brand not found', [], 404);
            }

            return ApiResponseType::sendJsonResponse(true, __('labels.brand_retrieved_successfully') ?? 'Brand retrieved successfully', new BrandResource($brand));
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.brand_not_found') ?? 'Brand not found', [], 404);
        }
    }
}
