<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GlobalProductAttributeValueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => data_get($this->resource, 'id'),
            'title' => data_get($this->resource, 'title'),
            'swatche_value' => data_get($this->resource, 'swatche_value'),
            // Optional flag injected by controller to indicate if this value is currently selectable
            'enabled' => (bool) data_get($this->resource, 'enabled', true),
        ];
    }
}
