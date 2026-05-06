<?php

namespace App\Http\Resources\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class   DeliveryBoySettingResource extends JsonResource
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
                'termsCondition' => $this->value['termsCondition'] ?? '',
                'privacyPolicy' => $this->value['privacyPolicy'] ?? '',
            ]
        ];
    }
}
