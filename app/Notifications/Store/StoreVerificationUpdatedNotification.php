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
use Illuminate\Support\Str;

class StoreVerificationUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Store $store,
        public ?string $oldStatus,
        public ?string $newStatus,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    public function toMail(object $notifiable): ?MailMessage
    {
        $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());
        $subject = 'Store Verification Status Updated';
        $greeting = 'Hello ' . ($notifiable->name ?? '');
        $line1 = $isSeller
            ? ('Your store "' . ($this->store->name ?? '-') . '" verification status changed from ' . (Str::replace('_', ' ', $this->oldStatus ?? '-')) . ' to ' . (Str::replace('_', ' ', $this->newStatus ?? '-')) . '.')
            : ('Store "' . ($this->store->name ?? '-') . '" verification status changed from ' . (Str::replace('_', ' ', $this->oldStatus ?? '-')) . ' to ' . (Str::replace('_', ' ', $this->newStatus ?? '-')) . '.');

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line1);
    }

    public function toFirebase($notifiable): array
    {
        return [
            'title' => 'Store Verification Updated',
            'body' => 'Verification status for ' . ($this->store->name ?? '-') . ' changed to ' . (Str::replace('_', ' ', $this->newStatus ?? '-')) . '.',
            'image' => $this->store->getFirstMediaUrl() ?? null,
            'data'  => [
                'store_id' => $this->store->id ?? null,
                'store_slug' => $this->store->slug ?? null,
                'new_status' => $this->newStatus,
                'old_status' => $this->oldStatus,
                'type' => NotificationTypeEnum::SYSTEM(),
            ],
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());
        $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());
        return [
            'title' => 'Store Verification Updated',
            'message' => 'Verification status for ' . ($this->store->name ?? '-') . ' changed from ' . (Str::replace('_', ' ', $this->oldStatus ?? '-')) . ' to ' . (Str::replace('_', ' ', $this->newStatus ?? '-')) . '.',
            'type' => NotificationTypeEnum::SYSTEM(),
            'sent_to' => $isSeller ? 'seller' : ($isAdmin ? 'admin' : 'user'),
            'user_id' => $notifiable->id ?? null,
            'store_id' => $this->store->id ?? null,
            'metadata' => [
                'store_id' => $this->store->id ?? null,
                'store_slug' => $this->store->slug ?? null,
                'new_status' => $this->newStatus,
                'old_status' => $this->oldStatus,
            ],
        ];
    }
}
