<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CartRecommendationSectionResource;
use App\Http\Controllers\Controller;
use App\Models\CartRecommendationSection;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Cart Recommendations')]
class CartRecommendationApiController extends Controller
{
    #[QueryParameter('products_limit', description: 'Maximum products returned per section.', type: 'int', default: 20, example: 10)]
    public function index(Request $request): JsonResponse
    {
        $productsLimit = min(max((int) $request->input('products_limit', 20), 1), 50);
        $sections = CartRecommendationSection::active()
            ->ordered()
            ->with(['products' => function ($query) use ($productsLimit) {
                $query->take($productsLimit);
            }])
            ->get();

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'Cart recommendations fetched successfully.',
            data: [
                'sections' => CartRecommendationSectionResource::collection($sections),
            ]
        );
    }
}
