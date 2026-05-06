@php use App\Enums\Subscription\SubscriptionPlanKeyEnum; use App\Enums\Payment\PaymentTypeEnum; @endphp
@extends('layouts.seller.app', ['page' => $menuSeller['subscriptions']['active'] ?? "", 'sub_page' => $menuSeller['subscriptions']['route']['plans']['sub_active'] ?? null])

@section('title', __('labels.subscription_plan_details'))

@php
    $limitMap = $plan->limits?->pluck('value','key')->toArray() ?? [];
    $currency = $systemSettings['currencySymbol'] ?? ($subscriptionSettings['currencySymbol'] ?? '$');
@endphp

@section('seller-content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ __('labels.subscription_plan_details') }}</h2>
                    <div class="text-secondary">{{ $subscriptionSettings['subscriptionDescription'] ?? '' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-uppercase text-secondary fw-medium">{{ $plan->name }}</div>
                                <div
                                    class="display-6 fw-bold my-2">{{ $plan->is_free ? __('labels.free') : $currency . number_format((float)($plan->price ?? 0), 2) }}</div>
                                <div class="small text-secondary mb-2">
                                    {{ __('labels.duration') }}:
                                    {{ $plan->duration_type === 'unlimited' ? __('labels.unlimited') : (($plan->duration_days ?? 0).' '.__('labels.days')) }}
                                </div>
                                @if($plan->is_default)
                                    <span class="badge bg-blue-lt">{{ __('labels.default_plan') }}</span>
                                @endif
                                {{--@dd($eligible)--}}
                                @foreach ($eligible['details'] as $key => $item)
                                    @php
                                        $used    = $item['used'];
                                        $limit   = $item['limit'];
                                        $ok      = $item['ok'];
                                        $percent = $limit > 0 ? round(($used / $limit) * 100) : 0;
                                        $barClass = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : '');
                                        $label   = ucwords(str_replace('_', ' ', $key));
                                    @endphp

                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-label">
                                                @if($ok)
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                         viewBox="0 0 24 24" fill="currentColor"
                                                         class="icon icon-tabler icons-tabler-filled icon-tabler-circle-check text-success">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path
                                                            d="M17 3.34a10 10 0 1 1 -14.995 8.984l-.005 -.324l.005 -.324a10 10 0 0 1 14.995 -8.336zm-1.293 5.953a1 1 0 0 0 -1.32 -.083l-.094 .083l-3.293 3.292l-1.293 -1.292l-.094 -.083a1 1 0 0 0 -1.403 1.403l.083 .094l2 2l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l4 -4l.083 -.094a1 1 0 0 0 -.083 -1.32z"/>
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                         viewBox="0 0 24 24" fill="currentColor"
                                                         class="icon icon-tabler icons-tabler-filled icon-tabler-xbox-x text-danger">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path
                                                            d="M12 2c5.523 0 10 4.477 10 10s-4.477 10 -10 10s-10 -4.477 -10 -10s4.477 -10 10 -10m3.6 5.2a1 1 0 0 0 -1.4 .2l-2.2 2.933l-2.2 -2.933a1 1 0 1 0 -1.6 1.2l2.55 3.4l-2.55 3.4a1 1 0 1 0 1.6 1.2l2.2 -2.933l2.2 2.933a1 1 0 0 0 1.6 -1.2l-2.55 -3.4l2.55 -3.4a1 1 0 0 0 -.2 -1.4"/>
                                                    </svg>
                                                @endif
                                                {{ $label }}</label>
                                            <label class="form-label {{ $ok ? '' : 'text-danger fw-bold' }}">
                                                {{ $used }} / {{ $limit ?? __('labels.unlimited') }}
                                            </label>
                                        </div>
                                        <div class="progress progress-1 mb-2">
                                            <div class="progress-bar {{ $barClass }}"
                                                 style="width: {{ $percent }}%"
                                                 role="progressbar"
                                                 aria-valuenow="{{ $percent }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"
                                                 aria-label="{{ $percent }}% Complete">
                                                <span class="visually-hidden">{{ $percent }}% Complete</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if(($eligible['eligible'] ?? false) && $buyPermission)
                                    <hr class="my-3">
                                    <form class="form-submit" method="post"
                                          action="{{ route('seller.subscription-plans.buy') }}">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <div class="mt-2 {{$plan->is_free ? 'd-none' : ''}}">
                                            <label for="payment_type"
                                                   class="form-label">{{ __('labels.payment_method') }}</label>
                                            <select class="form-select" name="payment_type">
                                                @foreach([PaymentTypeEnum::RAZORPAY(), PaymentTypeEnum::STRIPE(), PaymentTypeEnum::PAYSTACK(), PaymentTypeEnum::FLUTTERWAVE(), PaymentTypeEnum::WALLET()] as $pm)
                                                    <option
                                                        value="{{$pm}}">{{ ucwords(str_replace(['Payment','_'],' ', (string)$pm)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="d-grid mt-3">
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#buyModel">
                                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                                  aria-hidden="true"></span>
                                                <span class="btn-label">{{ __('labels.buy_now') }}</span>
                                            </button>
                                        </div>
                                        <div
                                            class="modal modal-blur fade"
                                            id="buyModel"
                                            tabindex="-1"
                                            role="dialog"
                                            aria-hidden="true"
                                        >
                                            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    <div class="modal-status bg-success"></div>
                                                    <div class="modal-body text-center py-4">
                                                        <!-- Download SVG icon from http://tabler.io/icons/icon/circle-check -->
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            width="24"
                                                            height="24"
                                                            viewBox="0 0 24 24"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="icon mb-2 text-green icon-lg"
                                                        >
                                                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                                                            <path d="M9 12l2 2l4 -4"/>
                                                        </svg>
                                                        <h3>{{__('labels.confirm_purchase')}}</h3>
                                                        <div class="text-secondary">
                                                            {{__('labels.you_are_about_to_purchase_this_subscription_plan')}}
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <div class="w-100">
                                                            <div class="row">
                                                                <div class="col">
                                                                    <a href="#" class="btn btn-outline-secondary w-100"
                                                                       data-bs-dismiss="modal">{{__('labels.cancel')}}</a>
                                                                </div>
                                                                <div class="col">
                                                                    <button type="submit" class="btn btn-success w-100"
                                                                            data-bs-dismiss="modal">{{__('labels.buy_now')}}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                @elseif(!($eligible['eligible'] ?? false))
                                    <div class="alert alert-danger mt-2" role="alert">
                                        <div class="alert-icon">
                                            <!-- Download SVG icon from http://tabler.io/icons/icon/alert-circle -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon alert-icon icon-2">
                                                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                                <path d="M12 8v4"></path>
                                                <path d="M12 16h.01"></path>
                                            </svg>
                                        </div>
                                        <div class="alert-text">
                                            {{ __('labels.eligibility_error') }}
                                        </div>
                                    </div>
                                @endif

                                @if(($eligible['eligible'] ?? false) && !$buyPermission)
                                    <div class="alert alert-warning mt-3" role="alert">
                                        <div class="alert-text">
                                            {{ __('labels.permission_denied') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h3>{{ __('labels.plan_configurations') }}</h3>
                            <p class="mb-3">{{$plan->description}}</p>
                            <div class="table-responsive">
                                <table class="table table-vcenter table-bordered table-nowrap">
                                    <thead>
                                    <tr>
                                        <th>{{ __('labels.configuration') }}</th>
                                        <th class="w-25 text-center">{{ __('labels.value') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach(SubscriptionPlanKeyEnum::values() as $key)
                                        <tr>
                                            <td class="text-capitalize">{{ \Illuminate\Support\Str::replace('_',' ', $key) }}</td>
                                            <td class="text-center">{{ $limitMap[$key] ?? __('labels.unlimited') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
