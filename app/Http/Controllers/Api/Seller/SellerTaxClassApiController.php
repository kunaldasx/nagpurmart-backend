<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\TaxClass;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Seller Tax Classes')]
class SellerTaxClassApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * List tax classes with pagination and optional search
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of tax classes per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search query for tax class title or tax rate title', type: 'string', example: 'Standard')]
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', TaxClass::class);

            // For now, tax classes are global; sellers can view them
            $perPage = (int) $request->input('per_page', 15);
            $q = trim((string) $request->input('search', ''));

            $query = TaxClass::query()->with('taxRates')->orderByDesc('id');

            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%$q%")
                        ->orWhereHas('taxRates', function ($qr) use ($q) {
                            $qr->where('title', 'like', "%$q%");
                        });
                });
            }

            $paginator = $query->paginate($perPage);

            $response = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'data' => $paginator->items(),
            ];

            return ApiResponseType::sendJsonResponse(true, __('labels.tax_class_fetched_successfully'), $response);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.tax_class_not_found'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show a single tax class with its rates
     */
    public function show(int $id): JsonResponse
    {
        try {
            $this->authorize('viewAny', TaxClass::class);

            $taxClass = TaxClass::with('taxRates')->findOrFail($id);

            return ApiResponseType::sendJsonResponse(true, __('labels.tax_class_fetched_successfully'), $taxClass);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.tax_class_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        }
    }
}
