<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HighlightedSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id, 'title' => $this->title, 'slug' => $this->slug,
            'subtitle' => $this->subtitle, 'template' => $this->template?->value ?? $this->template,
            'scope_type' => $this->scope_type, 'scope_id' => $this->scope_id,
            'scope_category' => $this->whenLoaded('scopeCategory', fn () => new CategoryResource($this->scopeCategory)),
            'background_color' => $this->background_color, 'font_color' => $this->font_color,
            'button_color' => $this->button_color, 'button_text_color' => $this->button_text_color,
            'badge_color' => $this->badge_color, 'badge_text_color' => $this->badge_text_color,
            'deal_badge_color' => $this->deal_badge_color, 'deal_price_color' => $this->deal_price_color,
            'sort_order' => $this->sort_order, 'status' => $this->status,
            'is_bgimage' => (bool) $this->is_bgimage,
            'background_images' => $this->background_images,
            'hero_image' => $this->hero_image,
            'powered_by_image' => $this->powered_by_image,
            'items' => HighlightedSectionItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}