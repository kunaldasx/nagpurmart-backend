<?php

namespace App\Models;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\Seller\SellerWithdrawalStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @method static where(string $string, $id)
 * @method static firstOrCreate(array $array, array $array1)
 */
class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'balance',
        'currency_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'balance' => 'decimal:2',
            'blocked_balance' => 'decimal:2',
            'currency_code' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSellerBlockedBalance($sellerId): ?float
    {
        return SellerWithdrawalRequest::where('seller_id', $sellerId)
            ->where('status', SellerWithdrawalStatusEnum::PENDING())
            ->sum('amount');
    }


    public static function captureRefund($transactionId, $refundAmount = null): array
    {
        DB::beginTransaction();
        try {
            $transaction = WalletTransaction::where('id', $transactionId)->first();

            // Check if transaction exists and is eligible for refund
            if (!$transaction) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Transaction not found.',
                ];
            }

            // Only allow refunds for completed or partially refunded transactions
            if (!in_array($transaction->status, [PaymentStatusEnum::COMPLETED(), PaymentStatusEnum::PARTIALLY_REFUNDED()])) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Transaction not eligible for refund. Status: ' . $transaction->status,
                ];
            }

            $wallet = self::where('id', $transaction->wallet_id)->first();
            if (!$wallet) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Wallet not found.',
                ];
            }

            // Set refund amount - default to full transaction amount if not specified
            $refundAmount = $refundAmount ?? $transaction->amount;

            // Validate refund amount
            if ($refundAmount <= 0) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Refund amount must be greater than zero.',
                ];
            }

            if ($refundAmount > $transaction->amount) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Refund amount cannot exceed the original transaction amount.',
                ];
            }

            // For partially refunded transactions, check if we've already refunded some amount
            $totalRefundedAmount = $refundAmount;
            if ($transaction->status === PaymentStatusEnum::PARTIALLY_REFUNDED()) {
                // Calculate total refunded amount by summing all refund transactions for this original transaction
                $previousRefunds = WalletTransaction::where('order_id', $transaction->order_id)
                    ->where('transaction_type', 'refund')
                    ->where('status', PaymentStatusEnum::COMPLETED())
                    ->sum('amount');

                $totalRefundedAmount = $previousRefunds + $refundAmount;

                if ($totalRefundedAmount > $transaction->amount) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'Total refund amount would exceed the original transaction amount.',
                    ];
                }
            }

            // Update wallet balance
            $wallet->balance -= $refundAmount;
            $wallet->save();

            // Determine the new transaction status
            if ($totalRefundedAmount >= $transaction->amount) {
                // Full refund
                $transaction->status = PaymentStatusEnum::REFUNDED();
                $statusMessage = 'Wallet full refund successful.';
            } else {
                // Partial refund
                $transaction->status = PaymentStatusEnum::PARTIALLY_REFUNDED();
                $statusMessage = 'Wallet partial refund successful.';
            }

            $transactionSaved = $transaction->save();

            if ($transactionSaved) {
                DB::commit();
                return [
                    'success' => true,
                    'message' => $statusMessage,
                    'refunded_amount' => $refundAmount,
                    'total_refunded' => $totalRefundedAmount,
                    'original_amount' => $transaction->amount,
                    'status' => $transaction->status,
                ];
            } else {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Failed to update transaction status.',
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ];
        }
    }

    public static function captureRecharge($transactionId): array
    {
        try {
            $transaction = WalletTransaction::where('id', $transactionId)->first();
            if ($transaction && $transaction->status === PaymentStatusEnum::PENDING()) {
                $wallet = self::where('id', $transaction->wallet_id)->first();
                if ($wallet) {
                    $wallet->balance += $transaction->amount;
                    $wallet->save();

                    $transaction->status = PaymentStatusEnum::COMPLETED();
                    return [
                        'success' => $transaction->save(),
                        'message' => $transaction->save() ? 'Wallet recharge successful.' : 'Failed to update transaction status.',
                    ];
                }
                return [
                    'success' => false,
                    'message' => 'Wallet not found.',
                ];
            }
            return [
                'success' => false,
                'message' => 'Transaction not found or already completed.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ];
        }
    }
}
