<?php

namespace App\Notifications;

use App\Events\Product\ProductStatusAfterUpdate;
use App\Enums\NotificationTypeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductStatusAfterUpdate $event)
    {
        $this->event = $event;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->event->product;

        return (new MailMessage)
            ->subject('Product Status Updated - ' . $product->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A product status has been updated.')
            ->line('Product: ' . $product->title)
            ->line('New Status: ' . $product->status)
            ->line('Verification Status: ' . $product->verification_status)
            ->action('View Product', url('admin/products/' . $product->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $product = $this->event->product;

        return [
            'product_id' => $product->id,
            'title' => 'Product Status Updated',
            'message' => 'The status of product "' . $product->title . '" has been updated.',
            'type' => 'product_status_updated',
            'metadata' => [
                'product_title' => $product->title,
                'product_status' => $product->status,
                'verification_status' => $product->verification_status,
                'seller_id' => $product->seller_id,
            ]
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $product = $this->event->product;

        return [
            'title' => 'Product Status Updated',
            'message' => 'The status of product "' . $product->title . '" has been updated.',
            'type' => NotificationTypeEnum::PRODUCT,
            'sent_to' => 'admin',
            'user_id' => $notifiable->id,
            'store_id' => $product->seller_id,
            'order_id' => null,
            'metadata' => [
                'product_id' => $product->id,
                'product_title' => $product->title,
                'product_status' => $product->status,
                'verification_status' => $product->verification_status,
                'seller_id' => $product->seller_id,
            ]
        ];
    }
}
