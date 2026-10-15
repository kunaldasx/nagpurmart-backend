<?php

namespace App\Services;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Payments\RazorpayController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DeliveryBoyCodPaymentService
{
    private const PENDING_MESSAGE = 'COD Razorpay QR payment pending';
    private const COMPLETED_MESSAGE = 'COD payment collected by delivery boy';

    public function __construct(private readonly RazorpayController $razorpayController)
    {
    }

    public function createQrCode(int $orderItemId, int $deliveryBoyId): array
    {
        if (!$this->razorpayController->isConfigured() || !config('services.razorpay.cod_qr_enabled')) {
            return [
                'success' => false,
                'message' => 'Razorpay COD QR payments are not enabled.',
                'data' => [],
                'http_status' => 422,
            ];
        }

        $orderItem = OrderItem::query()
            ->whereKey($orderItemId)
            ->whereHas('order', fn ($query) => $query->where('delivery_boy_id', $deliveryBoyId))
            ->first();

        if (!$orderItem) {
            return ['success' => false, 'message' => 'Order item not found.', 'data' => [], 'http_status' => 404];
        }

        try {
            $result = DB::transaction(function () use ($orderItem, $deliveryBoyId): array {
                $order = Order::query()
                    ->whereKey($orderItem->order_id)
                    ->where('delivery_boy_id', $deliveryBoyId)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    return ['success' => false, 'message' => 'Order not found.', 'data' => [], 'http_status' => 404];
                }

                if ($order->payment_method !== PaymentTypeEnum::COD()) {
                    return ['success' => false, 'message' => 'This order does not require COD collection.', 'data' => [], 'http_status' => 422];
                }

                if ($order->payment_status === PaymentStatusEnum::COMPLETED()) {
                    return ['success' => false, 'message' => 'This order has already been paid.', 'data' => [], 'http_status' => 409];
                }

                if ($order->currency_code !== 'INR') {
                    return ['success' => false, 'message' => 'Razorpay UPI QR payments are only available for INR orders.', 'data' => [], 'http_status' => 422];
                }

                $pendingQr = OrderPaymentTransaction::query()
                    ->where('order_id', $order->id)
                    ->where('payment_method', 'upi')
                    ->where('message', self::PENDING_MESSAGE)
                    ->latest('id')
                    ->first();
                $pendingDetails = $pendingQr?->payment_details ?? [];

                if (
                    $pendingQr
                    && !empty($pendingDetails['qr_code_id'])
                    && (int) ($pendingDetails['close_by'] ?? 0) > now()->timestamp
                ) {
                    return ['success' => true, 'message' => 'Existing Razorpay UPI QR returned.', 'data' => []];
                }

                $amountPaise = (int) round((float) $order->total_payable * 100);
                $closeBy = now()->addMinutes(30)->timestamp;
                $qrCode = $this->razorpayController->createUpiQrCode([
                    'type' => 'upi_qr',
                    'name' => 'COD ' . ($order->order_number ?? $order->id),
                    'usage' => 'single_use',
                    'fixed_amount' => true,
                    'payment_amount' => $amountPaise,
                    'description' => 'COD order ' . ($order->order_number ?? $order->id),
                    'close_by' => $closeBy,
                    'notes' => [
                        'type' => 'delivery_boy_cod_qr',
                        'order_id' => (string) $order->id,
                        'order_item_id' => (string) $orderItem->id,
                        'delivery_boy_id' => (string) $deliveryBoyId,
                    ],
                ]);

                if (empty($qrCode['id']) || empty($qrCode['image_url'])) {
                    throw new \RuntimeException('Razorpay did not return a QR code and image URL.');
                }

                OrderPaymentTransaction::create([
                    'uuid' => Str::uuid()->toString(),
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'transaction_id' => $qrCode['id'],
                    'amount' => $order->total_payable,
                    'currency' => $order->currency_code,
                    'payment_method' => 'upi',
                    'payment_status' => PaymentStatusEnum::PENDING(),
                    'message' => self::PENDING_MESSAGE,
                    'payment_details' => [
                        'source' => 'delivery_boy_cod_razorpay_qr',
                        'qr_code_id' => $qrCode['id'],
                        'qr_image_url' => $qrCode['image_url'],
                        'close_by' => (int) ($qrCode['close_by'] ?? $closeBy),
                        'order_item_id' => $orderItem->id,
                        'delivery_boy_id' => $deliveryBoyId,
                    ],
                ]);

                return ['success' => true, 'message' => 'Razorpay UPI QR created successfully.', 'data' => []];
            });

            if (!$result['success']) {
                return $result;
            }

            $instructions = $this->getPaymentInstructions($orderItemId, $deliveryBoyId);
            $instructions['message'] = $result['message'];
            return $instructions;
        } catch (Throwable $exception) {
            Log::error('Unable to create delivery boy COD QR code.', [
                'order_item_id' => $orderItemId,
                'delivery_boy_id' => $deliveryBoyId,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to create a Razorpay UPI QR code.',
                'data' => [],
                'http_status' => 502,
            ];
        }
    }

