<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'center_latitude' => $this->center_latitude,
            'center_longitude' => $this->center_longitude,
            'radius_km' => $this->radius_km,
            'boundary_json' => $this->boundary_json,
            'rush_delivery_enabled' => $this->rush_delivery_enabled,
            'delivery_time_per_km' => $this->delivery_time_per_km,
            'rush_delivery_time_per_km' => $this->rush_delivery_time_per_km,
            'rush_delivery_charges' => $this->rush_delivery_charges,
            'regular_delivery_charges' => $this->regular_delivery_charges,
            'free_delivery_amount' => $this->free_delivery_amount,
            'distance_based_delivery_charges' => $this->distance_based_delivery_charges,
            'per_store_drop_off_fee' => $this->per_store_drop_off_fee,
            'handling_charges' => $this->handling_charges,
            'buffer_time' => $this->buffer_time,
            'status' => $this->status,
            'delivery_boy_base_fee' => $this->delivery_boy_base_fee,
            'delivery_boy_per_store_pickup_fee' => $this->delivery_boy_per_store_pickup_fee,
            'delivery_boy_distance_based_fee' => $this->delivery_boy_distance_based_fee,
            'delivery_boy_per_order_incentive' => $this->delivery_boy_per_order_incentive,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
