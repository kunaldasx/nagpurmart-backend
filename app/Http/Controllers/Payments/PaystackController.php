<?php

namespace App\Http\Controllers\Payments;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SettingTypeEnum;
use App\Enums\Wallet\WalletTransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTransaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\SettingService;
use App\Services\WalletService;
use App\Types\Api\ApiResponseType;
use App\Models\SubscriptionTransaction;
use App\Models\SellerSubscription;
use App\Enums\Subscription\SubscriptionTransactionStatusEnum;
use App\Enums\Subscription\SellerSubscriptionStatusEnum;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaystackController extends Controller
{
    private $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Render a web view for paying a subscription transaction via Paystack Hosted Page
     */
    public function showSubscriptionPaymentPage(string $transaction): Factory|View
    {
        $txn = SubscriptionTransaction::with('plan')
            ->where('uuid', $transaction)
            ->firstOrFail();

        if ((string)$txn->payment_gateway !== (string)PaymentTypeEnum::PAYSTACK()) {
            abort(404);
        }

        if ((string)$txn->status !== 'pending') {
            return view('payments.subscription-status', ['transaction' => $txn]);
        }

        // Ensure Paystack initialization exists and get authorization URL
        $init = $this->createSubscriptionInitialize([
            'amount' => (float)$txn->amount,
            'subscription_transaction_id' => $txn->id,
            'seller_subscription_id' => $txn->seller_subscription_id,
            'seller_id' => $txn->seller_id,
            'user_id' => auth()->id(),
        ]);
        if (!($init['success'] ?? false)) {
            abort(400, $init['message'] ?? 'Unable to initialize Paystack payment');
        }
        $authorizationUrl = $init['data']['authorization_url'] ?? null;
        $reference = $init['data']['reference'] ?? null;
        if ($reference) {
            $txn->transaction_id = $reference; // store Paystack reference
            $txn->save();
        }

        $paymentSettings = $this->settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
        $currency = $paymentSettings->value['paystackCurrencyCode'] ?? '';

        return view('payments.subscription-payment', [
            'gateway' => 'paystack',
            'authorizationUrl' => $authorizationUrl,
            'amount' => (float)$txn->amount,
            'currency' => $currency,
            'transactionId' => $txn->id,
            'transactionUuid' => $txn->uuid,
            'sellerId' => $txn->seller_id,
            'subscriptionId' => $txn->seller_subscription_id,
            'plan' => $txn->plan,
            'planName' => optional($txn->plan)->name ?? 'Subscription Plan',
            'payButtonLabel' => __('labels.pay_with_paystack'),
        ]);
    }

    /**
     * Initialize Paystack for subscription purchases
     */
    public function createSubscriptionInitialize(array $data): array
    {
        try {
            $validated = validator($data, [
                'amount' => 'required|numeric|min:1',
                'subscription_transaction_id' => 'required|integer',
                'seller_subscription_id' => 'required|integer',
                'seller_id' => 'required|integer',
                'user_id' => 'nullable|integer',
            ])->validate();

            $paymentSettings = $this->settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
            $secretKey = $paymentSettings->value['paystackSecretKey'] ?? null;
            $currency = $paymentSettings->value['paystackCurrencyCode'] ?? null;

            if (empty($currency)) {
                return ApiResponseType::toArray(success: false, message: 'Paystack currency code not configured');
            }
            if (!$secretKey) {
                return ApiResponseType::toArray(success: false, message: 'Paystack not configured');
            }

            $reference = 'sub_' . ($validated['seller_id']) . '_' . time();
            // Resolve UUID for callback URL
            $st = SubscriptionTransaction::find($validated['subscription_transaction_id']);
            $transactionUuid = $st?->uuid ?? (string)$validated['subscription_transaction_id'];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', [
                'email' => auth()->user()->email ?? ('customer_' . Str::random(5) . '@example.com'),
                'amount' => (int)round($validated['amount'] * 100),
                'currency' => $currency,
                'reference' => $reference,
                'callback_url' => route('subscription-payment.status', ['transaction' => $transactionUuid]),
                'metadata' => [
                    'type' => 'subscription_payment',
                    'subscription_transaction_id' => (string)$validated['subscription_transaction_id'],
                    'seller_subscription_id' => (string)$validated['seller_subscription_id'],
                    'seller_id' => (string)$validated['seller_id'],
                    'user_id' => (string)($validated['user_id'] ?? ''),
                    'transaction_id' => (string)$validated['subscription_transaction_id'],
                ],
            ]);

            $json = $response->json();
            if (!($json['status'] ?? false)) {
                return ApiResponseType::toArray(
                    success: false,
                    message: 'Payment initialization failed: ' . ($json['message'] ?? 'Unknown error'),
                    data: $json['data'] ?? null,
                );
            }

            return ApiResponseType::toArray(
                success: true,
                message: 'Payment initialized successfully',
                data: [
                    'authorization_url' => $json['data']['authorization_url'] ?? null,
                    'reference' => $reference,
                ],
            );
        } catch (ValidationException $ve) {
            return ApiResponseType::toArray(success: false, message: 'Validation Error ' . $ve->getMessage(), data: $ve->errors());
        } catch (Exception $e) {
            Log::error('Paystack subscription init failed: ' . $e->getMessage());
            return ApiResponseType::toArray(success: false, message: 'Paystack payment intent creation failed: ' . $e->getMessage());
        }
    }

    public function createOrderPaymentIntent(Request $request): JsonResponse
    {
        try {
            $input = $request->validate([
                'amount' => 'required|numeric|min:1',
            ]);

            $paymentSettings = $this->settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
            $secretKey = $paymentSettings->value['paystackSecretKey'] ?? null;

            if (empty($paymentSettings->value['paystackCurrencyCode'])) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'Paystack currency code not configured',
                );
            }
            if (!$secretKey) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'Paystack not configured',
                );
            }

            $ref = 'order_' . auth()->id() . '_' . time();
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', [
                'email' => auth()->user()->email ?? 'customer_' . Str::random(5) . '@example.com',
                'amount' => (int)$input['amount'] * 100, // Convert to paisa
                'currency' => $paymentSettings->value['paystackCurrencyCode'],

                'reference' => $ref,
                'callback_url' => route('paystack.callback'),
                'metadata' => [
                    'user_id' => auth()->id(),
                    'transaction_id' => $ref,
                ]
            ]);

            $data = $response->json();

            if (!$data['status']) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'Payment initialization failed: ' . ($data['message'] ?? 'Unknown error'),
                    data: $data['data'] ?? null,
                );
            }
            $transactionData = [
                'user_id' => auth()->id(),
                'amount' => $input['amount'],
                'currency' => $paymentSettings->value['paystackCurrencyCode'],
            ];
            $transaction = OrderPaymentTransaction::saveTransaction(data: $transactionData, paymentId: $ref, paymentMethod: PaymentTypeEnum::PAYSTACK(), paymentStatus: PaymentStatusEnum::PENDING());

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'Payment initialized successfully',
                data: [
                    'transaction' => $transaction,
                    'payment_response' => $data['data'],
                ]
            );
        } catch (ValidationException $ve) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Validation Error ' . $ve->getMessage(),
                data: $ve->errors()
            );
        } catch (Exception $e) {
            Log::error('Paystack payment intent creation failed: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Paystack payment intent creation failed: ' . $e->getMessage(),
            );
        }
    }

    public function createWalletRechargeOrderPaymentIntent(array $data): array
    {
        try {
            $validated = validator($data, [
                'amount' => 'required|numeric|min:1',
                'transaction_id' => 'required|string',
            ])->validate();

            $paymentSettings = $this->settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
            $secretKey = $paymentSettings->value['paystackSecretKey'] ?? null;

            if (empty($paymentSettings->value['paystackCurrencyCode'])) {
                return ApiResponseType::toArray(
                    success: false,
                    message: 'Paystack currency code not configured',
                );
            }
            if (!$secretKey) {
                return ApiResponseType::toArray(
                    success: false,
                    message: 'Paystack not configured',
                );
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', [
                'email' => auth()->user()->email ?? 'customer_' . Str::random(5) . '@example.com',
                'amount' => (int)$validated['amount'] * 100, // Convert to paisa
                'currency' => $paymentSettings->value['paystackCurrencyCode'],

                'reference' => 'wallet_' . auth()->id() . '_' . time(),
                'callback_url' => route('paystack.callback'),
                'metadata' => [
                    'user_id' => auth()->id(),
                    'transaction_id' => $data['transaction_id'],
                    'type' => 'wallet_recharge'
                ]
            ]);

            $data = $response->json();

            if (!$data['status']) {
                return ApiResponseType::toArray(
                    success: false,
                    message: 'Payment initialization failed: ' . ($data['message'] ?? 'Unknown error'),
                    data: $data['data'] ?? null,
                );
            }
            return ApiResponseType::toArray(
                success: true,
                message: 'Payment initialized successfully',
                data: $data['data'],
            );
        } catch (ValidationException $ve) {
            return ApiResponseType::toArray(
                success: false,
                message: 'Validation Error ' . $ve->getMessage(),
                data: $ve->errors()
            );
        } catch (Exception $e) {
            Log::error('Paystack payment intent creation failed: ' . $e->getMessage());
            return ApiResponseType::toArray(
                success: false,
                message: 'Paystack payment intent creation failed: ' . $e->getMessage(),
            );
        }
    }

    public function verifyPayment(string $reference): array
    {
        try {
            $paymentSettings = $this->settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
            $secretKey = $paymentSettings->value['paystackSecretKey'] ?? null;

            if (!$secretKey) {
                return ['success' => false, 'message' => 'Paystack not configured'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $data = $response->json();

            if ($data['status']) {
                return [
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'data' => $data['data']
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment verification failed'
            ];
        } catch (Exception $e) {
            Log::error('Paystack payment verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment verification failed'
            ];
        }
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            $paymentSettings = $this->settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
            $secretKey = $paymentSettings->value['paystackSecretKey'] ?? null;

            if (!$secretKey) {
                Log::error('Paystack webhook secret not configured');
                return response()->json(['error' => 'Webhook secret not configured'], 400);
            }

            // Verify webhook signature
            $signature = $request->header('x-paystack-signature');
            $payload = $request->getContent();

            if (!hash_equals($signature, hash_hmac('sha512', $payload, $secretKey))) {
                Log::error('Paystack Invalid signature');
                return response()->json(['error' => 'Paystack Invalid signature'], 400);
            }

            $event = json_decode($payload);
            Log::info("Paystack webhook payload: " . json_encode($event));
            $transactionId = $event->data->metadata->transaction_id ?? "";
            $paymentType = $event->data->metadata->type ?? "order_payment";
            //            if ($paymentType === 'order_payment') {
            //                sleep(15);
            //            }
            $reference = $event->data->reference ?? "";
            $transaction = $this->findTransaction(paymentType: $paymentType, transactionId: $transactionId);

            if ($event->event === 'charge.success') {
                $this->handlePaymentSucceeded(transaction: $transaction, reference: $reference, paymentType: $paymentType);
            } elseif ($event->event === 'charge.dispute.create') {
                $this->handlePaymentFailed(transaction: $transaction, reference: $reference, paymentType: $paymentType);
            } elseif ($event->event === 'refund.processed') {
                $this->handleRefund(data: $event->data);
            }
            Log::info("Paystack webhook handling completed");
            return response()->json(['status' => 'success']);
        } catch (Exception $e) {
            Log::error('Paystack webhook handling failed: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook handling failed'], 500);
        }
    }

    private function handlePaymentSucceeded($transaction, $reference, $paymentType): void
    {
        try {
            if (empty($transaction)) {
                Log::warning("Transaction not found for reference: {$reference}");
                return;
            }
            DB::beginTransaction();
            if ($paymentType === 'subscription_payment' && $transaction instanceof SubscriptionTransaction) {
                // Mark subscription transaction completed and activate subscription
                $transaction->update([
                    'status' => SubscriptionTransactionStatusEnum::COMPLETED(),
                    'transaction_id' => $reference,
                ]);
                $subscription = SellerSubscription::find($transaction->seller_subscription_id);
                if ($subscription) {
                    $subscription->status = SellerSubscriptionStatusEnum::ACTIVE();
                    $subscription->price_paid = $transaction->amount;
                    $subscription->save();
                }
                Log::info('Subscription payment captured', ['payment_id' => $reference, 'transaction_id' => $transaction->id]);
                DB::commit();
                return;
            }

            if ($transaction && ($transaction->status === PaymentStatusEnum::PENDING() || $transaction->payment_status === PaymentStatusEnum::PENDING())) {
                if ($paymentType === 'wallet_recharge') {
                    $transaction->update(['transaction_reference' => $reference]);
                    $result = Wallet::captureRecharge($transaction->id);
                    if (!$result['success']) {
                        Log::error("Wallet Recharge Failed: " . $result['message']);
                        return;
                    }
                    Log::info("Wallet Recharge Completed: {$transaction->id}");
                    DB::commit();
                    return;
                }

                $transaction->update([
                    'transaction_id' => $reference,
                    'payment_status' => PaymentStatusEnum::COMPLETED(),
                    'message' => 'Payment successful via Paystack',
                ]);
                if ($transaction->order_id === null) {
                    Log::warning("Order ID is null for transaction: {$transaction->id}");
                    DB::commit();
                    return;
                }
                Order::capturePayment($transaction->order_id);
                OrderItem::capturePayment($transaction->order_id);
                Log::info("Order Updated And Ready to Go: {$transaction->order_id}");
                DB::commit();
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Paystack payment success handling failed: ' . $e->getMessage());
        }
    }

    private function handlePaymentFailed($transaction, $reference, $paymentType): void
    {
        try {
            if (empty($transaction)) {
                Log::warning("Transaction not found for reference: {$reference}");
                return;
            }
            if ($paymentType === 'wallet_recharge') {
                $transaction->update([
                    'transaction_reference' => $reference,
                    'status' => PaymentStatusEnum::FAILED(),
                    'message' => 'Wallet recharge failed via Paystack',
                ]);
                Log::info('Wallet Recharge Failed', ['payment_id' => $transaction->id]);
            } elseif ($paymentType === 'order_payment') {
                $transaction->update([
                    'payment_status' => PaymentStatusEnum::FAILED(),
                    'message' => 'Order payment failed via Paystack',
                ]);

                Order::paymentFailed($transaction->order_id);
                OrderItem::paymentFailed($transaction->order_id);
                Log::info('Order Payment Failed', ['order_id' => $transaction->order_id]);
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
            }
        } catch (Exception $e) {
            Log::error('Paystack payment failure handling failed: ' . $e->getMessage());
        }
    }

    public
    function handleCallback(Request $request): JsonResponse
    {
        try {
            $reference = $request->query('reference');

            if (!$reference) {
                return response()->json(['error' => 'Reference not provided'], 400);
            }

            $verification = $this->verifyPayment($reference);

            if ($verification['success']) {
                $transactionId = $verification['data']['metadata']['transaction_id'] ?? "";
                $paymentType = $verification['data']['metadata']['type'] ?? "order_payment";
                $transaction = $this->findTransaction(paymentType: $paymentType, transactionId: $transactionId);
                $this->handlePaymentSucceeded(transaction: $transaction, reference: $reference, paymentType: $paymentType);
                return response()->json(['status' => 'success', 'message' => 'Payment successful']);
            }

            return response()->json(['error' => 'Payment verification failed'], 400);
        } catch (Exception $e) {
            Log::error('Paystack callback handling failed: ' . $e->getMessage());
            return response()->json(['error' => 'Callback handling failed'], 500);
        }
    }

    private
    function handleRefund($data): void
    {
        $transactionId = $data->transaction_reference ?? "";
        $amount = $data->amount / 100;
        $transaction = WalletTransaction::where('transaction_reference', $transactionId)->first() ?? OrderPaymentTransaction::where('transaction_reference', $transactionId)->first();
        if (!$transaction) {
            Log::error('Transaction not found for refund: ' . $transactionId);
            return;
        }
        if (!empty($transaction->transaction_type) && $transaction->transaction_type === WalletTransactionTypeEnum::DEPOSIT()) {
            $result = Wallet::captureRefund(transactionId: $transaction->id, refundAmount: $amount);
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
        Log::info('event refund.processed Payment Refunded', ['payment_id' => $transaction->transaction_id ?? null]);
    }

    private function findTransaction(string $paymentType, string $transactionId)
    {
        if ($paymentType === 'order_payment') {
            return OrderPaymentTransaction::where('transaction_id', $transactionId)->first();
        }

        if ($paymentType === 'wallet_recharge') {
            $transaction = WalletTransaction::find($transactionId);

            if (!$transaction) {
                Log::warning("Wallet Transaction not found for ID: $transactionId");
                throw new Exception('Wallet Transaction not found');
            }

            return $transaction;
        }

        if ($paymentType === 'subscription_payment') {
            if (!$transactionId) {
                return null;
            }
            return SubscriptionTransaction::find($transactionId);
        }

        return null;
    }

    /**
     * Refund a payment via Paystack
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function refundPayment($paymentId, $amount = null, $reason = "Order Failed to Execute"): array
    {
        try {

            $paymentSettings = $this->settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
            $secretKey = $paymentSettings->value['paystackSecretKey'] ?? null;
            $refundData = [
                'transaction' => $paymentId,
                'reason' => $reason
            ];
            if (!empty($amount)) {
                $refundData['amount'] = $amount * 100;
            }
            if (!$secretKey) {
                return ApiResponseType::toArray(
                    success: false,
                    message: 'Paystack not configured',
                );
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/refund', $refundData);

            $data = $response->json();

            if (!$data['status']) {
                return ApiResponseType::toArray(
                    success: false,
                    message: 'Refund failed: ' . ($data['message'] ?? 'Unknown error'),
                    data: $data['data'] ?? null,
                );
            }

            return ApiResponseType::toArray(
                success: true,
                message: 'Refund initiated successfully',
                data: $data['data']
            );

        } catch (ValidationException $ve) {
            return ApiResponseType::toArray(
                success: false,
                message: 'Validation Error ' . $ve->getMessage(),
                data: $ve->errors()
            );
        } catch (Exception $e) {
            Log::error('Paystack refund failed: ' . $e->getMessage());
            return ApiResponseType::toArray(
                success: false,
                message: 'Paystack refund failed: ' . $e->getMessage(),
            );
        }
    }


}
