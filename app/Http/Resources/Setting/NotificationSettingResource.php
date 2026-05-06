<?php

namespace App\Http\Resources\Setting;

use App\Services\NotificationService;
use App\Traits\PanelAware;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NotificationSettingResource extends JsonResource
{
    use PanelAware;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = Auth::guard('sanctum')->user();
        $notification = app(NotificationService::class);
        $data = [
            'variable' => $this->variable,
            'value' => [
                'firebaseProjectId' => $this->resource->value['firebaseProjectId'] ?? '',
                'userRequest' => $this->resource->value['userRequest'] ?? '',
                'vapIdKey' => $this->resource->value['vapIdKey'] ?? '',
                'notification_unread_count' => $user?->id ? $notification->getUnreadCount($user->id) : 0
            ]
        ];

        $path = storage_path('app/private/settings/service-account-file.json');

        $json = file_get_contents($path);
        $jsonContent = json_decode($json, true);

        // Only admin panel can access serviceAccountFile

        if ($this->getPanel() === 'admin') {
            $data['value']['serviceAccountFile'] = $this->resource->value['serviceAccountFile'] ?? '';
            $data['value']['serviceAccountFileExist'] = file_exists($path);
            $data['value']['serviceAccountFileData'] = $jsonContent;
        }

        return $data;
    }
}
