<?php

namespace App\Http\Resources\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeGeneralSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'variable' => $this->variable,
            'value' => [
                'title' => $this->value['title'] ?? '',
                'searchLabels' => $this->value['searchLabels'] ?? [],
                'backgroundType' => $this->value['backgroundType'] ?? '',
                'backgroundColor' => $this->value['backgroundColor'] ?? '#ffffff',
                'backgroundImage' => !empty($this->value['backgroundImage']) ? url('storage/' . $this->value['backgroundImage']) : '',
                'icon' => !empty($this->value['icon']) ? url('storage/' . $this->value['icon']) : '',
                'activeIcon' => !empty($this->value['activeIcon']) ? url('storage/' . $this->value['activeIcon']) : '',
                'fontColor' => $this->value['fontColor'] ?? '#000000',
                'scroll_icon' => !empty($this->value['scroll_icon']) ? url('storage/' . $this->value['scroll_icon']) : '',
                'scroll_active_icon' => !empty($this->value['scroll_active_icon']) ? url('storage/' . $this->value['scroll_active_icon']) : '',
                'scroll_background' => !empty($this->value['scroll_background']) ? url('storage/' . $this->value['scroll_background']) : '',
                'scroll_font' => !empty($this->value['scroll_font']) ? url('storage/' . $this->value['scroll_font']) : '',
            ]
        ];
    }
}
