<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\AddressTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\AddressResource;
use App\Models\Address;
use App\Models\DeliveryZone;
use App\Services\DeliveryZoneService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

#[Group('Addresses')]
class AddressApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     */
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 20)]
    #[QueryParameter('query', description: 'Search term to filter addresses across multiple fields (address_line1, address_line2, city, landmark, state, zipcode, mobile).', type: 'string', example: 'main street')]
    #[QueryParameter('address_type', description: 'Filter by address type.', type: 'string', example: 'home')]
    #[QueryParameter('city', description: 'Filter by city name (partial match).', type: 'string', example: 'New York')]
    #[QueryParameter('state', description: 'Filter by state name (partial match).', type: 'string', example: 'California')]
    #[QueryParameter('country', description: 'Filter by country name (partial match).', type: 'string', example: 'United States')]
    #[QueryParameter('sort', description: 'Field to sort by.', type: 'string', example: 'city')]
    #[QueryParameter('order', description: 'Sort order.', type: 'string', example: 'asc')]
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('zone_id', description: 'Delivery Zone ID', type: 'int', example: 1)]
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Address::where('user_id', Auth::id());
            $perPage = $request->input('per_page', 15); // Default to 15 items per page if not provided

            // Search functionality
            if ($request->filled('query')) {
                $searchQuery = $request->input('query');
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('address_line1', 'LIKE', '%' . $searchQuery . '%')
                        ->orWhere('address_line2', 'LIKE', '%' . $searchQuery . '%')
                        ->orWhere('city', 'LIKE', '%' . $searchQuery . '%')
                        ->orWhere('landmark', 'LIKE', '%' . $searchQuery . '%')
                        ->orWhere('state', 'LIKE', '%' . $searchQuery . '%')
                        ->orWhere('zipcode', 'LIKE', '%' . $searchQuery . '%')
                        ->orWhere('mobile', 'LIKE', '%' . $searchQuery . '%');
                });
            }

            // Filter by address type
            if ($request->filled('address_type')) {
                $query->where('address_type', $request->input('address_type'));
            }

            // Filter by city
            if ($request->filled('city')) {
                $query->where('city', 'LIKE', '%' . $request->input('city') . '%');
            }

            // Filter by state
            if ($request->filled('state')) {
                $query->where('state', 'LIKE', '%' . $request->input('state') . '%');
            }

            // Filter by country
            if ($request->filled('country')) {
                $query->where('country', 'LIKE', '%' . $request->input('country') . '%');
            }

            // Sorting functionality
            if ($request->filled('sort') && $request->filled('order')) {
                $sortField = $request->input('sort');
                $sortOrder = $request->input('order');

                // Validate sort field to prevent SQL injection
                $allowedSortFields = [
                    'id', 'address_line1', 'city', 'state', 'zipcode',
                    'address_type', 'country', 'created_at', 'updated_at'
                ];

                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortOrder);
                }
            } else {
                // Default sorting by created_at desc
                $query->orderBy('created_at', 'desc');
            }

            // Zone filtering
            $zoneId = $request->input('zone_id');
            if ($zoneId) {
                $zone = DeliveryZone::find($zoneId);
                if ($zone) {
                    // Get all addresses first, then filter by zone
                    $allAddresses = $query->get();
                    $filteredAddresses = $allAddresses->filter(function ($address) use ($zone) {
                        return DeliveryZoneService::isPointInPolygon(
                            $zone,
                            floatval($address->latitude),
                            floatval($address->longitude)
                        );
                    })->values();

                    // Manually paginate the filtered collection
                    $page = $request->input('page', 1);
                    $addresses = new LengthAwarePaginator(
                        $filteredAddresses->forPage($page, $perPage),
                        $filteredAddresses->count(),
                        $perPage,
                        $page,
                        ['path' => $request->url(), 'query' => $request->query()]
                    );
                } else {
                    $addresses = $query->paginate($perPage);
                }
            } else {
                // Use Laravel's paginate method
                $addresses = $query->paginate($perPage);
            }

            // Transform the paginated data using AddressResource
            $addresses->getCollection()->transform(function ($address) {
                return new AddressResource($address);
            });

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'Addresses retrieved successfully',
                data: [
                    'current_page' => $addresses->currentPage(),
                    'last_page' => $addresses->lastPage(),
                    'per_page' => $addresses->perPage(),
                    'total' => $addresses->total(),
                    'data' => $addresses->items(),
                ]);
        } catch (Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Failed to retrieve addresses',
                data: null,
                status: 500
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'address_line1' => 'required|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'city' => 'required|string|max:100',
                'landmark' => 'nullable|string|max:255',
                'state' => 'required|string|max:100',
                'zipcode' => 'required|string|max:10',
                'mobile' => 'required|string|max:15',
                'address_type' => ['sometimes', 'required', new Enum(AddressTypeEnum::class)],
                'country' => 'required|string|max:100',
                'country_code' => 'required|string|max:3',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);
            $isExist = DeliveryZoneService::existsAtPoint($validatedData['latitude'], $validatedData['longitude']);
            if (!$isExist) {
                return ApiResponseType::sendJsonResponse(
                    success: false, message: __('labels.delivery_zone_not_found')
                );
            }

            $validatedData['user_id'] = Auth::id();

            $address = Address::create($validatedData);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'Address created successfully',
                data: new AddressResource($address),
                status: 201
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Validation failed',
                data: $e->errors(),
                status: 422
            );
        } catch (Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Failed to create address',
                data: null,
                status: 500
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $address = Address::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$address) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'Address not found',
                    data: null,
                    status: 404
                );
            }

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'Address retrieved successfully',
                data: new AddressResource($address)
            );
        } catch (Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Failed to retrieve address',
                data: null,
                status: 500
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $address = Address::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$address) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'Address not found',
                    data: null,
                    status: 404
                );
            }

            $validatedData = $request->validate([
                'address_line1' => 'sometimes|required|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'city' => 'sometimes|required|string|max:100',
                'landmark' => 'nullable|string|max:255',
                'state' => 'sometimes|required|string|max:100',
                'zipcode' => 'sometimes|required|string|max:10',
                'mobile' => 'sometimes|required|string|max:15',
                'address_type' => ['required', new Enum(AddressTypeEnum::class)],
                'country' => 'sometimes|required|string|max:100',
                'country_code' => 'sometimes|required|string|max:5',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $address->update($validatedData);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'Address updated successfully',
                data: new AddressResource($address->fresh())
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Validation failed',
                data: $e->errors(),
                status: 422
            );
        } catch (Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Failed to update address',
                data: null,
                status: 500
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $address = Address::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$address) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'Address not found',
                    data: null,
                    status: 404
                );
            }

            $address->delete();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'Address deleted successfully',
                data: null
            );
        } catch (Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Failed to delete address',
                data: null,
                status: 500
            );
        }
    }
}