    public function getPaymentInstructions(int $orderItemId, int $deliveryBoyId): array
    {
        $orderItem = OrderItem::query()
            ->whereKey($orderItemId)
            ->whereHas('order', fn ($query) => $query->where('delivery_boy_id', $deliveryBoyId))
            ->with('order')
            ->first();

        if (!$orderItem) {
            return ['success' => false, 'message' => 'Order item not found.', 'data' => []];
        }

        $order = $orderItem->order;
        $isCod = $order->payment_method === PaymentTypeEnum::COD();
        $qrEnabled = $isCod
            && $order->currency_code === 'INR'
            && $this->razorpayController->isConfigured()
            && config('services.razorpay.cod_qr_enabled');

        $qrTransaction = $isCod
            ? OrderPaymentTransaction::query()
                ->where('order_id', $order->id)
                ->where('payment_method', 'upi')
                ->whereIn('message', [self::PENDING_MESSAGE, self::COMPLETED_MESSAGE])
                ->latest('id')
                ->get()
                ->first(fn (OrderPaymentTransaction $transaction) =>
                    ($transaction->payment_details['source'] ?? null) === 'delivery_boy_cod_razorpay_qr'
                )
            : null;
        $qrDetails = $qrTransaction?->payment_details ?? [];
        $qrIsExpired = $qrTransaction
            && $qrTransaction->payment_status !== PaymentStatusEnum::COMPLETED()
            && (int) ($qrDetails['close_by'] ?? 0) <= now()->timestamp;

        $qrStatus = $qrTransaction
            ? ($qrTransaction->payment_status === PaymentStatusEnum::COMPLETED() ? 'completed' : ($qrIsExpired ? 'expired' : 'pending'))
            : null;

        $paymentCollection = $isCod
            ? OrderPaymentTransaction::query()
                ->where('order_id', $order->id)
                ->where('message', self::COMPLETED_MESSAGE)
                ->where('payment_status', PaymentStatusEnum::COMPLETED())
                ->latest('id')
                ->first()
            : null;
        $collectionDetails = $paymentCollection?->payment_details ?? [];

        return [
            'success' => true,
            'message' => 'Payment instructions fetched successfully.',
            'data' => [
                'required' => $isCod && $order->payment_status !== PaymentStatusEnum::COMPLETED(),
                'payment_status' => $order->payment_status,
                'amount' => number_format((float) $order->total_payable, 2, '.', ''),
                'currency' => $order->currency_code,
                'accepted_methods' => $isCod ? array_values(array_filter(['cash', $qrEnabled ? 'upi' : null])) : [],
                'upi_id' => $isCod && $order->currency_code === 'INR' ? config('services.upi.vpa') : null,
                'upi_id_auto_confirmation' => false,
                'razorpay_qr' => $qrEnabled && $qrTransaction ? [
                    'id' => $qrDetails['qr_code_id'] ?? $qrTransaction->transaction_id,
                    'image_url' => $qrIsExpired || $order->payment_status === PaymentStatusEnum::COMPLETED()
                        ? null
                        : ($qrDetails['qr_image_url'] ?? null),
                    'status' => $qrStatus,
                    'expires_at' => !empty($qrDetails['close_by'])
                        ? date(DATE_ATOM, (int) $qrDetails['close_by'])
                        : null,
                ] : null,
                'payment_collection' => $paymentCollection ? [
                    'status' => $paymentCollection->payment_status,
                    'method' => $paymentCollection->payment_method,
                    'transaction_reference' => $collectionDetails['transaction_reference'] ?? null,
                    'confirmed_at' => $collectionDetails['confirmed_at'] ?? $paymentCollection->created_at?->toIso8601String(),
                ] : null,
            ],
        ];
    }

