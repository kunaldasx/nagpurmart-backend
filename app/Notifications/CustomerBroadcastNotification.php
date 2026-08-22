<?php

namespace App\Notifications;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CustomerBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $description,
        protected ?string $imageUrl = null,
        protected ?string $actionUrl = null,
        protected ?string $deepLink = null,
        protected int $priority = 0,
        protected bool $isActive = true,
        protected array $metadata = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', FirebaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->description,
            'sent_to' => 'customer',
            'metadata' => array_merge([
                'image_url' => $this->imageUrl,
                'action_url' => $this->actionUrl,
                'deep_link' => $this->deepLink,
                'priority' => $this->priority,
                'is_active' => $this->isActive,
                'template' => 'customer_broadcast',
            ], $this->metadata),
            'type' => NotificationTypeEnum::SYSTEM(),
        ];
    }

    public function toFirebase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->description,
            'image' => $this->imageUrl ?? '',
            'data' => [
                'type' => NotificationTypeEnum::SYSTEM(),
                'title' => $this->title,
                'body' => $this->description,
                'image_url' => $this->imageUrl ?? '',
                'action_url' => $this->actionUrl ?? '',
                'deep_link' => $this->deepLink ?? '',
                // FCM data payload values must be strings.
                'priority' => (string) $this->priority,
                'is_active' => $this->isActive ? 'true' : 'false',
                'template' => 'customer_broadcast',
            ],
        ];
    }
}
