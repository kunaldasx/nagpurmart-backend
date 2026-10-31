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
        $statusMessage = $this->alertStatus === 'expired'
            ? "expired on {$expiryDate}. This stock is still included in on-hand inventory and may remain sellable until you review it. The system has not blocked or written it off; review the batch and record any required stock adjustment."
            : "is nearing expiry on {$expiryDate}. Review the batch and rotate stock as appropriate. Expiry alerts do not automatically block sales or adjust inventory.";

        return [
            'type' => 'product_expiry',
            'sent_to' => $notifiable->access_panel?->value === 'admin' ? 'admin' : 'seller',
            'title' => $this->alertStatus === 'expired' ? 'Expired stock needs review' : 'Product stock nearing expiry',
            'message' => "{$storeVariant->productVariant?->product?->title} has {$this->batch->quantity_remaining} unit(s) in batch "
                . ($this->batch->batch_code ?: "#{$this->batch->id}")
                . ' ' . $statusMessage,
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
            'requires_manual_review' => true,
            'stock_adjusted_automatically' => false,
            'policy_notice_version' => $this->alertStatus === 'expired' ? 1 : null,
            'days_until_expiry' => (int) today()->diffInDays($this->batch->expiry_date, false),
            'metadata' => [
                'event' => 'product_expiry_alert',
                'batch_id' => $this->batch->id,
                'alert_status' => $this->alertStatus,
            ],
            'url' => $notifiable->access_panel?->value === 'admin'
                ? '/admin/inventory/expiry-management?status=' . $this->alertStatus
                : '/seller/inventory/expiry-management?status=' . $this->alertStatus,
        ];
    }
}
