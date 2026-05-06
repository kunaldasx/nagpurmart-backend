<?php

namespace App\Services;

use App\Enums\SpatieMediaCollectionName;
use App\Enums\Store\StoreVerificationStatusEnum;
use App\Enums\Store\StoreVisibilityStatusEnum;
use App\Events\Store\StoreCreated;
use App\Events\Store\StoreUpdated;
use App\Http\Requests\Store\StoreStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Models\Country;
use App\Models\Seller;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class StoreService
{
    public function listForSeller(Seller $seller, ?string $search, int $perPage = 15, $filters = []): LengthAwarePaginator
    {
        $query = Store::query()
            ->where('seller_id', $seller->id)
            ->orderByDesc('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['visibility_status'])) {
            $query->where('visibility_status', $filters['visibility_status']);
        }
        if (!empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }

        if ($search !== null && trim($search) !== '') {
            $q = trim($search);
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%$q%")
                    ->orWhere('description', 'like', "%$q%")
                    ->orWhere('address', 'like', "%$q%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a store for seller using validated request data.
     */
    public function createForSeller(StoreStoreRequest $request, Seller $seller): Store
    {
        return DB::transaction(function () use ($request, $seller) {
            $validated = $request->safe()->except('address_proof', 'voided_check');
            $validated['seller_id'] = $seller->id;

            $isInZone = DeliveryZoneService::getZonesAtPoint($validated['latitude'], $validated['longitude']);
            if ($isInZone['exists'] === false) {
                throw new \RuntimeException('Store location is not within any delivery zone');
            }

            $country = Country::where('name', $validated['country'])->firstOrFail();
            if (!empty($country->phonecode)) {
                $validated['country_code'] = $country->phonecode;
                $validated['currency_code'] = $country->currency;
            }

            $validated['verification_status'] = StoreVerificationStatusEnum::NOT_APPROVED();
            $validated['visibility_status'] = StoreVisibilityStatusEnum::DRAFT();

            $store = Store::create($validated);

            if (!empty($isInZone['zone_id'])) {
                $store->zones()->sync([$isInZone['zone_id']]);
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

            return $store;
        });
    }

    /**
     * Update an existing seller-owned store.
     */
    public function updateForSeller(UpdateStoreRequest $request, Store $store): Store
    {
        return DB::transaction(function () use ($request, $store) {
            $validated = $request->validated();

            $isInZone = DeliveryZoneService::getZonesAtPoint($validated['latitude'], $validated['longitude']);
            if ($isInZone['exists'] === false) {
                throw new \RuntimeException('Store location is not within any delivery zone');
            }

            $country = Country::where('name', $validated['country'])->firstOrFail();
            if (!empty($country->phonecode)) {
                $validated['country_code'] = $country->phonecode;
                $validated['currency_code'] = $country->currency;
            }

            $store->update($validated);

            if (!empty($isInZone['zone_id'])) {
                $store->zones()->sync([$isInZone['zone_id']]);
            }

            if ($request->hasFile('store_logo')) {
                SpatieMediaService::update($request, $store, SpatieMediaCollectionName::STORE_LOGO());
            }
            if ($request->hasFile('store_banner')) {
                SpatieMediaService::update($request, $store, SpatieMediaCollectionName::STORE_BANNER());
            }

            event(new StoreUpdated($store));

            return $store;
        });
    }

    public function deleteForSeller(Store $store): void
    {
        $store->delete();
    }

    public function updateStatusForSeller(Store $store, string $status): Store
    {
        $store->status = $status;
        $store->save();
        return $store;
    }

    /**
     * Ensure the given store belongs to the seller; throw if not found/owned.
     */
    public function findOwnedOrFail(Seller $seller, int $id): Store
    {
        $store = Store::findOrFail($id);
        if ((int)$store->seller_id !== (int)$seller->id) {
            throw new ModelNotFoundException('Store not found');
        }
        return $store;
    }
}

