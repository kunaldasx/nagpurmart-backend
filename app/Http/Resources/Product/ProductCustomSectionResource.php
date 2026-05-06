<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCustomSectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'sort_order' => (int) ($this->sort_order ?? 0),

            'fields' => $this->whenLoaded('fields', function () {
                return $this->fields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'uuid' => $field->uuid,
                        'title' => $field->title,
                        'description' => $field->description,
                        'image' => $field->image,
                        'sort_order' => (int) ($field->pivot->sort_order ?? 0),
                    ];
                })->sortBy('sort_order')->values();
            }),
        ];
    }
}
