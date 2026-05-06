<?php

namespace App\Http\Resources\Setting;

use App\Traits\PanelAware;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorageSettingResource extends JsonResource
{
    use PanelAware;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'variable' => $this->variable,
            'value' => [
                'awsRegion' => $this->value['awsRegion'] ?? '',
                'awsBucket' => $this->value['awsBucket'] ?? '',
                'awsAssetUrl' => $this->value['awsAssetUrl'] ?? '',
            ]
        ];

        // Only admin panel can access keys
        if ($this->getPanel() === 'admin') {
            $data['value'] = array_merge($data['value'], [
                'awsAccessKeyId' => $this->value['awsAccessKeyId'] ?? '',
                'awsSecretAccessKey' => $this->value['awsSecretAccessKey'] ?? '',
            ]);
        }

        return $data;
    }
}
