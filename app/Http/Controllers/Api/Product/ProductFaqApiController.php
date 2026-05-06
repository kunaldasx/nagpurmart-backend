<?php

namespace App\Http\Controllers\Api\Product;

use App\Enums\ActiveInactiveStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductFaqResource;
use App\Models\ProductFaq;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Product FAQs')]
class ProductFaqApiController extends Controller
{
    /**
     * Get Product FAQs.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search term to filter Product FAQs by question, answer, or product title.', type: 'string', example: 'delivery')]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $searchTerm = $request->input('search');

        $query = ProductFaq::with('product')
            ->where('status', ActiveInactiveStatusEnum::ACTIVE());

        // Add search functionality
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('question', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('answer', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhereHas('product', function ($productQuery) use ($searchTerm) {
                        $productQuery->where('title', 'LIKE', '%' . $searchTerm . '%');
                    });
            });
        }

        $productFaqs = $query->orderBy('id')->paginate($perPage);

        $productFaqs->getCollection()->transform(fn($productFaq) => new ProductFaqResource($productFaq));

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.product_faqs_fetched_successfully'),
            data: $productFaqs
        );
    }

    /**
     * Get a specific Product FAQ by Faq ID.
     */
    #[QueryParameter('id', description: 'Product FAQ ID.', type: 'int', example: 1)]
    public function show($id): JsonResponse
    {
        $productFaq = ProductFaq::with('product')
            ->where('status', ActiveInactiveStatusEnum::ACTIVE())
            ->find($id);

        if (!$productFaq) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.product_faq_not_found'),
                data: []
            );
        }

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.product_faq_fetched_successfully'),
            data: new ProductFaqResource($productFaq)
        );
    }

    /**
     * Get Product FAQs by specific product Slug.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search term to filter FAQs by question or answer.', type: 'string', example: 'delivery')]
    public function getByProduct($slug, Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $searchTerm = $request->input('search');

        $query = ProductFaq::with(['product' => function ($productQuery) use ($slug) {
            $productQuery->where('slug', $slug);
        }])
            ->where('status', ActiveInactiveStatusEnum::ACTIVE())
            ->whereHas('product', function ($productQuery) use ($slug) {
                $productQuery->where('slug', $slug);
            });

        // Add search functionality
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('question', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('answer', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $productFaqs = $query->orderBy('id')->paginate($perPage);

        $productFaqs->getCollection()->transform(fn($productFaq) => new ProductFaqResource($productFaq));

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.product_faqs_fetched_successfully'),
            data: $productFaqs
        );
    }
}
