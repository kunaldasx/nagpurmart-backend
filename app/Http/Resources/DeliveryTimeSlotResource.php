<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryTimeSlotResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->name,
            ]),
            'day_of_week' => $this->day_of_week,
            'date' => $request->input('date'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'label' => $this->start_time . ' - ' . $this->end_time,
            'max_orders' => $this->max_orders,
            'booked_orders' => $this->booked_orders ?? 0,
            'remaining_orders' => max(0, $this->max_orders - ($this->booked_orders ?? 0)),
            'is_active' => (bool) $this->is_active,
        ];
    }
}