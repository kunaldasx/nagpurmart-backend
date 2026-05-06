<?php

namespace App\Http\Resources;

use App\Enums\Store\StoreVerificationStatusEnum;
use App\Enums\Store\StoreVisibilityStatusEnum;
use App\Http\Resources\Product\ProductResource;
use App\Models\Store;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeaturedSectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $productsLimit = $this->additional['products_limit'] ?? 10;
        $latitude = $this->additional['latitude'] ?? null;
        $longitude = $this->additional['longitude'] ?? null;
        $zoneInfo = $this->additional['zone_info'] ?? null;

        // Get products based on location if coordinates are provided
        if ($latitude && $longitude && $zoneInfo) {
            // Get products query based on section type and categories
            $productsQuery = $this->getProductsQuery();

            // Get stores in the delivery zone
            $storeIds = Store::whereHas('zones', function ($q) use ($zoneInfo) {
                $q->where('delivery_zones.id', $zoneInfo['zone_id']);
            })
            ->where('verification_status', StoreVerificationStatusEnum::APPROVED())
            ->where('visibility_status', StoreVisibilityStatusEnum::VISIBLE())
            ->pluck('id')
            ->toArray();

            // Filter products by stores in the zone
            $productsQuery->with([
                'variants' => function ($q) use ($storeIds) {
                    $q->whereHas('storeProductVariants', function ($sq) use ($storeIds) {
                        $sq->whereIn('store_id', $storeIds);
                    });
                },
                'variants.storeProductVariants' => function ($q) use ($storeIds) {
                    $q->whereIn('store_id', $storeIds);
                },
                'variants.storeProductVariants.store',
            ])
            ->whereHas('variants.storeProductVariants', function ($q) use ($storeIds) {
                $q->whereIn('store_id', $storeIds);
            });

            // Get products with limit
            $products = $productsQuery->take($productsLimit)->get();

            // Store the user's latitude and longitude in each product for delivery time calculation
            foreach ($products as $product) {
                $product->user_latitude = $latitude;
                $product->user_longitude = $longitude;
                $product->zone_info = $zoneInfo;
            }

            // Get count for products in this zone
            $productsCount = $productsQuery->count();
        } else {
            // Get products without location filtering
            $products = $this->products($productsLimit);
            $productsCount = $this->getProductsQuery()->count();
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'style' => $this->style,
            'section_type' => $this->section_type,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'scope_type' => $this->scope_type,
            'scope_id' => $this->scope_id,
            'scope_category_slug' => $this->scopeCategory->slug ?? "",
            'scope_category_title' => $this->scopeCategory->title ?? "",
            'background_type' => $this->background_type,
            'background_color' => $this->background_color,
            'background_image' => $this->background_image ?? "",
            'desktop_4k_background_image' => $this->desktop_4k_background_image,
            'desktop_fdh_background_image' => $this->desktop_fdh_background_image,
            'tablet_background_image' => $this->tablet_background_image,
            'mobile_background_image' => $this->mobile_background_image,
            'text_color' => $this->text_color,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'scope_category' => new CategoryResource($this->whenLoaded('scopeCategory')),
            'products' => ProductResource::collection($products),
            'products_count' => $productsCount,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
