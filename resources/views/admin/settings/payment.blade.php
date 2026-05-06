@php use App\Enums\Payment\FlutterwaveCurrencyCodeEnum;use App\Enums\Payment\PaymentTypeEnum;use App\Enums\Payment\PaystackCurrencyCodeEnum; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['payment']['sub_active'] ?? "" ])

@section('title', __('labels.payment_settings'))

@section('header_data')
    @php
        $page_title = __('labels.payment_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.payment_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.payment_settings') }}</h2>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
        </div>
    </div>
    <!-- BEGIN PAGE BODY -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-5">
                <div class="col-sm-2 d-none d-lg-block">
                    <div class="sticky-top">
                        <h3>{{ __('labels.menu') }}</h3>
                        <nav class="nav nav-vertical nav-pills" id="pills">
                            <a class="nav-link" href="#pills-stripe">{{ __('labels.stripe_payment') }}</a>
                            <a class="nav-link" href="#pills-razorpay">{{ __('labels.razorpay_payment') }}</a>
                            <a class="nav-link" href="#pills-paystack">{{ __('labels.paystack_payment') }}</a>
                            <a class="nav-link" href="#pills-flutterwave">{{ __('labels.flutterwave_payment') }}</a>
                            <a class="nav-link" href="#pills-wallet">{{ __('labels.wallet_payment') }}</a>
                            <a class="nav-link" href="#pills-cod">{{ __('labels.cash_on_delivery') }}</a>
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="payment">
                                <div class="card mb-4" id="pills-stripe">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.stripe_payment') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_stripe_payment') }}</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="stripePayment" value="1" {{ isset($settings['stripePayment']) && $settings['stripePayment'] ? 'checked' : '' }} />
                                                    </label>
                                                </span>
                                            </label>
                                        </div>
                                        <div id="stripeFields"
                                             style="{{ isset($settings['stripePayment']) && $settings['stripePayment'] ? 'display: block;' : 'display: none;' }}">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.stripe_payment_mode') }}</label>
                                                <select class="form-select" name="stripePaymentMode">
                                                    <option value=""
                                                            disabled {{ !isset($settings['stripePaymentMode']) ? 'selected' : '' }}>{{ __('labels.stripe_payment_mode_placeholder') }}</option>
                                                    <option
                                                        value="test" {{ isset($settings['stripePaymentMode']) && $settings['stripePaymentMode'] === 'test' ? 'selected' : '' }}>
                                                        Test
                                                    </option>
                                                    <option
                                                        value="live" {{ isset($settings['stripePaymentMode']) && $settings['stripePaymentMode'] === 'live' ? 'selected' : '' }}>
                                                        Live
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.stripe_publishable_key') }}</label>
                                                <input type="text" class="form-control" name="stripePublishableKey"
                                                       placeholder="{{ __('labels.stripe_publishable_key_placeholder') }}"
                                                       value="{{ $settings['stripePublishableKey'] ?? '' }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.stripe_secret_key') }}</label>
                                                <input type="text" class="form-control" name="stripeSecretKey"
                                                       placeholder="{{ __('labels.stripe_secret_key_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['stripeSecretKey'] ?? '****'), '****', 3, 8) : ($settings['stripeSecretKey'] ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.stripe_currency_code') }}</label>
                                                <select class="form-select" name="stripeCurrencyCode">
                                                    <option value=""
                                                            disabled {{ !isset($settings['stripeCurrencyCode']) ? 'selected' : '' }}>{{ __('labels.stripe_currency_code_placeholder') }}</option>
                                                    <option
                                                        value="USD" {{ isset($settings['stripeCurrencyCode']) && $settings['stripeCurrencyCode'] === 'USD' ? 'selected' : '' }}>
                                                        USD
                                                    </option>
                                                    <option
                                                        value="EUR" {{ isset($settings['stripeCurrencyCode']) && $settings['stripeCurrencyCode'] === 'EUR' ? 'selected' : '' }}>
                                                        EUR
                                                    </option>
                                                    <option
                                                        value="GBP" {{ isset($settings['stripeCurrencyCode']) && $settings['stripeCurrencyCode'] === 'GBP' ? 'selected' : '' }}>
                                                        GBP
                                                    </option>
                                                    <!-- Add more currencies as per StripeCurrencyCodeEnum -->
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.stripe_webhook_secret_key') }}</label>
                                                <input type="text" class="form-control"
                                                       name="stripeWebhookSecretKey"
                                                       placeholder="{{ __('labels.stripe_webhook_secret_key_placeholder') }}"
                                                       value="{{ $settings['stripeWebhookSecretKey'] ?? '' }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label">{{ __('labels.stripe_payment_endpoint_url') }}</label>
                                                <input type="url" class="form-control"
                                                       name="stripePaymentEndpointUrl"
                                                       placeholder="{{ __('labels.stripe_payment_endpoint_url_placeholder') }}"
                                                       value="{{ route('stripe.webhook') }}"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-razorpay">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.razorpay_payment') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_razorpay_payment') }}</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="razorpayPayment" value="1" {{ isset($settings['razorpayPayment']) && $settings['razorpayPayment'] ? 'checked' : '' }} />
                                                    </label>
                                                </span>
                                            </label>
                                        </div>
                                        <div id="razorpayFields"
                                             style="{{ isset($settings['razorpayPayment']) && $settings['razorpayPayment'] ? 'display: block;' : 'display: none;' }}">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.razorpay_payment_mode') }}</label>
                                                <select class="form-select" name="razorpayPaymentMode">
                                                    <option value=""
                                                            disabled {{ !isset($settings['razorpayPaymentMode']) ? 'selected' : '' }}>{{ __('labels.razorpay_payment_mode_placeholder') }}</option>
                                                    <option
                                                        value="test" {{ isset($settings['razorpayPaymentMode']) && $settings['razorpayPaymentMode'] === 'test' ? 'selected' : '' }}>
                                                        Test
                                                    </option>
                                                    <option
                                                        value="live" {{ isset($settings['razorpayPaymentMode']) && $settings['razorpayPaymentMode'] === 'live' ? 'selected' : '' }}>
                                                        Live
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.razorpay_key_id') }}</label>
                                                <input type="text" class="form-control" name="razorpayKeyId"
                                                       placeholder="{{ __('labels.razorpay_key_id_placeholder') }}"
                                                       value="{{ $settings['razorpayKeyId'] ?? '' }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.razorpay_secret_key') }}</label>
                                                <input type="text" class="form-control" name="razorpaySecretKey"
                                                       placeholder="{{ __('labels.razorpay_secret_key_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['razorpaySecretKey'] ?? '****'), '****', 3, 8) : ($settings['razorpaySecretKey'] ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.razorpay_webhook_secret') }}</label>
                                                <input type="text" class="form-control" name="razorpayWebhookSecret"
                                                       placeholder="{{ __('labels.razorpay_webhook_secret_placeholder') }}"
                                                       value="{{ $settings['razorpayWebhookSecret'] ?? '' }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label">{{ __('labels.razorpay_webhook_url') }}</label>
                                                <p type="text"
                                                   class="form-control mb-1 user-select-all">{{ route('razorpay.webhook') }}</p>
                                                <small
                                                    class="form-text text-muted">{{ __('labels.razorpay_webhook_url_description') }}
                                                     {{__('labels.for_more_details')}} <a
                                                        href="https://razorpay.com/docs/webhooks/setup-edit-payments/#set-up-webhooks" target="_blank">{{__('labels.setup_razorpay_webhook')}}</a></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-paystack">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.paystack_payment') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_paystack_payment') }}</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="paystackPayment" value="1" {{ isset($settings['paystackPayment']) && $settings['paystackPayment'] ? 'checked' : '' }} />
                                                    </label>
                                                </span>
                                            </label>
                                        </div>
                                        <div id="paystackFields"
                                             style="{{ isset($settings['paystackPayment']) && $settings['paystackPayment'] ? 'display: block;' : 'display: none;' }}">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.paystack_payment_mode') }}</label>
                                                <select class="form-select" name="paystackPaymentMode">
                                                    <option value=""
                                                            disabled {{ !isset($settings['paystackPaymentMode']) ? 'selected' : '' }}>{{ __('labels.paystack_payment_mode_placeholder') }}</option>
                                                    <option
                                                        value="test" {{ isset($settings['paystackPaymentMode']) && $settings['paystackPaymentMode'] === 'test' ? 'selected' : '' }}>
                                                        Test
                                                    </option>
                                                    <option
                                                        value="live" {{ isset($settings['paystackPaymentMode']) && $settings['paystackPaymentMode'] === 'live' ? 'selected' : '' }}>
                                                        Live
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.paystack_public_key') }}</label>
                                                <input type="text" class="form-control" name="paystackPublicKey"
                                                       placeholder="{{ __('labels.paystack_public_key_placeholder') }}"
                                                       value="{{ $settings['paystackPublicKey'] ?? '' }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.paystack_secret_key') }}</label>
                                                <input type="text" class="form-control" name="paystackSecretKey"
                                                       placeholder="{{ __('labels.paystack_secret_key_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['paystackSecretKey'] ?? '****'), '****', 3, 8) : ($settings['paystackSecretKey'] ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.paystack_webhook_secret') }}</label>
                                                <input type="text" class="form-control" name="paystackWebhookSecret"
                                                       placeholder="{{ __('labels.paystack_webhook_secret_placeholder') }}"
                                                       value="{{ $settings['paystackWebhookSecret'] ?? '' }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.paystack_currency_code') }}</label>
                                                <select class="form-select" name="paystackCurrencyCode">
                                                    @foreach(PaystackCurrencyCodeEnum::values() as $value)
                                                        <option
                                                            value="{{ $value }}" {{ isset($settings['paystackCurrencyCode']) && $settings['paystackCurrencyCode'] === $value ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.paystack_webhook_url') }}</label>
                                                <p type="text"
                                                   class="form-control mb-1 user-select-all">{{ route('paystack.webhook') }}</p>
                                                <small
                                                    class="form-text text-muted">{{ __('labels.paystack_webhook_url_description') . " " . __('labels.for_more_details')}}<a
                                                        href="https://support.paystack.com/en/articles/2123458#webhook_url" target="_blank"> {{__('labels.setup_paystack_webhook')}}</a></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-flutterwave">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.flutterwave_payment') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_flutterwave_payment') }}</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="flutterwavePayment" value="1"
                                                               {{ isset($settings['flutterwavePayment']) && $settings['flutterwavePayment'] ? 'checked' : '' }} />
                                                    </label>
                                                </span>
                                            </label>
                                        </div>

                                        <div id="flutterwaveFields"
                                             style="{{ isset($settings['flutterwavePayment']) && $settings['flutterwavePayment'] ? 'display: block;' : 'display: none;' }}">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.flutterwave_payment_mode') }}</label>
                                                <select class="form-select" name="flutterwavePaymentMode">
                                                    <option value=""
                                                            disabled {{ !isset($settings['flutterwavePaymentMode']) ? 'selected' : '' }}>
                                                        {{ __('labels.flutterwave_payment_mode_placeholder') }}
                                                    </option>
                                                    <option value="test"
                                                        {{ isset($settings['flutterwavePaymentMode']) && $settings['flutterwavePaymentMode'] === 'test' ? 'selected' : '' }}>
                                                        Test
                                                    </option>
                                                    <option value="live"
                                                        {{ isset($settings['flutterwavePaymentMode']) && $settings['flutterwavePaymentMode'] === 'live' ? 'selected' : '' }}>
                                                        Live
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.flutterwave_public_key') }}</label>
                                                <input type="text" class="form-control" name="flutterwavePublicKey"
                                                       placeholder="{{ __('labels.flutterwave_public_key_placeholder') }}"
                                                       value="{{ $settings['flutterwavePublicKey'] ?? '' }}"/>
                                            </div>

                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.flutterwave_secret_key') }}</label>
                                                <input type="text" class="form-control" name="flutterwaveSecretKey"
                                                       placeholder="{{ __('labels.flutterwave_secret_key_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['flutterwaveSecretKey'] ?? '****'), '****', 3, 8) : ($settings['flutterwaveSecretKey'] ?? '') }}"/>
                                            </div>

                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.flutterwave_encryption_key') }}</label>
                                                <input type="text" class="form-control" name="flutterwaveEncryptionKey"
                                                       placeholder="{{ __('labels.flutterwave_encryption_key_placeholder') }}"
                                                       value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['flutterwaveEncryptionKey'] ?? '****'), '****', 3, 8) : ($settings['flutterwaveEncryptionKey'] ?? '') }}"/>
                                            </div>

                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.flutterwave_webhook_secret') }}</label>
                                                <input type="text" class="form-control" name="flutterwaveWebhookSecret"
                                                       placeholder="{{ __('labels.flutterwave_webhook_secret_placeholder') }}"
                                                       value="{{ $settings['flutterwaveWebhookSecret'] ?? '' }}"/>
                                            </div>

                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.flutterwave_currency_code') }}</label>
                                                <select class="form-select" name="flutterwaveCurrencyCode">
                                                    @foreach(FlutterwaveCurrencyCodeEnum::values() as $value)
                                                        <option value="{{ $value }}"
                                                            {{ isset($settings['flutterwaveCurrencyCode']) && $settings['flutterwaveCurrencyCode'] === $value ? 'selected' : '' }}>
                                                            {{ __('labels.currency_data.' . $value) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.flutterwave_webhook_url') }}</label>
                                                <p type="text"
                                                   class="form-control mb-1 user-select-all">{{ route('flutterwave.webhook') }}</p>
                                                <small
                                                    class="form-text text-muted">{{ __('labels.flutterwave_webhook_url_description') . " " . __('labels.for_more_details')}}<a
                                                        href="https://developer.flutterwave.com/docs/webhooks#enabling-webhooks" target="_blank"> {{__('labels.setup_flutterwave_webhook')}}</a></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-wallet">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.wallet_payment') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_wallet_payment') }}</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="walletPayment"
                                                               value="1" {{ isset($settings['walletPayment']) && $settings['walletPayment'] ? 'checked' : '' }} />
                                                    </label>
                                                </span>
                                                <small
                                                    class="form-text text-muted">{{ __('labels.wallet_payment_description') }}</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-cod">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.cash_on_delivery') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_cash_on_delivery') }}</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="{{PaymentTypeEnum::COD()}}"
                                                               value="1" {{ isset($settings['cod']) && $settings['cod'] ? 'checked' : '' }} />
                                                    </label>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'payment'])
                                            <button type="submit"
                                                    class="btn btn-primary ms-auto">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- END PAGE BODY -->
    <script>
        const stripeToggle = document.querySelector('input[name="stripePayment"]');
        const stripeFields = document.getElementById('stripeFields');
        const razorpayToggle = document.querySelector('input[name="razorpayPayment"]');
        const razorpayFields = document.getElementById('razorpayFields');
        const paystackToggle = document.querySelector('input[name="paystackPayment"]');
        const paystackFields = document.getElementById('paystackFields');
        const bankToggle = document.querySelector('input[name="directBankTransfer"]');
        const bankFields = document.getElementById('bankFields');
        const flutterwaveToggle = document.querySelector('input[name="flutterwavePayment"]');
        const flutterwaveFields = document.getElementById('flutterwaveFields');

        const toggleStripeFields = () => {
            stripeFields.style.display = stripeToggle.checked ? 'block' : 'none';
        };
        const toggleRazorpayFields = () => {
            razorpayFields.style.display = razorpayToggle.checked ? 'block' : 'none';
        };
        const togglePaystackFields = () => {
            paystackFields.style.display = paystackToggle.checked ? 'block' : 'none';
        };
        const toggleFlutterwaveFields = () => {
            flutterwaveFields.style.display = flutterwaveToggle.checked ? 'block' : 'none';
        };
        const toggleBankFields = () => {
            bankFields.style.display = bankToggle.checked ? 'block' : 'none';
        };

        stripeToggle.addEventListener('change', toggleStripeFields);
        razorpayToggle.addEventListener('change', toggleRazorpayFields);
        paystackToggle.addEventListener('change', togglePaystackFields);
        flutterwaveToggle.addEventListener('change', toggleFlutterwaveFields);
        bankToggle.addEventListener('change', toggleBankFields);
        toggleStripeFields();
        toggleRazorpayFields();
        togglePaystackFields();
        toggleBankFields();
    </script>
@endsection
