@extends('layouts.no-head-foot')

@section('title', __('labels.subscription_payment_summary'))
@section('content')
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <!-- BEGIN NAVBAR LOGO -->
            <a href="." class="navbar-brand navbar-brand-autodark">
                @if(($systemSettings['demoMode'] ?? false))
                    <img
                        src="{{asset('logos/hyper-local-logo.png')}}"
                        alt="{{$systemSettings['appName'] ?? ""}}" width="150px">
                @else
                    <img
                        src="{{!empty($systemSettings['logo'])?$systemSettings['logo'] : asset('logos/hyper-local-logo.png')}}"
                        alt="{{$systemSettings['appName'] ?? ""}}" width="150px">
                @endif
            </a>
            <!-- END NAVBAR LOGO -->
        </div>

        <div class="card card-md">
            <div class="card-body">
                <h2>{{ __('labels.subscription_payment_summary') }}</h2>

                @isset($plan)
                    <div class="d-flex align-items-center justify-content-between mb-2 fw-bold"><span>{{ __('labels.plan') }}:</span>
                        <span>{{ $plan->name ?? ($planName ?? '') }}</span>
                    </div>
                @endisset

                @isset($plan)
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span>{{ __('labels.duration') }}:</span>
                        <span>{{ ($plan->duration_type ?? null) === 'unlimited' ? __('labels.unlimited') : ((($plan->duration_days ?? 0)).' '.__('labels.days')) }}</span>
                    </div>
                @endisset

                @isset($transactionId)
                    <div class="d-flex align-items-center justify-content-between"><span>{{ __('labels.system_transaction_id') }}:</span>
                        <span>#{{ $transactionId }}</span>
                    </div>
                @endisset
                <hr class="my-3"/>
                <div class="d-flex align-items-center justify-content-between h3">
                    <span>{{ __('labels.amount') }}:</span>
                    <span>{{ number_format((float)($amount ?? 0), 2) }} {{ $systemSettings['currency'] ??  ($currency ?? '') }}</span>
                </div>

                <div id="payment-element" class="mt-2"></div>
                <div id="error-message" class="text-center text-danger"></div>
                <button class="btn w-100 mt-2 btn-primary" id="payBtn">{{ $payButtonLabel ?? __('labels.pay_now') }}</button>

                {{-- Gateway specific area: each gateway partial can add hidden forms/elements and scripts --}}
                @isset($gateway)
                    @includeIf('payments.gateways.' . $gateway)
                @endisset
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            (function () {
                var shouldWarnOnUnload = true;

                function beforeUnloadHandler(e) {
                    if (!shouldWarnOnUnload) return;
                    e.preventDefault();
                    e.returnValue = '';
                    return '';
                }

                // Attach listener immediately for this payment page
                window.addEventListener('beforeunload', beforeUnloadHandler, {capture: true});

                // Expose helpers for gateway scripts to control behavior
                window.disableLeaveWarning = function () {
                    shouldWarnOnUnload = false;
                    window.removeEventListener('beforeunload', beforeUnloadHandler, {capture: true});
                };
                window.enableLeaveWarning = function () {
                    if (!shouldWarnOnUnload) {
                        shouldWarnOnUnload = true;
                        window.addEventListener('beforeunload', beforeUnloadHandler, {capture: true});
                    }
                };
            })();
        </script>
        @isset($gateway)
            @if($gateway === 'paystack' && !empty($authorizationUrl ?? null))
                <script>
                    (function(){
                        const payBtn = document.getElementById('payBtn');
                        const authUrl = @json($authorizationUrl ?? '');
                        payBtn?.addEventListener('click', function(){
                            try {
                                window.disableLeaveWarning && window.disableLeaveWarning();
                                if (authUrl) {
                                    window.location.href = authUrl;
                                } else {
                                    alert('Authorization URL missing.');
                                    window.enableLeaveWarning && window.enableLeaveWarning();
                                }
                            } catch (e) {
                                window.enableLeaveWarning && window.enableLeaveWarning();
                            }
                        });
                    })();
                </script>
            @elseif($gateway === 'flutterwave' && !empty($authorizationUrl ?? null))
                <script>
                    (function(){
                        const payBtn = document.getElementById('payBtn');
                        const authUrl = @json($authorizationUrl ?? '');
                        payBtn?.addEventListener('click', function(){
                            try {
                                window.disableLeaveWarning && window.disableLeaveWarning();
                                payBtn.disabled = true;
                                if (authUrl) {
                                    window.location.href = authUrl;
                                } else {
                                    alert('Authorization URL missing.');
                                    payBtn.disabled = false;
                                    window.enableLeaveWarning && window.enableLeaveWarning();
                                }
                            } catch (e) {
                                payBtn.disabled = false;
                                window.enableLeaveWarning && window.enableLeaveWarning();
                            }
                        });
                    })();
                </script>
            @endif
        @endisset
    @endpush
@endsection
