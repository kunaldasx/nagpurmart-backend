<?php

namespace App\Http\Resources\DeliveryBoy;

use App\Http\Resources\DeliveryZoneResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryBoyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'delivery_zone_id' => $this->delivery_zone_id,
            // Include full delivery zone details when relation is loaded
            'delivery_zone' => new DeliveryZoneResource($this->whenLoaded('deliveryZone')),
            'status' => $this->status,
            'full_name' => $this->full_name,
            'address' => $this->address,
            'driver_license' => $this->driver_license,
            'driver_license_number' => $this->driver_license_number,
            'vehicle_type' => $this->vehicle_type,
            'vehicle_registration' => $this->vehicle_registration,
            'verification_status' => $this->verification_status,
            'verification_remark' => $this->verification_remark,
            'created_at' => $this->created_at,
        ];
    }
}
