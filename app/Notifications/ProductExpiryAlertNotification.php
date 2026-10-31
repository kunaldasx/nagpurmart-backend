<?php

namespace App\Notifications;

use App\Models\StoreProductExpiryBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductExpiryAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly StoreProductExpiryBatch $batch,
        private readonly string $alertStatus
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $storeVariant = $this->batch->storeProductVariant;
        $expiryDate = $this->batch->expiry_date->toDateString();

        return [
            'type' => 'product_expiry',
            'sent_to' => $notifiable->access_panel?->value === 'admin' ? 'admin' : 'seller',
            'title' => $this->alertStatus === 'expired' ? 'Product stock expired' : 'Product stock nearing expiry',
            'message' => "{$storeVariant->productVariant?->product?->title} has {$this->batch->quantity_remaining} unit(s) in batch "
                . ($this->batch->batch_code ?: "#{$this->batch->id}")
                . ($this->alertStatus === 'expired' ? ' that expired on ' : ' expiring on ')
                . $expiryDate . '.',
            'batch_id' => $this->batch->id,
            'store_product_variant_id' => $this->batch->store_product_variant_id,
            'store_id' => $storeVariant->store_id,
            'seller_id' => $storeVariant->store?->seller_id,
            'product_id' => $storeVariant->productVariant?->product_id,
            'product_name' => $storeVariant->productVariant?->product?->title,
            'batch_code' => $this->batch->batch_code,
            'quantity_remaining' => $this->batch->quantity_remaining,
            'expiry_date' => $expiryDate,
            'alert_status' => $this->alertStatus,
            'days_until_expiry' => (int) today()->diffInDays($this->batch->expiry_date, false),
            'metadata' => [
                'event' => 'product_expiry_alert',
                'batch_id' => $this->batch->id,
                'alert_status' => $this->alertStatus,
            ],
            'url' => $notifiable->access_panel?->value === 'admin'
                ? '/admin/inventory/expiry-batches?status=' . $this->alertStatus
                : '/api/seller/inventory/expiry-batches?status=' . $this->alertStatus,
        ];
    }
}
