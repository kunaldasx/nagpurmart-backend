<?php

namespace App\Http\Controllers\Payments;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SettingTypeEnum;
use App\Enums\Subscription\SellerSubscriptionStatusEnum;
use App\Enums\Subscription\SubscriptionTransactionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTransaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\SettingService;
use App\Types\Api\ApiResponseType;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\SubscriptionTransaction;
use App\Models\SellerSubscription;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeController extends Controller
{

    private string $secretKey;
    private string $webhookSecret;
    private string $publishableKey;

    public function __construct(SettingService $settingService)
    {
        $setting = $settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
        $this->secretKey = $setting->value['stripeSecretKey'] ?? "";
        $this->webhookSecret = $setting->value['stripeWebhookSecretKey'] ?? "";
        $this->publishableKey = $setting->value['stripePublishableKey'] ?? "";
    }

    public function createOrderPaymentIntent(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'currency' => 'nullable|string|size:3',
            ]);
            Stripe::setApiKey($this->secretKey);

            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100, // amount in cents (₹100 = 10000)
                'currency' => $request->currency ?? 'usd',
                'metadata' => [
                    'user_id' => auth()->id(),
                    'type' => 'order_payment', // Specify wallet recharge
                ],
            ]);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.payment_intent_created_successfully',
                data: ['clientSecret' => $paymentIntent->client_secret]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'messages.something_went_wrong',
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Render a web view for paying a subscription transaction via Stripe Elements
     */
    public function showSubscriptionPaymentPage(string $transaction): Factory|View
    {
        $txn = SubscriptionTransaction::with('plan')
            ->where('uuid', $transaction)
            ->firstOrFail();

        if ((string)$txn->payment_gateway !== (string)PaymentTypeEnum::STRIPE()) {
            abort(404);
        }

        if ((string)$txn->status !== 'pending') {
            return view('payments.subscription-status', ['transaction' => $txn]);
        }

        Stripe::setApiKey($this->secretKey);

        // Ensure PaymentIntent exists
        $paymentIntentId = $txn->transaction_id;
        if (empty($paymentIntentId)) {
            $intentRes = $this->createSubscriptionPaymentIntent([
                'amount' => (float)$txn->amount,
                'description' => 'Subscription: ' . (optional($txn->plan)->name ?? 'Plan'),
                'subscription_transaction_id' => $txn->id,
                'seller_subscription_id' => $txn->seller_subscription_id,
                'seller_id' => $txn->seller_id,
                'user_id' => auth()->id(),
            ]);
            if (!($intentRes['success'] ?? false)) {
                abort(400, $intentRes['message'] ?? 'Unable to initialize Stripe payment');
            }
            $paymentIntentId = $intentRes['data']['id'] ?? null;
            if ($paymentIntentId) {
                $txn->transaction_id = $paymentIntentId;
                $txn->save();
            }
        }

        // Retrieve client secret
        $intent = PaymentIntent::retrieve($paymentIntentId);
        $clientSecret = $intent->client_secret;

        return view('payments.subscription-payment', [
            'gateway' => 'stripe',
            'publishableKey' => $this->publishableKey,
            'clientSecret' => $clientSecret,
            'amount' => (float)$txn->amount,
            'currency' => $intent->currency ?? 'usd',
            'transactionId' => $txn->id,
            'transactionUuid' => $txn->uuid,
            'sellerId' => $txn->seller_id,
            'subscriptionId' => $txn->seller_subscription_id,
            'plan' => $txn->plan,
            'planName' => optional($txn->plan)->name ?? 'Subscription Plan',
            'payButtonLabel' => __('labels.pay_with_stripe'),
        ]);
    }

    /**
     * Create a PaymentIntent for subscription purchases
     */
    public function createSubscriptionPaymentIntent(array $data): array
    {
        try {
            $validated = validator(
                $data,
                [
                    'amount' => 'required|numeric|min:1',
                    'currency' => 'nullable|string',
                    'description' => 'nullable|string',
                    'subscription_transaction_id' => 'required|integer',
                    'seller_subscription_id' => 'required|integer',
                    'seller_id' => 'required|integer',
                    'user_id' => 'nullable|integer',
                ])->validate();

            Stripe::setApiKey($this->secretKey);

            $paymentIntent = PaymentIntent::create([
                'amount' => (int)round($validated['amount'] * 100),
                'currency' => $validated['currency'] ?? 'usd',
                'description' => $validated['description'] ?? 'Seller Subscription',
                'metadata' => [
                    'type' => 'subscription_payment',
                    'subscription_transaction_id' => (string)$validated['subscription_transaction_id'],
                    'seller_subscription_id' => (string)$validated['seller_subscription_id'],
                    'seller_id' => (string)$validated['seller_id'],
                    'user_id' => (string)($validated['user_id'] ?? ''),
                ],
            ]);

            return [
                'success' => true,
                'message' => 'Payment intent created successfully',
                'data' => [
                    'id' => $paymentIntent->id,
                    'clientSecret' => $paymentIntent->client_secret,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    public function createWalletRechargeOrderPaymentIntent(array $data): array
    {
        try {
            $validated = validator(
                $data,
                [
                    'amount' => 'required|numeric|min:1',
                    'currency' => 'nullable|string',
                    'description' => 'nullable|string',
                    'transaction_id' => 'required|string',
                ])->validate();
            Stripe::setApiKey($this->secretKey);

            $paymentIntent = PaymentIntent::create([
                'amount' => $validated['amount'] * 100, // amount in cents (₹100 = 10000)
                'currency' => $validated['currency'] ?? 'usd',
                'metadata' => [
                    'user_id' => auth()->id(),
                    'type' => 'wallet_recharge', // Specify wallet recharge
                    'transaction_id' => $validated['transaction_id'],
                ],
            ]);

            return [
                'success' => true,
                'message' => 'Payment intent created successfully',
                'data' => ['clientSecret' => $paymentIntent->client_secret],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    public function verifyPayment(string $paymentIntentId): array
    {
        try {
            Stripe::setApiKey($this->secretKey);

            // Retrieve PaymentIntent from Stripe
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            // Check status
            if ($paymentIntent->status === 'succeeded') {
                return [
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'data' => $paymentIntent,
                ];
            }

            return [
                'success' => false,
                'message' => "Payment not completed, status: {$paymentIntent->status}",
                'data' => $paymentIntent,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $endpointSecret = $this->webhookSecret;

//        Log::info("Stripe Webhook Payload: " . $payload);

        DB::beginTransaction();
        try {
            // Verify webhook signature
            $event = Webhook::constructEvent($payload, $signature, $endpointSecret);
            $data = $event->data->object;


            Log::info("Stripe Webhook Payload: " . $data);
            $paymentType = $data->metadata->type ?? 'order_payment';
            $transaction = $this->findTransaction(paymentType: $paymentType, data: $data);

            Log::info("webhook " . ($transaction ? "transaction found" : "transaction not found"));
            Log::info("Stripe Event Type: " . $event->type);


            $this->handleWebhookEvent($event, $paymentType, $data, $transaction);

            DB::commit();
            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::info("Stripe Webhook Payload: " . $payload);
            Log::error("Stripe Webhook Error: " . $e->getMessage());
            return response()->json(['error' => 'Server Error'], 500);
        }
    }

    private function handleWebhookEvent(object $event, string $paymentType, object $data, $transaction): void
    {
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded(paymentType: $paymentType, data: $data, transaction: $transaction);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($paymentType, $transaction, $event);
                break;

            case 'payment_intent.amount_capturable_updated':
                // Equivalent to Razorpay 'authorized' event
                Log::info('event payment_intent.amount_capturable_updated Payment authorized (amount capturable updated)', ['payment_id' => $transactionId ?? ""]);
                break;

            case 'charge.refunded':
                $this->handleRefund($paymentType, $transaction, $data);
                break;

            default:
                Log::warning("Unhandled Stripe Webhook Event: {$event->type}");
                break;
        }
    }

    private function handlePaymentSucceeded(string $paymentType, object $data, $transaction): void
    {
        $paymentId = $data->id;
        $userId = $data->metadata->user_id ?? null;
        if ($userId === null) {
            Log::warning('event payment_intent.succeeded Missing user_id in metadata', ['payment_id' => $paymentId]);
        }
        if ($paymentType === 'wallet_recharge') {
            $transaction->update([
                'transaction_reference' => $paymentId,
                'amount' => $data->amount_received / 100,
                'currency_code' => $data->currency,
                'description' => 'Wallet Recharge Payment Captured'
            ]);
            $result = Wallet::captureRecharge($transaction->id);
            if (!$result['success']) {
                Log::error("Wallet Recharge Failed: " . $result['message']);
                return;
            }
            Log::info("Wallet Recharge Completed: payment succeeded");
            return;
        }

        if ($paymentType === 'subscription_payment' && $transaction instanceof SubscriptionTransaction) {
            // Mark subscription transaction completed and activate subscription
            $transaction->update([
                'status' => SubscriptionTransactionStatusEnum::COMPLETED(),
                'transaction_id' => $paymentId,
                'amount' => ($data->amount_received ?? 0) / 100,
            ]);
            $subscription = SellerSubscription::find($transaction->seller_subscription_id);
            if ($subscription) {
                $subscription->status = SellerSubscriptionStatusEnum::ACTIVE();
                $subscription->price_paid = $transaction->amount;
                $subscription->save();
            }
            Log::info('Subscription payment succeeded', ['payment_id' => $paymentId, 'transaction_id' => $transaction->id]);
            return;
        }
        $paymentData = [
            'amount' => $data->amount_received / 100,
            'currency' => $data->currency,
            'data' => $data->toArray(),
            'order_id' => $transaction->order_id ?? null,
            'user_id' => $userId
        ];
        OrderPaymentTransaction::saveTransaction(data: $paymentData, paymentId: $paymentId, paymentMethod: PaymentTypeEnum::STRIPE(), paymentStatus: PaymentStatusEnum::COMPLETED());

        Log::info('event payment_intent.succeeded Payment succeeded', ['payment_id' => $paymentId]);

        if ($transaction !== null && $transaction->order_id !== null) {
            Order::capturePayment($transaction->order_id);
            OrderItem::capturePayment($transaction->order_id);
            Log::info("event payment_intent.succeeded Order Updated After Payment Succeeded: {$transaction->order_id}");
        }
    }

    private function handlePaymentFailed(string $paymentType, $transaction, $event): void
    {
        if ($transaction === null) {
            return;
        }

        if ($paymentType === 'wallet_recharge') {
            $transaction->update([
                'status' => PaymentStatusEnum::FAILED(),
                'message' => $event,
            ]);
            Log::info('Wallet Recharge Failed', ['payment_id' => $transaction->id]);
        } elseif ($paymentType === 'subscription_payment' && $transaction instanceof SubscriptionTransaction) {
            $transaction->update([
                'status' => SubscriptionTransactionStatusEnum::FAILED(),
            ]);
            $subscription = SellerSubscription::find($transaction->seller_subscription_id);
            if ($subscription) {
                $subscription->status = SellerSubscriptionStatusEnum::CANCELLED();
                $subscription->save();
            }
            Log::info('Subscription Payment Failed', ['subscription_id' => $transaction->seller_subscription_id]);
        } else {
            $transaction->update([
                'payment_status' => PaymentStatusEnum::FAILED(),
                'message' => 'Payment Failed'
            ]);

            if ($transaction->order_id !== null) {
                Order::paymentFailed($transaction->order_id);
                OrderItem::paymentFailed($transaction->order_id);
            }

            Log::warning('event payment_intent.payment_failed Payment failed', ['payment_id' => $transactionId ?? ""]);
        }
    }

    private function handleRefund(string $paymentType, $transaction, object $data): void
    {
        Log::info(" data = " . $data);
        if ($transaction === null) {
            Log::warning('event refund.updated Transaction not found for refund update', ['payment_id' => $data->id ?? null]);
            return;
        }
        if ($paymentType === 'wallet_recharge') {
            $refundAmount = $data->amount_refunded / 100;
            $result = Wallet::captureRefund(transactionId: $transaction->id, refundAmount:$refundAmount);
            if (!$result['success']) {
                Log::error("Wallet Refund Failed: " . $result['message']);
                return;
            }
            Log::info('event refund.processed Wallet Refund Processed', ['payment_id' => $transaction->transaction_reference ?? null]);
            return;
        }
        $transaction->update([
            'payment_status' => PaymentStatusEnum::REFUNDED(),
            'message' => "Payment Refunded",
            'payment_details' => $data
        ]);
        Log::info('event charge.refunded Payment Refunded', ['payment_id' => $transaction->id]);
    }

    private function findTransaction(string $paymentType, object $data)
    {
        if ($paymentType === 'order_payment') {
            $transactionId = $data->id ?? null;
            return OrderPaymentTransaction::where('transaction_id', $transactionId)->first();
        }

        if ($paymentType === 'wallet_recharge') {
            $transactionId = $data->metadata->transaction_id ?? '';
            $transaction = WalletTransaction::find($transactionId);

            if (!$transaction) {
                Log::warning("Wallet Transaction not found for ID: {$transactionId}");
                throw new Exception('Wallet Transaction not found');
            }

            return $transaction;
        }

        if ($paymentType === 'subscription_payment') {
            $subscriptionTxnId = $data->metadata->subscription_transaction_id ?? '';
            if (!$subscriptionTxnId) {
                return null;
            }
            return SubscriptionTransaction::find($subscriptionTxnId);
        }

        return null;
    }


    public function refundPayment($paymentIntentId, $amount = null): array
    {
        try {
            Stripe::setApiKey($this->secretKey);

            $params = [
                'payment_intent' => $paymentIntentId,
            ];

            // If partial refund
            if ($amount) {
                $params['amount'] = $amount * 100; // Convert to cents
            }

            $refund = Refund::create($params);

            return [
                'success' => true,
                'message' => 'Refund processed successfully',
                'data' => $refund,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
