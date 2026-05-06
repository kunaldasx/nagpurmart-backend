<?php

namespace App\Http\Controllers\Api;

use App\Enums\ActiveInactiveStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('FAQs')]
class FaqApiController extends Controller
{
    /**
     * Get all active FAQs with pagination and search.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search term to filter FAQs by question or answer.', type: 'string', example: 'delivery')]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $searchTerm = $request->input('search');

        $query = Faq::where('status', ActiveInactiveStatusEnum::ACTIVE());

        // Add search functionality
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('question', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('answer', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $faqs = $query->orderBy('id')->paginate($perPage);

        $faqs->getCollection()->transform(fn($faq) => new FaqResource($faq));

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.faqs_fetched_successfully'),
            data: $faqs
        );
    }


    /**
     * Get a specific FAQ by ID.
     */
    #[QueryParameter('id', description: 'FAQ ID.', type: 'int', example: 1)]
    public function show($id): JsonResponse
    {
        $faq = Faq::where('status', true)->find($id);

        if (!$faq) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.faq_not_found'),
                data: []
            );
        }

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.faq_fetched_successfully'),
            data: new FaqResource($faq)
        );
    }
}
