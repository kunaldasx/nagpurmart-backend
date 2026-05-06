@php use App\Enums\Subscription\SubscriptionPlanKeyEnum;use Illuminate\Support\Str; @endphp
@extends('layouts.seller.app', ['page' => $menuSeller['subscriptions']['active'] ?? "", 'sub_page' => $menuSeller['subscriptions']['route']['current']['sub_active'] ?? null])

@section('title', __('labels.current_subscription'))

@section('header_data')
    @php
        $page_title = __('labels.current_subscription');
        $page_pretitle = __('labels.subscriptions');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.subscriptions'), 'url' => route('seller.subscription-plans.index')],
        ['title' => __('labels.current_subscription'), 'url' => '']
    ];
    $currency = $systemSettings['currencySymbol'] ?? ($subscriptionSettings['currencySymbol'] ?? '$');
@endphp

@section('seller-content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ __('labels.current_subscription') }}</h2>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    @if(!$subscription)
                        <div class="alert alert-info" role="alert">
                            {{ __('labels.no_active_subscription_found') }}
                        </div>
                    @else
                        <div class="row">
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div
                                        class="text-uppercase text-secondary fw-medium">{{ $subscription->snapshot?->plan_name }}</div>
                                    <div
                                        class="display-6 fw-bold my-2">{{ $currency }} {{ number_format((float)( $subscription->snapshot?->price ?? 0), 2) }}</div>
                                    <div class="small text-secondary mb-2">
                                        {{ __('labels.duration') }}:
                                        @php
                                            $duration = $subscription->plan?->duration_type === 'unlimited'
                                                ? __('labels.unlimited')
                                                : (($subscription->snapshot?->duration_days ?? 0).' '.__('labels.days'));
                                        @endphp
                                        {{ $duration }}
                                    </div>
                                    <div class="mb-2">
                                        {!! view('partials.status', ['status' => (string)$subscription->status])->render() !!}
                                    </div>
                                    <div class="small text-secondary">
                                        {{ __('labels.start_date') }}
                                        : {{ optional($subscription->start_date)->format('Y-m-d') ?? '-' }}<br>
                                        {{ __('labels.end_date') }}
                                        : {{ optional($subscription->end_date)->format('Y-m-d') ?? '-' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h3>{{ __('labels.plan_configurations') }}</h3>
                                <p class="mb-3">{{ $subscription->plan?->description }}</p>
                                @php $limitMap =$subscription->snapshot->limits_json ?? []; @endphp
                                <div class="table-responsive">
                                    <table class="table table-vcenter table-bordered table-nowrap">
                                        <thead>
                                        <tr>
                                            <th>{{ __('labels.configuration') }}</th>
                                            <th class="w-25 text-center">{{ __('labels.limit') }}</th>
                                            <th class="w-25 text-center">{{ __('labels.used') }}</th>
                                            <th class="w-25 text-center">{{ __('labels.remaining') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach(SubscriptionPlanKeyEnum::values() as $key)
                                            <tr>
                                                <td class="text-capitalize">{{ Str::replace('_',' ', $key) }}</td>
                                                @php
                                                    $detail = $usageDetails[$key] ?? ['limit' => ($limitMap[$key] ?? null), 'used' => 0, 'remaining' => null];
                                                    $limitVal = $detail['limit'];
                                                    $usedVal = $detail['used'] ?? 0;
                                                    $remainingVal = $detail['remaining'];
                                                @endphp
                                                <td class="text-center">{{ $limitVal === null ? __('labels.unlimited') : (int)$limitVal }}</td>
                                                <td class="text-center">{{ (int)$usedVal }}</td>
                                                <td class="text-center">{{ $remainingVal === null ? __('labels.unlimited') : (int)$remainingVal }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
