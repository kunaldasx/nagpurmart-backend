<?php

namespace App\Http\Resources\Setting;

use App\Traits\PanelAware;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailSettingResource extends JsonResource
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
                'smtpHost' => $this->value['smtpHost'] ?? '',
                'smtpPort' => $this->value['smtpPort'] ?? '',
                'smtpEmail' => $this->value['smtpEmail'] ?? '',
                'smtpEncryption' => $this->value['smtpEncryption'] ?? '',
                'smtpContentType' => $this->value['smtpContentType'] ?? '',
            ]
        ];

        // Only show smtpPassword if admin panel
        if ($this->getPanel() === 'admin') {
            $data['value']['smtpPassword'] = $this->value['smtpPassword'] ?? '';
        }

        return $data;
    }
}
