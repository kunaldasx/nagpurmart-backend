<?php

namespace App\Types\Settings;

use App\Enums\Payment\FlutterwaveCurrencyCodeEnum;
use App\Enums\Payment\PaymentModeEnum;
use App\Enums\Payment\PaystackCurrencyCodeEnum;
use App\Enums\Payment\StripeCurrencyCodeEnum;
use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;
use Illuminate\Validation\Rules\Enum;

class PaymentSettingType implements SettingInterface
{
    use SettingTrait;

    public bool $stripePayment = false;
    public string $stripePaymentMode = "";
    public string $stripePublishableKey = "";
    public string $stripeSecretKey = "";
    public string $stripePaymentEndpointUrl = "";
    public string $stripeWebhookSecretKey = "";
    public string $stripeCurrencyCode = "";
    public bool $razorpayPayment = false;
    public string $razorpayPaymentMode = "";
    public string $razorpayKeyId = "";
    public string $razorpaySecretKey = "";
    public string $razorpayWebhookSecret = "";
    public bool $paystackPayment = false;
    public string $paystackPaymentMode = "";
    public string $paystackPublicKey = "";
    public string $paystackSecretKey = "";
    public string $paystackWebhookSecret = "";
    public string $paystackCurrencyCode = "";
    public bool $walletPayment = false;
    public bool $cod = false;
    public bool $directBankTransfer = false;
    public string $bankAccountName = "";
    public string $bankAccountNumber = "";
    public string $bankName = "";
    public string $bankCode = "";
    public string $bankExtraNote = "";
    public bool $flutterwavePayment = false;
    public string $flutterwavePaymentMode = "";
    public string $flutterwavePublicKey = "";
    public string $flutterwaveSecretKey = "";
    public string $flutterwaveEncryptionKey = "";
    public string $flutterwaveWebhookSecret = "";
    public string $flutterwaveCurrencyCode = "";


    protected static function getValidationRules(): array
    {
        return [
            'stripePayment' => 'nullable|boolean',
            'stripePaymentMode' => ['required_if:stripePayment,true', new Enum(PaymentModeEnum::class)],
            'stripePublishableKey' => 'required_if:stripePayment,true',
            'stripeSecretKey' => 'required_if:stripePayment,true',
            'stripePaymentEndpointUrl' => 'required_if:stripePayment,true|nullable|url',
            'stripeWebhookSecretKey' => 'required_if:stripePayment,true',
            'stripeCurrencyCode' => ['required_if:stripePayment,true', new Enum(StripeCurrencyCodeEnum::class)],
            'razorpayPayment' => 'nullable|boolean',
            'razorpayPaymentMode' => ['required_if:razorpayPayment,true', new Enum(PaymentModeEnum::class)],
            'razorpayKeyId' => 'required_if:razorpayPayment,true',
            'razorpaySecretKey' => 'required_if:razorpayPayment,true',
            'razorpayWebhookSecret' => 'required_if:razorpayPayment,true',
            'paystackPayment' => 'nullable|boolean',
            'paystackPaymentMode' => ['required_if:paystackPayment,true', new Enum(PaymentModeEnum::class)],
            'paystackPublicKey' => 'required_if:paystackPayment,true',
            'paystackSecretKey' => 'required_if:paystackPayment,true',
            'paystackWebhookSecret' => 'required_if:paystackPayment,true',
            'paystackCurrencyCode' => ['required_if:paystackPayment,true', new Enum(PaystackCurrencyCodeEnum::class)],
            'walletPayment' => 'nullable|boolean',
            'cod' => 'nullable|boolean',
            'directBankTransfer' => 'nullable|boolean',
            'bankAccountName' => 'required_if:directBankTransfer,true',
            'bankAccountNumber' => 'required_if:directBankTransfer,true',
            'bankName' => 'required_if:directBankTransfer,true',
            'bankCode' => 'required_if:directBankTransfer,true',
            'bankExtraNote' => 'nullable',
            'flutterwavePayment' => 'nullable|boolean',
            'flutterwavePaymentMode' => ['required_if:flutterwavePayment,true', new Enum(PaymentModeEnum::class)],
            'flutterwavePublicKey' => 'required_if:flutterwavePayment,true',
            'flutterwaveSecretKey' => 'required_if:flutterwavePayment,true',
            'flutterwaveEncryptionKey' => 'required_if:flutterwavePayment,true',
            'flutterwaveWebhookSecret' => 'required_if:flutterwavePayment,true',
            'flutterwaveCurrencyCode' => ['required_if:flutterwavePayment,true', new Enum(FlutterwaveCurrencyCodeEnum::class)],
        ];
    }

