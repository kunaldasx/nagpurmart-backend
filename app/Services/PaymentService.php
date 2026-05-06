<?php

namespace App\Services;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Payments\FlutterwaveController;
use App\Http\Controllers\Payments\PaystackController;
use App\Http\Controllers\Payments\RazorpayController;
use App\Http\Controllers\Payments\StripeController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    protected RazorpayController $razorpayController;
    protected StripeController $stripeController;

    protected PaystackController $paystackController;
    protected FlutterwaveController $flutterwaveController;

    public function __construct()
    {
        $this->razorpayController = app(RazorpayController::class);
        $this->stripeController = app(StripeController::class);
        $this->paystackController = app(PaystackController::class);
        $this->flutterwaveController = app(FlutterwaveController::class);
    }

    public function prePaymentOrderVerification($transactionId, $order): void
    {
        switch ($order->payment_method) {
            case PaymentTypeEnum::RAZORPAY():
            case PaymentTypeEnum::STRIPE():
            case PaymentTypeEnum::PAYSTACK():
                $transaction = OrderPaymentTransaction::where('transaction_id', $transactionId)->get()->first();

                if ($transaction !== null && $transaction->order_id === null) {
                    $transaction->update(['order_id' => $order->id]);
                    $status = $transaction->payment_status;

                    if ($status === PaymentStatusEnum::COMPLETED()) {
                        Order::capturePayment($transaction->order_id);
                        OrderItem::capturePayment($transaction->order_id);
                        Log::info("operation place order: order update from webhook");
                    }
                    Log::info("operation place order: transaction exist");
                    return;
                }
                Log::info("operation place order: transaction not exist");
                if ($order->payment_method !== PaymentTypeEnum::PAYSTACK()) {
                    $paymentInfo = [
                        'transaction_id' => $transactionId ?? null,
                        'amount' => $order->total_payable,
                        'currency' => $order->currency_code,
                        'payment_method' => $order->payment_method,
                        'message' => 'Payment pending verification',
                    ];
                    $this->makeOrderPaymentTransaction($order, $paymentInfo, PaymentStatusEnum::PENDING());
                }
                break;
            default:
                break;
        }
    }

    public function postPaymentInitialtion($order, $redirectUrl = null): array
    {
        switch ($order->payment_method) {
            case PaymentTypeEnum::FLUTTERWAVE():
                $data = [
                    'order_id' => $order->id,
                    'amount' => $order->total_payable,
                    'redirect_url' => $redirectUrl ?? config('app.url')
                ];
                $result = $this->flutterwaveController->createOrder($data);
                if ($result['success'] === false) {
                    throw new \Exception($result['message']);
                }
                return $result;
            default:
                return [];
        }

    }

    public function makeOrderPaymentTransaction($order, $paymentData, $status): void
    {
        $transactionData = [
            'uuid' => Str::uuid(),
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'amount' => $paymentData['amount'] ?? 0,
            'currency' => $paymentData['currency'] ?? 'INR',
            'payment_method' => $paymentData['payment_method'] ?? 'unknown',
            'payment_status' => $status,
            'message' => $paymentData['message'] ?? null,
            'payment_details' => isset($paymentData['details']) ? json_encode($paymentData['details']) : null,
        ];

        if (empty($paymentData['transaction_id'])) {
            OrderPaymentTransaction::create($transactionData);
        } else {
            OrderPaymentTransaction::updateOrCreate(
                ['transaction_id' => $paymentData['transaction_id']],
                $transactionData
            );
        }
    }

    public function verifyOnlinePayment(array $data): array
    {
        $verifiedResponse = [
            'success' => true,
            'message' => __('labels.payment_verified_successfully'),
        ];
        switch ($data['payment_type']) {
            case PaymentTypeEnum::RAZORPAY():
                $verificationResult = $this->razorpayController->verifyPayment($data);
                if (!$verificationResult['success']) {
                    return [
                        'success' => false,
                        'message' => $verificationResult['message'],
                        'data' => $verificationResult['data']
                    ];
                }
                return $verifiedResponse;
            case PaymentTypeEnum::STRIPE():
                $verification = $this->stripeController->verifyPayment($data['transaction_id']);
                if (!$verification['success']) {
                    return [
                        'success' => false,
                        'message' => $verification['message'],
                        'data' => $verification['data']
                    ];
                }
                return $verifiedResponse;
            case PaymentTypeEnum::PAYSTACK():

                $verification = $this->paystackController->verifyPayment($data['transaction_id']);
                if (!$verification['success']) {
                    return [
                        'success' => false,
                        'message' => $verification['message'],
                        'data' => $verification['data'] ?? []
                    ];
                }
                return $verifiedResponse;
            case PaymentTypeEnum::COD():
            case PaymentTypeEnum::FLUTTERWAVE():
            case PaymentTypeEnum::WALLET():
                return $verifiedResponse;

            default:
                return [
                    'success' => false,
                    'message' => __('labels.unsupported_payment_method'),
                    'data' => []
                ];
        }
    }

    public function processOrderRefund($paymentMethod, $transactionId): void
    {
        try {
            DB::beginTransaction();
            $transaction = OrderPaymentTransaction::where('transaction_id', $transactionId)->get()->first();
            if (!$transaction) {
                Log::error('Error in refunding payment: Transaction not found', ['transaction_id' => $transactionId]);
            }
            switch ($paymentMethod) {
                case PaymentTypeEnum::RAZORPAY():
                    $result = $this->razorpayController->refundPayment($transactionId);
                    if ($result['success'] === false) {
                        Log::error('Razorpay Error in refunding payment: ' . $result['message'], ['transaction_id' => $transactionId]);
                        return;
                    }
                    Log::info('Razorpay Payment refunded successfully', ['transaction_id' => $transactionId]);
                    DB::commit();
                    break;
                case PaymentTypeEnum::STRIPE():
                    $result = $this->stripeController->refundPayment($transactionId);
                    if ($result['success'] === false) {
                        Log::error('Stripe Error in refunding payment: ' . $result['message'], ['transaction_id' => $transactionId]);
                        return;
                    }
                    Log::info('Stripe Payment refunded successfully', ['transaction_id' => $transactionId]);
                    DB::commit();
                    break;
                case PaymentTypeEnum::PAYSTACK():
                    $result = $this->paystackController->refundPayment($transactionId);
                    if ($result['success'] === false) {
                        Log::error('Paystack Error in refunding payment: ' . $result['message'], ['transaction_id' => $transactionId]);
                        return;
                    }
                    Log::info('Paystack Payment refunded successfully', ['transaction_id' => $transactionId]);
                    DB::commit();
                    break;
                default:
                    Log::error('Error in refunding payment: Unsupported payment method');
                    break;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing refund', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            [
                'success' => false,
                'message' => __('labels.error_processing_refund') . ': ' . $e->getMessage(),
                'data' => []
            ];
            return;
        }

    }

}
