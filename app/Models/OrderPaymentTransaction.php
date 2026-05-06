<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrderPaymentTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'order_id',
        'user_id',
        'transaction_id',
        'amount',
        'currency',
        'payment_method',
        'payment_status',
        'message',
        'payment_details'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'json',
//        'payment_status' => PaymentStatusEnum::class
    ];

    /**
     * Get all possible payment statuses
     *
     * @return array
     */
    public static function getPaymentStatuses(): array
    {
        return PaymentStatusEnum::values();
    }

    /**
     * Get the order associated with the payment transaction
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who made the payment
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set Razorpay payment details
     *
     * @param string $orderId
     * @param string $paymentId
     * @param string $signature
     * @return void
     */
    public function setRazorpayDetails(string $orderId, string $paymentId, string $signature): void
    {
        $details = $this->payment_details ?? [];
        $details['razorpay_order_id'] = $orderId;
        $details['razorpay_payment_id'] = $paymentId;
        $details['razorpay_signature'] = $signature;
        $this->payment_details = $details;
    }

    public static function saveTransaction(array $data, string $paymentId, $paymentMethod, $paymentStatus)
    {
        return OrderPaymentTransaction::updateOrCreate(
            ['transaction_id' => $paymentId],
            [
                'uuid' => Str::uuid()->toString(),
                'order_id' => $data['order_id'] ?? null,
                'user_id' => $data['user_id'],
                'transaction_id' => $paymentId,
                'amount' => $data['amount'] / 100,
                'currency' => $data['currency'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'message' => 'Payment Captured verification',
                'payment_details' => $data ?? null,
            ]
        );
    }

    public static function createTransaction(array $data, $paymentMethod, $paymentStatus)
    {
        return OrderPaymentTransaction::create([
                'uuid' => Str::uuid()->toString(),
                'order_id' => $data['order_id'] ?? null,
                'user_id' => $data['user_id'],
                'transaction_id' => $data['payment_id'] ?? "",
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'message' => 'Payment Captured verification',
                'payment_details' => $data ?? null,
            ]
        );
    }
}
