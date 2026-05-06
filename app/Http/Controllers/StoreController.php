<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\BankAccountTypeEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Enums\SettingTypeEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Enums\Store\StoreStatusEnum;
use App\Enums\Store\StoreVerificationStatusEnum;
use App\Enums\Store\StoreVisibilityStatusEnum;
use App\Events\Store\StoreConfigUpdated;
use App\Events\Store\StoreCreated;
use App\Events\Store\StoreUpdated;
use App\Events\Store\StoreVerificationUpdate;
use App\Http\Requests\Seller\StoreStoreConfigurationRequest;
use App\Http\Requests\Store\StoreStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Http\Requests\Store\VerifyStoreRequest;
use App\Models\Country;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\Store;
use App\Services\DeliveryZoneService;
use App\Services\SubscriptionUsageService;
use App\Traits\SubscriptionLimitGuard;
use App\Services\SpatieMediaService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use App\Enums\Subscription\SubscriptionPlanKeyEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StoreController extends Controller
{
    use PanelAware, AuthorizesRequests, ChecksPermissions, SubscriptionLimitGuard;

    protected bool $editPermission = true;
    protected bool $deletePermission = true;
    protected bool $createPermission = true;
    protected bool $viewPermission = true;
    protected bool $verifyPermission = true;
    public int $sellerId;

    /**
     * @throws AuthorizationException
     */

    public function __construct()
    {
        $user = auth()->user();
        $seller = $user?->seller();
        $this->sellerId = $seller ? $seller->id : 0;


        if ($this->getPanel() === 'seller' || Setting::isSystemVendorTypeSingle()) {
            $this->editPermission = $this->hasPermission(SellerPermissionEnum::STORE_EDIT()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            $this->deletePermission = $this->hasPermission(SellerPermissionEnum::STORE_DELETE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            $this->createPermission = $this->hasPermission(SellerPermissionEnum::STORE_CREATE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            $this->viewPermission = $this->hasPermission(SellerPermissionEnum::STORE_VIEW()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
        }
        if ($this->getPanel() === 'admin') {
            $this->verifyPermission = $this->hasPermission(AdminPermissionEnum::STORE_VERIFY());
        }
    }

    public function index($id = null): View
    {
        $this->authorize('viewAny', Store::class);
        $seller = null;
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'name', 'name' => 'name', 'title' => __('labels.name')],
            ['data' => 'city', 'name' => 'city', 'title' => __('labels.city')],
            ['data' => 'contact_number', 'name' => 'contact_number', 'title' => __('labels.contact_number')],
            ['data' => 'verification_status', 'name' => 'verification_status', 'title' => __('labels.verification_status')],
            ['data' => 'visibility_status', 'name' => 'visibility_status', 'title' => __('labels.visibility_status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
        ];
        if ($this->getPanel() === 'seller') {
            $columns[] = ['data' => 'store_configuration', 'name' => 'store_configuration', 'title' => __('labels.store_configuration')];
            $columns[] = ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false];
        } else {
            $columns[] = ['data' => 'seller_name', 'name' => 'seller_name', 'title' => __('labels.seller_name')];
            $columns[] = ['data' => 'actions', 'name' => 'actions', 'title' => __('labels.actions')];
            if ($id != null) {
                $seller = Seller::findOrFail($id);
            }
        }
        $verificationStatus = StoreVerificationStatusEnum::values();
        $visibilityStatus = StoreVisibilityStatusEnum::values();

        // Add additional variables as in your example if needed
        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $createPermission = $this->createPermission;
        $storeCreateLimitReached = false;
        $storeCreateLimitMessage = null;

        if (
            $this->getPanel() === 'seller'
            && $createPermission
            && Setting::isSystemVendorTypeMultiple()
            && Setting::isSubscriptionEnabled()
        ) {
            $limitKey = SubscriptionPlanKeyEnum::STORE_LIMIT();
            $usageService = app(SubscriptionUsageService::class);
            $limit = $usageService->getLimit((int)$this->sellerId, $limitKey);
            $used = $usageService->getUsage((int)$this->sellerId, $limitKey);

            if ($limit >= 0 && $used >= $limit) {
                $storeCreateLimitReached = true;
                $storeCreateLimitMessage = __('labels.subscription_limit_exceeded', [
                    'key' => Str::ucfirst(Str::replace('_', ' ', $limitKey)),
                    'limit' => $limit,
                    'used' => $used,
                    'remaining' => max(0, $limit - $used),
                ]);
            }
        }

        return view($this->panelView('stores.index'), compact(
            'columns',
            'editPermission',
            'deletePermission',
            'createPermission',
            'seller',
            'verificationStatus',
            'visibilityStatus',
            'storeCreateLimitReached',
            'storeCreateLimitMessage'
        ));
    }

    /**
     * @throws AuthorizationException
     */
    public function getStores(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Store::class);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';
        $sellerId = $request->get('seller_id');
        $visibilityStatus = $request->get('visibility_status');
        $verificationStatus = $request->get('verification_status');

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'name', 'city', 'contact_number', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $query = Store::query()->with('seller.user');
        if ($visibilityStatus !== null) {
            $query->where('visibility_status', $visibilityStatus);
        }
        if ($verificationStatus !== null) {
            $query->where('verification_status', $verificationStatus);
        }
        if ($this->getPanel() === 'seller') {
            $user = auth()->user();
            $seller = $user->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(success: false, message: 'Seller not found');
            }
            $query->where('seller_id', $seller->id);
        } elseif ($sellerId) {
            // Check if sellerId contains multiple IDs (comma-separated)
            if (strpos($sellerId, ',') !== false) {
                $sellerIds = explode(',', $sellerId);
                $query->whereIn('seller_id', $sellerIds);
            } else {
                $query->where('seller_id', $sellerId);
            }
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%$searchValue%")
                    ->orWhere('city', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%")
                    ->orWhereHas('seller.user', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%$searchValue%");
                    });
            });
        }

        $totalRecords = $filteredRecords = $query->count();

        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($store) use ($editPermission, $deletePermission) {
                $data = [
                    'id' => $store->id,
                    'name' => $store->name,
                    'city' => $store->city,
                    'contact_number' => $store->contact_number,
                    'verification_status' => view('partials.status', [
                        'status' => $store->verification_status->value,
                    ])->render(),
                    'visibility_status' => view('partials.status', [
                        'status' => $store->visibility_status->value,
                    ])->render(),
                    'created_at' => $store->created_at->format('Y-m-d'),
                ];
                if ($this->getPanel() === 'seller') {
                    $data += [
                        'store_configuration' => view('seller.stores.partials.store-config-button', ['store' => $store])->render(),
                        'action' => view('partials.actions', [
                            'modelName' => 'store',
                            'id' => $store->id,
                            'title' => $store->name,
                            'mode' => 'page_view',
                            'route' => route($this->panelView('stores.edit'), $store->id),
                            'editPermission' => $editPermission,
                            'deletePermission' => $deletePermission
                        ])->render(),
                    ];
                } else {
                    $data += [
                        'seller_name' => $store->seller->user->name ?? "",
                        'actions' => view('admin.stores.partials.actions', ['store' => $store])->render(),
                    ];
                }
                return $data;
            })
            ->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', Store::class);
        $bankAccountTypes = BankAccountTypeEnum::values();
        $setting = Setting::find(SettingTypeEnum::WEB());
        $googleApiKey = $setting->value['googleMapKey'] ?? null;
        return view($this->panelView('stores.form'), compact('bankAccountTypes', 'googleApiKey'));
    }

    public function store(StoreStoreRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Store::class);
            DB::beginTransaction();
            $request->validated();
            $user = auth()->user();
            $seller = $user->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(success: false, message: 'Seller not found');
            }
            $validated = $request->safe()->except('address_proof', 'voided_check');
            $validated['seller_id'] = $seller->id;
            $isExistInZone = DeliveryZoneService::getZonesAtPoint($validated['latitude'], $validated['longitude']);
            if ($isExistInZone['exists'] === false) {
                return ApiResponseType::sendJsonResponse(success: false, message: 'Store location is not within any delivery zone');
            }
            $country = Country::where('name', $validated['country'])->firstOrFail();
            if (!empty($country->phonecode)) {
                $validated['country_code'] = $country->phonecode;
                $validated['currency_code'] = $country->currency;
            }
            $validated['verification_status'] = StoreVerificationStatusEnum::NOT_APPROVED();
            $validated['visibility_status'] = StoreVisibilityStatusEnum::DRAFT();

            // Pre-check subscription usage for store_limit only when multivendor
            if ($error = $this->ensureCanUseOrError($seller->id, SubscriptionPlanKeyEnum::STORE_LIMIT())) {
                DB::rollBack();
                dd($error);
                return $error;
            }

            // Create store after passing limit checks
            $store = Store::create($validated);

            // Update usage after adding (multivendor only)
            $this->recordUsageIfMultivendor($seller->id, SubscriptionPlanKeyEnum::STORE_LIMIT());
            if ($isExistInZone['zone_id']) {
                $store->zones()->sync([$isExistInZone['zone_id']]);
            }
            if ($request->hasFile('store_logo')) {
                SpatieMediaService::upload($store, SpatieMediaCollectionName::STORE_LOGO());
            }
            if ($request->hasFile('store_banner')) {
                SpatieMediaService::upload($store, SpatieMediaCollectionName::STORE_BANNER());
            }
            if ($request->hasFile('address_proof')) {
                SpatieMediaService::upload($store, SpatieMediaCollectionName::ADDRESS_PROOF());
            }
            if ($request->hasFile('voided_check')) {
                SpatieMediaService::upload($store, SpatieMediaCollectionName::VOIDED_CHECK());
            }
            event(new StoreCreated($store));
            DB::commit();
            return ApiResponseType::sendJsonResponse(success: true, message: 'Store created successfully', data: $store);
        } catch (AuthorizationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(success: false, message: 'Unauthorized action: ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(success: false, message: 'Error creating store: ' . $e->getMessage());
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function edit($id): View
    {
        $store = Store::findOrFail($id);
        $this->authorize('update', $store);
        $bankAccountTypes = BankAccountTypeEnum::values();
        $setting = Setting::find(SettingTypeEnum::AUTHENTICATION());
        $googleApiKey = $setting->value['googleApiKey'] ?? null;
        return view($this->panelView('stores.form'), compact('bankAccountTypes', 'store', 'googleApiKey'));
    }

    public function show($id): View
    {
        $store = Store::findOrFail($id);
        // Viewing a store should use the 'view' ability; verifying is handled in verify()
        $this->authorize('view', $store);
        $verificationStatus = StoreVerificationStatusEnum::values();
        $visibilityStatus = StoreVisibilityStatusEnum::values();
        $verifyPermission = $this->verifyPermission;
        return view($this->panelView('stores.view'), compact('store', 'verificationStatus', 'visibilityStatus', 'verifyPermission'));
    }

    public function update(UpdateStoreRequest $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $store = Store::findOrFail($id);
            $validated = $request->validated();
            $isExistInZone = DeliveryZoneService::getZonesAtPoint($validated['latitude'], $validated['longitude']);
            if ($isExistInZone['exists'] === false) {
                return ApiResponseType::sendJsonResponse(success: false, message: 'Store location is not within any delivery zone');
            }
            $country = Country::where('name', $validated['country'])->firstOrFail();
            if (!empty($country->phonecode)) {
                $validated['country_code'] = $country->phonecode;
                $validated['currency_code'] = $country->currency;
            }
            $this->authorize('update', $store);

            $store->update($validated);
            if ($request->hasFile('store_logo')) {
                SpatieMediaService::update($request, $store, SpatieMediaCollectionName::STORE_LOGO());
            }
            if ($request->hasFile('store_banner')) {
                SpatieMediaService::update($request, $store, SpatieMediaCollectionName::STORE_BANNER());
            }
            if ($isExistInZone['zone_id']) {
                $store->zones()->sync([$isExistInZone['zone_id']]);
            }
            event(new StoreUpdated($store));
            DB::commit();
            return ApiResponseType::sendJsonResponse(true, 'Store updated successfully', ['store' => $store]);
        } catch (AuthorizationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, 'Unauthorized action: ' . $e->getMessage(), []);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, 'Store not found', []);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, 'Error updating store: ' . $e->getMessage(), []);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $store = Store::findOrFail($id);
            $this->authorize('delete', $store);
            $sellerId = $store->seller_id;
            $store->delete();
            $store->zones()->detach();
            $store->productVariants()->delete();
            $store->clearMediaCollection(SpatieMediaCollectionName::VOIDED_CHECK());
            $store->clearMediaCollection(SpatieMediaCollectionName::ADDRESS_PROOF());

            // Reduce subscription usage for store_limit only when multivendor
            if ($sellerId) {
                $this->reduceUsageIfMultivendor((int)$sellerId, SubscriptionPlanKeyEnum::STORE_LIMIT());
            }
            DB::commit();
            return ApiResponseType::sendJsonResponse(true, 'Store deleted successfully', []);

        } catch (AuthorizationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, 'Unauthorized action: ' . $e->getMessage(), []);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, 'Store not found', []);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, 'Error deleting store: ' . $e->getMessage(), []);
        }
    }

    public function configuration($id): View
    {
        $store = Store::find($id);
        if (!$store) {
            abort(404, 'Store not found');
        }
        $this->authorize('view', $store);
        return view($this->panelView('stores.configuration'), compact('store'));
    }

    public function storeConfiguration(StoreStoreConfigurationRequest $request, $id): JsonResponse
    {
        try {
            $store = Store::findOrFail($id);
            $this->authorize('update', $store);
            $validated = $request->validated();
            $validated['max_delivery_distance'] = 0.00;
            $store->update($validated);
            event(new StoreConfigUpdated($store));
            return ApiResponseType::sendJsonResponse(true, 'Store Configuration updated successfully', ['store' => $store]);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'Unauthorized action: ' . $e->getMessage(), []);
        } catch (ModelNotFoundException $e) {
            return ApiResponseType::sendJsonResponse(false, 'Store not found', []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, 'Error updating store configuration: ' . $e->getMessage(), []);
        }
    }


    public function verify(VerifyStoreRequest $request, $id): JsonResponse
    {
        try {
            $this->authorize('verifyStore', Store::class);
            $store = Store::findOrFail($id);
            $validated = $request->validated();
            $store->update($validated);
            event(new StoreVerificationUpdate($store));
            return ApiResponseType::sendJsonResponse(true, 'Store verified successfully', ['store' => $store]);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'Unauthorized action: ' . $e->getMessage(), []);
        } catch (ModelNotFoundException $e) {
            return ApiResponseType::sendJsonResponse(false, 'Store not found', []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, 'Error updating store configuration: ' . $e->getMessage(), []);
        }
    }

    public function StoreList(Request $request): JsonResponse
    {
        $seller = auth()->user()->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'Seller ID is required');
        }

        $stores = Store::query()
            ->where('seller_id', $seller->id)
            ->get()
            ->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'city' => $store->city,
                    'contact_number' => $store->contact_number,
                    'verification_status' => $store->verification_status,
                    'visibility_status' => $store->visibility_status
                ];
            });

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'Stores retrieved successfully',
            data: $stores
        );
    }

    /**
     * Update a store's online/offline status (seller API)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|string|in:online,offline',
            ]);

            $store = Store::findOrFail($id);

            // Ensure the authenticated seller owns this store
            $seller = auth()->user()->seller();
            if (!$seller || (int)$store->seller_id !== (int)$seller->id) {
                throw new AuthorizationException('You do not own this store.');
            }

            $this->authorize('update', $store);

            $store->status = $request->input('status');
            $store->save();

            return ApiResponseType::sendJsonResponse(true, __('messages.store_status_updated_successfully'), [
                'store' => $store,
            ]);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, __('messages.unauthorized_action'), [
                'error' => $e->getMessage(),
            ]);
        } catch (ModelNotFoundException $e) {
            return ApiResponseType::sendJsonResponse(false, __('messages.store_not_found') ?? 'Store not found', []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, __('messages.store_status_update_failed') ?? ('Error: ' . $e->getMessage()), [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->input('search') ?? $request->input('q');
        $findId = $request->input('find_id');

        $query = Store::query()->select(['id', 'name']);

        // Scope by panel: sellers should only see their stores
        if ($this->getPanel() === 'seller') {
            $seller = auth()->user()?->seller();
            if ($seller) {
                $query->where('seller_id', $seller->id);
            } else {
                return response()->json([]);
            }
        }

        if (!empty($findId)) {
            $query->where('id', $findId);
        } elseif (!empty($q)) {
            $query->where('name', 'like', "%{$q}%");
        } else {
            // If no query, limit results to avoid heavy response
            $query->limit(20);
        }

        $stores = $query->orderBy('name')->get();

        $results = $stores->map(function ($store) {
            return [
                'id' => $store->id,
                'value' => $store->id,
                'text' => $store->name,
            ];
        });

        return response()->json($results);
    }
}
