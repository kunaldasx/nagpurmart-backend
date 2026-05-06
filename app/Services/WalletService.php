<?php

namespace App\Services;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\SettingTypeEnum;
use App\Enums\Wallet\WalletTransactionStatusEnum;
use App\Enums\Wallet\WalletTransactionTypeEnum;
use App\Http\Controllers\Payments\FlutterwaveController;
use App\Http\Controllers\Payments\PaystackController;
use App\Http\Controllers\Payments\RazorpayController;
use App\Http\Controllers\Payments\StripeController;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Types\Api\ApiResponseType;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WalletService
{

    protected RazorpayController $razorpayController;
    protected StripeController $stripeController;
    protected PaystackController $paystackController;
    protected FlutterwaveController $flutterwaveController;

    protected SettingService $settingService;

    public function __construct()
    {
        try {
            // Prevent DB calls if DB is not configured
            if (Schema::hasTable('settings')) {
                if (Schema::hasTable('settings')) {
                    $this->razorpayController = app(RazorpayController::class);
                    $this->stripeController = app(StripeController::class);
                    $this->paystackController = app(PaystackController::class);
                    $this->flutterwaveController = app(FlutterwaveController::class);
                    $this->settingService = app(SettingService::class);
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore during installation
            // Do NOT log here – installer should not fail
        }
    }


    /**
     * Get or create a user's wallet
     *
     * @param int $userId The user ID
     * @return array Result containing success status, message, and wallet data
     */
    public function getWallet(int $userId): array
    {
        try {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId],
                [
                    'balance' => 0.00,
                    'blocked_balance' => 0.00,
                    'currency_code' => 'USD' // You can make this configurable
                ]
            );
            $wallet->balance = number_format($wallet->balance, 2, '.', '');

            return [
                'success' => true,
                'message' => __('labels.wallet_retrieved_successfully'),
                'data' => $wallet
            ];
        } catch (Exception $e) {
            Log::error('Error retrieving wallet: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Add balance to the user wallet
     *
     * @param int $userId The user ID
     * @param array $data Balance data including amount, payment method, etc.
     * @return array Result containing success status, message, and wallet data
     */
    public function addBalance(int $userId, array $data): array
    {
        try {
            DB::beginTransaction();

            // Get or create a wallet
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId],
                [
                    'balance' => 0.00,
                    'currency_code' => 'USD'
                ]
            );

            // Add balance
            $amount = $data['amount'];
            $wallet->balance += $amount;
            $wallet->save();

            // Create a transaction record
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'transaction_type' => WalletTransactionTypeEnum::DEPOSIT(),
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'currency_code' => $wallet->currency_code,
                'status' => WalletTransactionStatusEnum::COMPLETED(),
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'description' => $data['description'] ?? 'Wallet balance added',
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => __('labels.wallet_balance_added_successfully'),
                'data' => [
                    'wallet' => $wallet,
                    'transaction' => $transaction
                ]
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error adding wallet balance: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Prepare wallet recharge
     *
     * @param int $userId The user ID
     * @param array $data Balance data including amount, payment method, etc.
     * @return array Result containing success status, message, and wallet data
     */
    public function prepareWalletRecharge(int $userId, array $data): array
    {
        try {
            DB::beginTransaction();

            // Get or create a wallet
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId],
                [
                    'balance' => 0.00,
                    'currency_code' => 'USD'
                ]
            );
            switch ($data['payment_method']) {
                case PaymentTypeEnum::PAYSTACK():
                    $paymentSettings = $this->settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
                    if (empty($paymentSettings->value['paystackCurrencyCode'])) {
                        return ApiResponseType::toArray(
                            success: false,
                            message: 'Paystack currency code not configured',
                        );
                    }
                    $currencyCode = $paymentSettings->value['paystackCurrencyCode'];
                    break;

                case PaymentTypeEnum::FLUTTERWAVE():
                    $paymentSettings = $this->settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
                    if (empty($paymentSettings->value['flutterwaveCurrencyCode'])) {
                        return ApiResponseType::toArray(
                            success: false,
                            message: 'Flutterwave currency code not configured',
                        );
                    }
                    $currencyCode = $paymentSettings->value['flutterwaveCurrencyCode'];
                    break;
                default:
                    $currencyCode = $wallet->currency_code;
                    break;
            }

            // Create a transaction record
            $transaction = $this->makeWalletPaymentTransaction(wallet: $wallet, userId: $userId, data: $data, status: WalletTransactionStatusEnum::PENDING(), transactionType: WalletTransactionTypeEnum::DEPOSIT(), currencyCode: $currencyCode);
            $data['transaction_id'] = (string)$transaction->id;

            $paymentResponse = $this->paymentIntent($data['payment_method'], $data);
            if (!$paymentResponse['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $paymentResponse['message'],
                    'data' => $paymentResponse['data']
                ];
            }


            DB::commit();

            return [
                'success' => true,
                'message' => __('labels.wallet_recharge_prepared_successfully'),
                'data' => [
                    'wallet' => $wallet,
                    'transaction' => $transaction,
                    'payment_response' => $paymentResponse['data']
                ]
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error adding wallet balance: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    private function paymentIntent(string $paymentMethod, array $data): array
    {
        switch ($paymentMethod) {
            case PaymentTypeEnum::RAZORPAY():
                return $this->razorpayController->createWalletRechargeOrder(data: $data);
            case PaymentTypeEnum::STRIPE():
                return $this->stripeController->createWalletRechargeOrderPaymentIntent(data: $data);
            case PaymentTypeEnum::PAYSTACK():
                return $this->paystackController->createWalletRechargeOrderPaymentIntent(data: $data);
            case PaymentTypeEnum::FLUTTERWAVE():
                return $this->flutterwaveController->createWalletRechargeOrder(data: $data);
            default:
                throw new Exception('Unsupported payment method: ' . $paymentMethod);
        }

    }

    /**
     * Create a wallet payment transaction
     *
     * @param Wallet $wallet The wallet instance
     * @param int $userId The user ID
     * @param array $data Transaction data including amount, payment method, etc.
     * @return WalletTransaction The created wallet transaction
     */
    public
    function makeWalletPaymentTransaction(Wallet $wallet, int $userId, array $data, string $status, string $transactionType, string $currencyCode): WalletTransaction
    {
        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $userId,
            'transaction_type' => $transactionType ?? WalletTransactionTypeEnum::DEPOSIT(),
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'currency_code' => $currencyCode,
            'status' => $status ?? WalletTransactionStatusEnum::PENDING(),
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'description' => $data['description'] ?? 'Wallet recharge initiated',
        ]);
    }

    /**
     * Deduct balance from the user wallet
     *
     * @param int $userId The user ID
     * @param array $data Deduction data including amount, order ID, etc.
     * @return array Result containing success status, message, and wallet data
     */
    public
    static function deductBalance(int $userId, array $data): array
    {
        try {
            $wallet = Wallet::where('user_id', $userId)->first();

            if (!$wallet) {
                return [
                    'success' => false,
                    'message' => __('labels.wallet_not_found'),
                    'data' => []
                ];
            }

            $amount = $data['amount'];

            if ($wallet->balance < $amount) {
                return [
                    'success' => false,
                    'message' => __('labels.insufficient_wallet_balance'),
                    'data' => []
                ];
            }

            DB::beginTransaction();

            // Deduct balance
            $wallet->balance -= $amount;
            $wallet->save();

            // Create a transaction record
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'order_id' => $data['order_id'] ?? null,
                'store_id' => $data['store_id'] ?? null,
                'transaction_type' => WalletTransactionTypeEnum::PAYMENT(),
                'payment_method' => 'wallet',
                'amount' => $amount,
                'currency_code' => $wallet->currency_code,
                'status' => WalletTransactionStatusEnum::COMPLETED(),
                'description' => $data['description'] ?? 'Payment made from wallet',
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => __('labels.wallet_balance_deducted_successfully'),
                'data' => [
                    'wallet' => $wallet,
                    'transaction' => $transaction
                ]
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deducting wallet balance: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Get wallet transactions with filtering and pagination
     *
     * @param int $userId The user ID
     * @param array $filters Filter parameters
     * @return array Result containing success status, message, and transactions data
     */
    public
    function getTransactions(int $userId, array $filters = []): array
    {
        try {
            $query = WalletTransaction::where('user_id', $userId);
            $perPage = $filters['per_page'] ?? 15; // Default to 15 items per page if not provided

            // Search functionality
            if (isset($filters['query'])) {
                $searchQuery = $filters['query'];
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('description', 'LIKE', '%' . $searchQuery . '%')
                        ->orWhere('transaction_reference', 'LIKE', '%' . $searchQuery . '%')
                        ->orWhere('payment_method', 'LIKE', '%' . $searchQuery . '%')
                        ->orWhere('amount', 'LIKE', '%' . $searchQuery . '%');
                });
            }

            // Filter by transaction type
            if (isset($filters['transaction_type'])) {
                $query->where('transaction_type', $filters['transaction_type']);
            }

            // Filter by status
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Filter by payment method
            if (isset($filters['payment_method'])) {
                $query->where('payment_method', 'LIKE', '%' . $filters['payment_method'] . '%');
            }

            // Filter by amount range
            if (isset($filters['min_amount'])) {
                $query->where('amount', '>=', $filters['min_amount']);
            }

            if (isset($filters['max_amount'])) {
                $query->where('amount', '<=', $filters['max_amount']);
            }

            // Sorting functionality
            if (isset($filters['sort']) && isset($filters['order'])) {
                $sortField = $filters['sort'];
                $sortOrder = $filters['order'];

                // Validate sort field to prevent SQL injection
                $allowedSortFields = [
                    'id', 'amount', 'transaction_type', 'status', 'payment_method',
                    'created_at', 'updated_at'
                ];

                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortOrder);
                }
            } else {
                // Default sorting by created_at desc
                $query->orderBy('created_at', 'desc');
            }

            $transactions = $query->paginate($perPage);

            return [
                'success' => true,
                'message' => __('labels.wallet_transactions_retrieved_successfully'),
                'data' => $transactions
            ];
        } catch (Exception $e) {
            Log::error('Error retrieving wallet transactions: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Get a single wallet transaction by ID
     *
     * @param int $userId The user ID
     * @param int $transactionId The transaction ID
     * @return array Result containing success status, message, and transaction data
     */
    public
    function getTransaction(int $userId, int $transactionId): array
    {
        try {
            $transaction = WalletTransaction::where('user_id', $userId)
                ->where('id', $transactionId)
                ->first();

            if (!$transaction) {
                return [
                    'success' => false,
                    'message' => __('labels.wallet_transaction_not_found'),
                    'data' => []
                ];
            }

            return [
                'success' => true,
                'message' => __('labels.wallet_transaction_retrieved_successfully'),
                'data' => $transaction
            ];
        } catch (Exception $e) {
            Log::error('Error retrieving wallet transaction: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Finalize a pending wallet deposit manually by admin (complete or fail)
     *
     * @param int $transactionId
     * @param bool $approve true to complete, false to fail
     * @param string|null $adminNote optional description/note to store
     * @return array
     */
    public function finalizeDeposit(int $transactionId, bool $approve, ?string $adminNote = null): array
    {
        try {
            DB::beginTransaction();

            $transaction = WalletTransaction::query()
                ->where('id', $transactionId)
                ->where('transaction_type', WalletTransactionTypeEnum::DEPOSIT())
                ->first();

            if (!$transaction) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => __('labels.wallet_transaction_not_found'),
                    'data' => []
                ];
            }

            if ($transaction->status !== WalletTransactionStatusEnum::PENDING()) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => __('labels.transaction_already_processed'),
                    'data' => []
                ];
            }

            // Append admin note to description if provided
            if (!empty($adminNote)) {
                $transaction->description = trim(($transaction->description ?? '') . "\nAdmin: " . $adminNote);
            }

            if ($approve) {
                // credit wallet balance
                $wallet = Wallet::where('id', $transaction->wallet_id)->first();
                if (!$wallet) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => __('labels.wallet_not_found'),
                        'data' => []
                    ];
                }

                $wallet->balance = (float)$wallet->balance + (float)$transaction->amount;
                $wallet->save();
                $transaction->status = WalletTransactionStatusEnum::COMPLETED();
            } else {
                $transaction->status = WalletTransactionStatusEnum::FAILED();
            }

            $transaction->save();

            DB::commit();

            return [
                'success' => true,
                'message' => $approve ? __('labels.wallet_recharge_success') : __('labels.wallet_recharge_failed'),
                'data' => [
                    'transaction' => $transaction
                ]
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error finalizing wallet deposit: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => __('labels.something_went_wrong'),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }
}
