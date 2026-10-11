<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\CatalogProductRequest;
use App\Services\CatalogProductRequestService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CatalogProductRequestApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 15);

        $paginator = CatalogProductRequest::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn (CatalogProductRequest $productRequest) => $this->present($productRequest));

        return ApiResponseType::sendJsonResponse(
            true,
            'Product requests fetched successfully.',
            ApiResponseType::responseFromPaginator($paginator)
        );
    }

    public function store(Request $request, CatalogProductRequestService $service): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'category_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reference_url' => ['nullable', 'url', 'max:2048'],
            'request_note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $user = $request->user();
            $productRequest = $service->submit($user->seller(), $user, $validated);
        } catch (ValidationException $exception) {
            return ApiResponseType::sendJsonResponse(false, $exception->validator->errors()->first(), $exception->errors(), 422);
        }

        return ApiResponseType::sendJsonResponse(
            true,
            'Product request submitted successfully.',
            $this->present($productRequest),
            201
        );
    }

    private function present(CatalogProductRequest $productRequest): array
    {
        return [
            'id' => $productRequest->id,
            'product_name' => $productRequest->product_name,
            'brand_name' => $productRequest->brand_name,
            'category_name' => $productRequest->category_name,
            'description' => $productRequest->description,
            'reference_url' => $productRequest->reference_url,
            'request_note' => $productRequest->request_note,
            'status' => $productRequest->status,
            'admin_remark' => $productRequest->admin_remark,
            'created_at' => $productRequest->created_at?->toISOString(),
            'processed_at' => $productRequest->processed_at?->toISOString(),
        ];
    }
}
