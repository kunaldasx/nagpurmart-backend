<?php

namespace App\Notifications\Store;

use App\Broadcasting\FirebaseChannel;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StoreStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Store $store,
        public $oldVisibility = null,
        public $newVisibility = null,
        public $oldStatus = null,
        public $newStatus = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    public function toMail(object $notifiable): ?MailMessage
    {
        $subject = 'Store Status Updated';
        $greeting = 'Hello ' . ($notifiable->name ?? '');

        $lines = [];
        if ($this->oldVisibility !== null || $this->newVisibility !== null) {
            $lines[] = 'Visibility changed from ' . ($this->oldVisibility ? str_replace('_', ' ', $this->oldVisibility) : '-') . ' to ' . ($this->newVisibility ? str_replace('_', ' ', $this->newVisibility) : '-') . '.';
        }
        if ($this->oldStatus !== null || $this->newStatus !== null) {
            $lines[] = 'Operational status changed from ' . ($this->oldStatus ? str_replace('_', ' ', $this->oldStatus) : '-') . ' to ' . ($this->newStatus ? str_replace('_', ' ', $this->newStatus) : '-') . '.';
        }

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line('Your store "' . ($this->store->name ?? '-') . '" has an update:');
        foreach ($lines as $l) {
            $mail->line($l);
        }
        return $mail;
    }

    public function toFirebase($notifiable): array
    {
        $bodyParts = [];
        if ($this->newVisibility !== null) {
            $bodyParts[] = 'Visibility: ' . str_replace('_', ' ', $this->newVisibility);
        }
        if ($this->newStatus !== null) {
            $bodyParts[] = 'Status: ' . str_replace('_', ' ', $this->newStatus);
        }
        return [
            'title' => 'Store Status Updated',
            'body'  => implode(' | ', $bodyParts) ?: 'Your store settings have changed.',
            'image' => $this->store->getFirstMediaUrl() ?? null,
            'data'  => [
                'store_id' => $this->store->id ?? null,
                'store_slug' => $this->store->slug ?? null,
                'new_visibility' => $this->newVisibility ? str_replace('_', ' ', $this->newVisibility) : null,
                'old_visibility' => $this->oldVisibility ? str_replace('_', ' ', $this->oldVisibility) : null,
                'new_status' => $this->newStatus ? str_replace('_', ' ', $this->newStatus) : null,
                'old_status' => $this->oldStatus ? str_replace('_', ' ', $this->oldStatus) : null,
                'type' => NotificationTypeEnum::SYSTEM(),
            ],
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());
        return [
            'title' => 'Store Status Updated',
            'message' => 'Your store status/visibility updated.',
            'type' => NotificationTypeEnum::SYSTEM(),
            'sent_to' => $isSeller ? 'seller' : 'user',
            'user_id' => $notifiable->id ?? null,
            'store_id' => $this->store->id ?? null,
            'metadata' => [
                'store_id' => $this->store->id ?? null,
                'store_slug' => $this->store->slug ?? null,
                'new_visibility' => $this->newVisibility,
                'old_visibility' => $this->oldVisibility,
                'new_status' => $this->newStatus,
                'old_status' => $this->oldStatus,
            ],
        ];
    }
}
