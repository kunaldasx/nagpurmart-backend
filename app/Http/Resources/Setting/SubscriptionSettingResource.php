<?php

namespace App\Http\Resources\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionSettingResource extends JsonResource
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
                'enableSubscription' => $this->value['enableSubscription'] ?? null,
                'subscriptionHeading' => $this->value['subscriptionHeading'] ?? __('labels.subscription_plans'),
                'subscriptionDescription' => $this->value['subscriptionDescription'] ?? __('labels.subscription_description_text'),
            ]
        ];
    }
}
