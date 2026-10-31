<?php

namespace App\Console\Commands;

use App\Enums\GuardNameEnum;
use App\Models\SellerUser;
use App\Models\StoreProductExpiryBatch;
use App\Models\User;
use App\Notifications\ProductExpiryAlertNotification;
use App\Services\ProductExpiryBatchService;
use Illuminate\Console\Command;

class NotifyProductExpiryBatches extends Command
{
    protected $signature = 'product-expiry:notify';

    protected $description = 'Notify sellers and admins about expiring and expired inventory batches';

    public function handle(): int
    {
        $today = today()->toDateString();
        $cutoff = today()
            ->addDays(ProductExpiryBatchService::DEFAULT_WARNING_DAYS)
            ->toDateString();
        $notificationCount = 0;
        $admins = User::where('access_panel', GuardNameEnum::ADMIN())->get();

        StoreProductExpiryBatch::query()
            ->with([
                'storeProductVariant.store.seller.user',
                'storeProductVariant.productVariant.product',
            ])
            ->where('quantity_remaining', '>', 0)
            ->whereDate('expiry_date', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($batches) use ($today, $admins, &$notificationCount) {
                foreach ($batches as $batch) {
                    $status = $batch->expiry_date->toDateString() < $today ? 'expired' : 'expiring';
                    $seller = $batch->storeProductVariant->store?->seller;

                    $recipients = $admins->values();
                    if ($seller?->user) {
                        $recipients->push($seller->user);
                    }

                    if ($seller) {
                        $sellerUsers = SellerUser::query()
                            ->with('user')
                            ->where('seller_id', $seller->id)
                            ->where('status', 'active')
                            ->where('login_approval_status', 'approved')
                            ->get()
                            ->pluck('user')
                            ->filter();
                        $recipients = $recipients->merge($sellerUsers);
                    }

                    foreach ($recipients->unique('id') as $recipient) {
                        $alreadyNotified = $recipient->notifications()
                            ->where('type', ProductExpiryAlertNotification::class)
                            ->where('data->batch_id', $batch->id)
                            ->where('data->alert_status', $status)
                            ->when(
                                $status === 'expired',
                                fn ($query) => $query->where('data->policy_notice_version', 1)
                            )
                            ->exists();

                        if (!$alreadyNotified) {
                            $recipient->notify(new ProductExpiryAlertNotification($batch, $status));
                            $notificationCount++;
                        }
                    }
                }
            });

        $this->info("Created {$notificationCount} product expiry notification(s).");

        return self::SUCCESS;
    }
}
