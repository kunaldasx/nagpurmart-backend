<?php

namespace App\Console\Commands;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Wallet\WalletTransactionTypeEnum;
use App\Models\Order;
use App\Models\OrderPromoLine;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessOrderCashback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashback:process {--days=7 : Number of days after delivery to process cashback}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process cashback for delivered orders after return period expires';

    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        parent::__construct();
        $this->walletService = $walletService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $returnPeriodDays = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($returnPeriodDays);

        $this->info("Processing cashback for orders delivered before: {$cutoffDate->format('Y-m-d H:i:s')}");

        // Find eligible orders with cashback promo that haven't been awarded yet
        $eligibleOrders = Order::with(['promoLine', 'user'])
            ->whereHas('promoLine', function ($query) {
                $query->where('cashback_flag', true)
                      ->where('is_awarded', false);
            })
            ->where('payment_status', 'paid')
            ->where('updated_at', '<=', $cutoffDate)
            ->get();

        if ($eligibleOrders->isEmpty()) {
            $this->info('No eligible orders found for cashback processing.');
            return 0;
        }

        $processed = 0;
        $errors = 0;

        foreach ($eligibleOrders as $order) {
            try {
                $promoLine = $order->promoLine;
                $cashbackAmount = $promoLine->discount_amount;

                // Credit cashback to user's wallet
                $walletData = [
                    'amount' => $cashbackAmount,
                    'currency_code' => $order->currency_code,
                    'description' => "Cashback for order #{$order->slug} (Promo: {$order->promo_code})",
                    'type' => WalletTransactionTypeEnum::DEPOSIT->value,
                ];

                $result = $this->walletService->addBalance($order->user_id, $walletData);

                if ($result['success']) {
                    // Mark cashback as awarded
                    $promoLine->update(['is_awarded' => true]);
                    $processed++;

                    $this->info("✓ Processed cashback of {$order->currency_code} {$cashbackAmount} for order #{$order->slug} (User ID: {$order->user_id})");
                } else {
                    $this->error("✗ Failed to credit wallet for order #{$order->slug}: {$result['message']}");
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->error("✗ Error processing order #{$order->slug}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->info("\n=== Cashback Processing Complete ===");
        $this->info("Total Orders Processed: {$processed}");
        $this->info("Total Errors: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}