    public function handleQrCodeCredited(array $payment, array $qrCode): void
    {
        $qrCodeId = $qrCode['id'] ?? null;
        $paymentId = $payment['id'] ?? null;

        if (!$qrCodeId || !$paymentId || ($payment['status'] ?? null) !== 'captured' || empty($payment['captured'])) {
            Log::warning('Ignoring incomplete Razorpay COD QR credited event.', [
                'qr_code_id' => $qrCodeId,
                'payment_id' => $paymentId,
            ]);
            return;
        }

        $transaction = OrderPaymentTransaction::query()
            ->where('transaction_id', $qrCodeId)
            ->where('payment_method', 'upi')
            ->whereIn('message', [self::PENDING_MESSAGE, self::COMPLETED_MESSAGE])
            ->first();

        if (!$transaction) {
            Log::warning('No COD transaction found for Razorpay QR payment.', ['qr_code_id' => $qrCodeId]);
            return;
        }

        $order = Order::query()->whereKey($transaction->order_id)->lockForUpdate()->first();
        $transaction = OrderPaymentTransaction::query()->whereKey($transaction->id)->lockForUpdate()->first();
        if (!$order || !$transaction) {
            Log::error('COD order or transaction missing for Razorpay QR payment.', ['qr_code_id' => $qrCodeId]);
            return;
        }

        $details = $transaction->payment_details ?? [];
        if (
            $transaction->payment_status === PaymentStatusEnum::COMPLETED()
            && ($details['transaction_reference'] ?? null) === $paymentId
        ) {
            return;
        }

        $expectedAmount = (int) round((float) $order->total_payable * 100);
        if (
            ($details['source'] ?? null) !== 'delivery_boy_cod_razorpay_qr'
            || $order->payment_method !== PaymentTypeEnum::COD()
            || $order->payment_status === PaymentStatusEnum::COMPLETED()
            || (int) ($payment['amount'] ?? 0) !== $expectedAmount
            || strtoupper((string) ($payment['currency'] ?? '')) !== 'INR'
            || $order->currency_code !== 'INR'
        ) {
            Log::error('Razorpay COD QR payment did not match the pending order.', [
                'order_id' => $order->id,
                'qr_code_id' => $qrCodeId,
                'payment_id' => $paymentId,
            ]);
            return;
        }

        $details['transaction_reference'] = $paymentId;
        $details['confirmed_at'] = now()->toIso8601String();
        $details['payment_method_detail'] = $payment['method'] ?? 'upi';
        $details['rrn'] = $payment['acquirer_data']['rrn'] ?? null;

        $transaction->update([
            'payment_status' => PaymentStatusEnum::COMPLETED(),
            'message' => self::COMPLETED_MESSAGE,
            'payment_details' => $details,
        ]);
        $order->update(['payment_status' => PaymentStatusEnum::COMPLETED()]);
    }
}