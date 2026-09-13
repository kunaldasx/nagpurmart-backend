<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GiftSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'heading' => $this->heading,
            'sub_heading' => $this->sub_heading,
            'bg_color' => $this->bg_color,
            'font_color' => $this->font_color,
            'icon_image' => $this->icon_image,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
