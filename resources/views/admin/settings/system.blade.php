@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['system']['sub_active'] ?? "" ])

@section('title', __('labels.system_settings'))

@section('header_data')
    @php
        $page_title = __('labels.system_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.system_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.system_settings') }}</h2>
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
                            <a class="nav-link" href="#pills-general">{{ __('labels.general') }}</a>
                            <a class="nav-link"
                               href="#pills-support">{{ __('labels.support_information') }}</a>
                            <a class="nav-link"
                               href="#pills-cart">{{ __('labels.cart_inventory_settings') }}</a>
                            <a class="nav-link" href="#pills-wallet">{{ __('labels.wallet_settings') }}</a>
                            <a class="nav-link"
                               href="#pills-maintenance">{{ __('labels.maintenance_mode') }}</a>
                            <a class="nav-link"
                               href="#pills-demomode">{{ __('labels.demo_mode') }}</a>
                            {{--                            <a class="nav-link"--}}
                            {{--                               href="#pills-referral">{{ __('labels.referral_earn_program') }}</a>--}}
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{route('admin.settings.store')}}" class="form-submit" method="post"
                                  enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="type" value="system">
                                <div class="card mb-4" id="pills-general">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.general') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.app_name') }}</label>
                                            <input type="text" class="form-control" name="appName"
                                                   placeholder="{{ __('labels.app_name_placeholder') }}"
                                                   value="{{$settings['appName'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.system_timezone') }}</label>
                                            <input type="text" class="form-control" name="systemTimezone"
                                                   placeholder="{{ __('labels.system_timezone_placeholder') }}"
                                                   value="{{$settings['systemTimezone'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.copyright_details') }}</label>
                                            <input type="text" class="form-control" name="copyrightDetails"
                                                   placeholder="{{ __('labels.copyright_details_placeholder') }}"
                                                   value="{{$settings['copyrightDetails'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.currency') }}</label>
                                            <input type="hidden" name="currencySymbol" id="currency-symbol"
                                                   value="{{$settings['currencySymbol'] ?? ''}}">
                                            <input type="hidden" id="selected-currency"
                                                   value="{{$settings['currency'] ?? ''}}">
                                            <select class="form-select" id="select-currency" name="currency"
                                                    placeholder="USD, EUR, INR, etc."></select>
                                        </div>
                                        @if(file_exists(storage_path('dev')))
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">Update System Vendor Type(Only in
                                                    dev server)</label>
                                                <select class="form-select text-capitalize" name="systemVendorType">
                                                    @foreach(\App\Enums\SystemVendorTypeEnum::values() as $key => $value)
                                                        <option value="{{$value}}" {{ isset($settings['systemVendorType']) && $settings['systemVendorType'] === $value ? 'selected' : '' }}>{{$value}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div
                                                        class="form-label required">{{ __('labels.logo') }}</div>
                                                    <input type="file" class="form-control" name="logo"
                                                           data-image-url="{{ $settings['logo'] }}"/>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div
                                                        class="form-label required">{{ __('labels.favicon') }}</div>
                                                    <input type="file" name="favicon"
                                                           data-image-url="{{ $settings['favicon'] }}"/>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Company Address</label>
                                            <textarea class="form-control" name="companyAddress" rows="3"
                                                      placeholder="Enter company address shown on invoice">{{ $settings['companyAddress'] ?? '' }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Admin Signature (Authorized Signatory)</label>
                                            <input type="file" name="adminSignature"
                                                   data-image-url="{{ $settings['adminSignature'] ?? '' }}"/>
                                            <small class="form-hint">Upload a signature image to display on
                                                invoices.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-support">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.support_information') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.seller_support_email') }}</label>
                                            <div>
                                                <input type="email" class="form-control" name="sellerSupportEmail"
                                                       aria-describedby="emailHelp"
                                                       placeholder="{{ __('labels.seller_support_email_placeholder') }}"
                                                       value="{{$settings['sellerSupportEmail'] ?? ''}}"/>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.seller_support_number') }}</label>
                                            <div>
                                                <input type="tel" class="form-control" name="sellerSupportNumber"
                                                       aria-describedby="numberHelp"
                                                       placeholder="{{ __('labels.seller_support_number_placeholder') }}"
                                                       value="{{$settings['sellerSupportNumber'] ?? ''}}"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-cart">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.cart_inventory_settings') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.select_checkout_type') }}</label>
                                            <div>
                                                <label class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="checkoutType"
                                                           value="single_store" {{!empty($settings['checkoutType']) && $settings['checkoutType'] === 'single_store' ? 'checked' : ''}}>
                                                    <span
                                                        class="form-check-label">{{ __('labels.single_store') }}</span>
                                                </label>
                                                <label class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="checkoutType"
                                                           value="multi_store" {{!empty($settings['checkoutType']) && $settings['checkoutType'] === 'multi_store' ? 'checked' : ''}}>
                                                    <span class="form-check-label">{{ __('labels.multi_store') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.minimum_cart_amount') }}</label>
                                            <input type="number" step="1" min="0" class="form-control"
                                                   name="minimumCartAmount"
                                                   placeholder="{{ __('labels.minimum_cart_amount_placeholder') }}"
                                                   value="{{$settings['minimumCartAmount'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.maximum_items_allowed_in_cart') }}</label>
                                            <input type="number" min="1" class="form-control"
                                                   name="maximumItemsAllowedInCart"
                                                   placeholder="{{ __('labels.maximum_items_allowed_in_cart placeholder') }}"
                                                   value="{{$settings['maximumItemsAllowedInCart'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.low_stock_limit') }}</label>
                                            <input type="number" min="0" class="form-control" name="lowStockLimit"
                                                   placeholder="{{ __('labels.low_stock_limit_placeholder') }}"
                                                   value="{{$settings['lowStockLimit'] ?? ''}}"/>
                                        </div>
                                        {{--                                        <div class="mb-3">--}}
                                        {{--                                            <label--}}
                                        {{--                                                class="form-label required">{{ __('labels.maximum_distance_to_nearest_store') }}</label>--}}
                                        {{--                                            <input type="number" step="0.01" min="0" class="form-control"--}}
                                        {{--                                                   name="maximumDistanceToNearestStore"--}}
                                        {{--                                                   placeholder="{{ __('labels.maximum_distance_to_nearest_store_placeholder') }}"--}}
                                        {{--                                                   value="{{$settings['maximumDistanceToNearestStore'] ?? ''}}"/>--}}
                                        {{--                                        </div>--}}
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-wallet">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.wallet_settings') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        {{--                                        <div class="mb-3">--}}
                                        {{--                                            <label class="row">--}}
                                        {{--                                                <span class="col">{{ __('labels.enable_wallet') }}</span>--}}
                                        {{--                                                <span class="col-auto">--}}
                                        {{--                                                        <label class="form-check form-check-single form-switch">--}}
                                        {{--                                                            <input class="form-check-input" type="checkbox"--}}
                                        {{--                                                                   name="enableWallet" value="1" {{ isset($settings['enableWallet']) && $settings['enableWallet'] ? 'checked' : '' }}/>--}}
                                        {{--                                                        </label>--}}
                                        {{--                                                    </span>--}}
                                        {{--                                            </label>--}}
                                        {{--                                        </div>--}}
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.welcome_wallet_balance_amount') }}</label>
                                            <input type="number" step="0.01" min="0" class="form-control"
                                                   name="welcomeWalletBalanceAmount"
                                                   placeholder="{{ __('labels.welcome_wallet_balance_amount_placeholder') }}"
                                                   value="{{$settings['welcomeWalletBalanceAmount'] ?? '0'}}"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-maintenance">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.maintenance_mode') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                    <span
                                                        class="col">{{ __('labels.seller_app_maintenance_mode') }}</span>
                                                <span class="col-auto">
                                                        <label class="form-check form-check-single form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="sellerAppMaintenanceMode" value="1" {{ isset($settings['sellerAppMaintenanceMode']) && $settings['sellerAppMaintenanceMode'] ? 'checked' : '' }}/>
                                                        </label>
                                                    </span>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.seller_app_maintenance_message') }}</label>
                                            <input type="text" class="form-control"
                                                   name="sellerAppMaintenanceMessage"
                                                   placeholder="{{ __('labels.seller_app_maintenance_message_placeholder') }}"
                                                   value="{{$settings['sellerAppMaintenanceMessage'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="row">
                                                    <span
                                                        class="col">{{ __('labels.web_maintenance_mode') }}</span>
                                                <span class="col-auto">
                                                        <label class="form-check form-check-single form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="webMaintenanceMode" value="1" {{ isset($settings['webMaintenanceMode']) && $settings['webMaintenanceMode'] ? 'checked' : '' }}/>
                                                        </label>
                                                    </span>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.web_maintenance_message') }}</label>
                                            <input type="text" class="form-control" name="webMaintenanceMessage"
                                                   placeholder="{{ __('labels.web_maintenance_message_placeholder') }}"
                                                   value="{{$settings['webMaintenanceMessage'] ?? ''}}"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-demomode">

                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.demo_mode') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="row">
                                                <span class="col">{{ __('labels.enable_demo_mode') }}</span>
                                                <span class="col-auto">
                                                    <label class="form-check form-check-single form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               name="demoMode" value="1" {{ isset($settings['demoMode']) && $settings['demoMode'] ? 'checked' : '' }}/>
                                                    </label>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.admin_demo_mode_message') }}</label>
                                            <input type="text" class="form-control" name="adminDemoModeMessage"
                                                   placeholder="{{ __('labels.admin_demo_mode_message_placeholder') }}"
                                                   value="{{$settings['adminDemoModeMessage'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.seller_demo_mode_message') }}</label>
                                            <input type="text" class="form-control" name="sellerDemoModeMessage"
                                                   placeholder="{{ __('labels.seller_demo_mode_message_placeholder') }}"
                                                   value="{{$settings['sellerDemoModeMessage'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.customer_demo_mode_message') }}</label>
                                            <input type="text" class="form-control" name="customerDemoModeMessage"
                                                   placeholder="{{ __('labels.customer_demo_mode_message_placeholder') }}"
                                                   value="{{$settings['customerDemoModeMessage'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.customer_location_demo_mode_message') }}</label>
                                            <input type="text" class="form-control"
                                                   name="customerLocationDemoModeMessage"
                                                   placeholder="{{ __('labels.customer_location_demo_mode_message_placeholder') }}"
                                                   value="{{$settings['customerLocationDemoModeMessage'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label">{{ __('labels.delivery_boy_demo_mode_message') }}</label>
                                            <input type="text" class="form-control" name="deliveryBoyDemoModeMessage"
                                                   placeholder="{{ __('labels.delivery_boy_demo_mode_message_placeholder') }}"
                                                   value="{{$settings['deliveryBoyDemoModeMessage'] ?? ''}}"/>
                                        </div>
                                    </div>
                                </div>

                                {{--                                <div class="card mb-4" id="pills-referral">--}}
                                {{--                                    <div class="card-header">--}}
                                {{--                                        <h4 class="card-title">{{ __('labels.referral_earn_program') }}</h4>--}}
                                {{--                                    </div>--}}
                                {{--                                    <div class="card-body">--}}
                                {{--                                        <div class="mb-3">--}}
                                {{--                                            <label class="row">--}}
                                {{--                                                    <span--}}
                                {{--                                                        class="col">{{ __('labels.enable_referral_program') }}</span>--}}
                                {{--                                                <span class="col-auto">--}}
                                {{--                                                        <label class="form-check form-check-single form-switch">--}}
                                {{--                                                            <input class="form-check-input" type="checkbox"--}}
                                {{--                                                                   name="referEarnStatus" role="switch"--}}
                                {{--                                                                   id="referEarnToggle" {{ isset($settings['referEarnStatus']) && $settings['referEarnStatus'] ? 'checked' : '' }}/>--}}
                                {{--                                                        </label>--}}
                                {{--                                                    </span>--}}
                                {{--                                            </label>--}}
                                {{--                                        </div>--}}
                                {{--                                        <div id="referEarnFields">--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.user_bonus_method') }}</label>--}}
                                {{--                                                <select class="form-select" name="referEarnMethodUser">--}}
                                {{--                                                    <option--}}
                                {{--                                                        value="fixed" {{ isset($settings['referEarnMethodUser']) && $settings['referEarnMethodUser'] === 'fixed' ? 'selected' : '' }}>--}}
                                {{--                                                        {{ __('labels.fixed') }}--}}
                                {{--                                                    </option>--}}
                                {{--                                                    <option--}}
                                {{--                                                        value="percentage" {{ isset($settings['referEarnMethodUser']) && $settings['referEarnMethodUser'] === 'percentage' ? 'selected' : '' }}>--}}
                                {{--                                                        {{ __('labels.percentage') }}--}}
                                {{--                                                    </option>--}}
                                {{--                                                </select>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.user_bonus') }}</label>--}}
                                {{--                                                <input type="number" class="form-control" name="referEarnBonusUser"--}}
                                {{--                                                       placeholder="{{ __('labels.user_bonus_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnBonusUser'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.max_bonus_amount_user') }}</label>--}}
                                {{--                                                <input type="number" min="0" step="0.01" class="form-control"--}}
                                {{--                                                       name="referEarnMaximumBonusAmountUser"--}}
                                {{--                                                       placeholder="{{ __('labels.max_bonus_amount_user_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnMaximumBonusAmountUser'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.referral_bonus_method') }}</label>--}}
                                {{--                                                <select class="form-select" name="referEarnMethodReferral">--}}
                                {{--                                                    <option--}}
                                {{--                                                        value="fixed" {{ isset($settings['referEarnMethodReferral']) && $settings['referEarnMethodReferral'] === 'fixed' ? 'selected' : '' }}>--}}
                                {{--                                                        {{ __('labels.fixed') }}--}}
                                {{--                                                    </option>--}}
                                {{--                                                    <option--}}
                                {{--                                                        value="percentage" {{ isset($settings['referEarnMethodReferral']) && $settings['referEarnMethodReferral'] === 'percentage' ? 'selected' : '' }}>--}}
                                {{--                                                        {{ __('labels.percentage') }}--}}
                                {{--                                                    </option>--}}
                                {{--                                                </select>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.referral_bonus') }}</label>--}}
                                {{--                                                <input type="number" class="form-control"--}}
                                {{--                                                       name="referEarnBonusReferral"--}}
                                {{--                                                       placeholder="{{ __('labels.referral_bonus_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnBonusReferral'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.max_bonus_amount_referral') }}</label>--}}
                                {{--                                                <input type="number" min="0" step="0.01" class="form-control"--}}
                                {{--                                                       name="referEarnMaximumBonusAmountReferral"--}}
                                {{--                                                       placeholder="{{ __('labels.max_bonus_amount_referral_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnMaximumBonusAmountReferral'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.minimum_order_amount_for_bonus') }}</label>--}}
                                {{--                                                <input type="number" min="0" step="0.01" class="form-control"--}}
                                {{--                                                       name="referEarnMinimumOrderAmount"--}}
                                {{--                                                       placeholder="{{ __('labels.minimum_order_amount_for_bonus_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnMinimumOrderAmount'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                            <div class="mb-3">--}}
                                {{--                                                <label--}}
                                {{--                                                    class="form-label">{{ __('labels.number_of_times_bonus_applicable') }}</label>--}}
                                {{--                                                <input type="number" min="0" class="form-control"--}}
                                {{--                                                       name="referEarnNumberOfTimesBonus"--}}
                                {{--                                                       placeholder="{{ __('labels.number_of_times_bonus_applicable_placeholder') }}"--}}
                                {{--                                                       value="{{$settings['referEarnNumberOfTimesBonus'] ?? ''}}"/>--}}
                                {{--                                            </div>--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}
                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'system'])
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
        // const toggle = document.getElementById('referEarnToggle');
        // const fields = document.getElementById('referEarnFields');
        // const toggleFields = () => {
        //     fields.style.display = toggle.checked ? 'block' : 'none';
        // };
        // toggle.addEventListener('change', toggleFields);
        // toggleFields(); // initial state
    </script>
@endsection
