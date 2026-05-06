@php use App\Enums\Subscription\SubscriptionTransactionStatusEnum; @endphp
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
                <div class="text-center" style="font-size: 50px;">
                    @if($transaction->status == SubscriptionTransactionStatusEnum::COMPLETED())
                        <i class="ti ti-circle-check text-success"></i>
                    @elseif($transaction->status == SubscriptionTransactionStatusEnum::FAILED())
                        <i class="ti ti-circle-x text-danger"></i>
                    @else
                        <i class="ti ti-cancel text-warning"></i>
                    @endif
                    <h3 class="text-capitalize">{{$transaction->status}}</h3>
                </div>
                <h2>{{ __('labels.subscription_payment_summary') }}</h2>

                @isset($plan)
                    <div class="d-flex align-items-center justify-content-between mb-2 fw-bold">
                        <span>{{ __('labels.plan') }}:</span>
                        <span>{{ $plan->name ?? $planName ?? '' }}</span>
                    </div>
                @endisset

                @isset($plan)
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span>{{ __('labels.duration') }}:</span>
                        <span>{{ ($plan->duration_type ?? null) === 'unlimited' ? __('labels.unlimited') : ((($plan->duration_days ?? 0)).' '.__('labels.days')) }}</span>
                    </div>
                @endisset

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span>{{ __('labels.system_transaction_id') }}:</span>
                    <span>#{{ $transaction->id }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span>{{ __('labels.payment_id') }}:</span>
                    <span>{{ $transaction->transaction_id }}</span>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span>{{ __('labels.status') ?? 'Status' }}:</span>
                    <span class="fw-bold">{{ (string)($transaction->status ?? '-') }}</span>
                </div>

                <hr class="my-3"/>

                <div class="d-flex align-items-center justify-content-between h3">
                    <span>{{ __('labels.amount') }}</span>
                    <span>
                        {{ number_format((float)($transaction->amount ?? 0), 2) }}
                        {{ $systemSettings['currency'] ?? ($transaction->currency ?? '') }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-center">
                    <a href="{{route('seller.subscription-plans.current')}}" class="btn btn-primary">{{__('labels.back_to_subscription')}}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