    protected static function getValidationMessages(): array
    {
        return [
            'stripePaymentMode.required_if' => __('labels.stripe_payment_mode') . ' is required when ' . __('labels.enable_stripe_payment') . ' is enabled.',
            'stripePaymentMode.enum' => 'The selected ' . __('labels.stripe_payment_mode') . ' is invalid.',
            'stripePublishableKey.required_if' => __('labels.stripe_publishable_key') . ' is required when ' . __('labels.enable_stripe_payment') . ' is enabled.',
            'stripeSecretKey.required_if' => __('labels.stripe_secret_key') . ' is required when ' . __('labels.enable_stripe_payment') . ' is enabled.',
            'stripePaymentEndpointUrl.required_if' => __('labels.stripe_payment_endpoint_url') . ' is required when ' . __('labels.enable_stripe_payment') . ' is enabled.',
            'stripePaymentEndpointUrl.url' => 'Please provide a valid URL for the ' . __('labels.stripe_payment_endpoint_url') . '.',
            'stripeWebhookSecretKey.required_if' => __('labels.stripe_webhook_secret_key') . ' is required when ' . __('labels.enable_stripe_payment') . ' is enabled.',
            'stripeCurrencyCode.required_if' => __('labels.stripe_currency_code') . ' is required when ' . __('labels.enable_stripe_payment') . ' is enabled.',
            'stripeCurrencyCode.enum' => 'The selected ' . __('labels.stripe_currency_code') . ' is not supported.',
            'razorpayPaymentMode.required_if' => __('labels.razorpay_payment_mode') . ' is required when ' . __('labels.enable_razorpay_payment') . ' is enabled.',
            'razorpayPaymentMode.enum' => 'The selected ' . __('labels.razorpay_payment_mode') . ' is invalid.',
            'razorpayKeyId.required_if' => __('labels.razorpay_key_id') . ' is required when ' . __('labels.enable_razorpay_payment') . ' is enabled.',
            'razorpaySecretKey.required_if' => __('labels.razorpay_secret_key') . ' is required when ' . __('labels.enable_razorpay_payment') . ' is enabled.',
            'razorpayWebhookSecret.required_if' => __('labels.razorpay_webhook_secret') . ' is required when ' . __('labels.enable_razorpay_payment') . ' is enabled.',
            'paystackPaymentMode.required_if' => __('labels.paystack_payment_mode') . ' is required when ' . __('labels.enable_paystack_payment') . ' is enabled.',
            'paystackPaymentMode.enum' => 'The selected ' . __('labels.paystack_payment_mode') . ' is invalid.',
            'paystackPublicKey.required_if' => __('labels.paystack_public_key') . ' is required when ' . __('labels.enable_paystack_payment') . ' is enabled.',
            'paystackSecretKey.required_if' => __('labels.paystack_secret_key') . ' is required when ' . __('labels.enable_paystack_payment') . ' is enabled.',
            'paystackWebhookSecret.required_if' => __('labels.paystack_webhook_secret') . ' is required when ' . __('labels.enable_paystack_payment') . ' is enabled.',
            'bankAccountName.required_if' => __('labels.bank_account_name') . ' is required when ' . __('labels.enable_direct_bank_transfer') . ' is enabled.',
            'bankAccountNumber.required_if' => __('labels.bank_account_number') . ' is required when ' . __('labels.enable_direct_bank_transfer') . ' is enabled.',
            'bankName.required_if' => __('labels.bank_name') . ' is required when ' . __('labels.enable_direct_bank_transfer') . ' is enabled.',
            'bankCode.required_if' => __('labels.bank_code') . ' is required when ' . __('labels.enable_direct_bank_transfer') . ' is enabled.',
            'flutterwavePaymentMode.required_if' => __('labels.flutterwave_payment_mode') . ' is required when ' . __('labels.enable_flutterwave_payment') . ' is enabled.',
            'flutterwavePaymentMode.enum' => 'The selected ' . __('labels.flutterwave_payment_mode') . ' is invalid.',
            'flutterwavePublicKey.required_if' => __('labels.flutterwave_public_key') . ' is required when ' . __('labels.enable_flutterwave_payment') . ' is enabled.',
            'flutterwaveSecretKey.required_if' => __('labels.flutterwave_secret_key') . ' is required when ' . __('labels.enable_flutterwave_payment') . ' is enabled.',
            'flutterwaveEncryptionKey.required_if' => __('labels.flutterwave_encryption_key') . ' is required when ' . __('labels.enable_flutterwave_payment') . ' is enabled.',
            'flutterwaveWebhookSecret.required_if' => __('labels.flutterwave_webhook_secret') . ' is required when ' . __('labels.enable_flutterwave_payment') . ' is enabled.',
            'flutterwaveCurrencyCode.required_if' => __('labels.flutterwave_currency_code') . ' is required when ' . __('labels.enable_flutterwave_payment') . ' is enabled.',
        ];
    }
}
