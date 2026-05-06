@extends('layouts.admin.app', ['page' => $menuAdmin['subscriptions']['active'] ?? '', 'sub_page' => $menuAdmin['subscriptions']['route']['subscribers']['sub_active'] ?? null])

@section('title', __('labels.subscription_details'))

@section('header_data')
    @php
        $page_title = __('labels.subscription_details');
        $page_pretitle = __('labels.view');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.subscriptions'), 'url' => route('admin.subscription-plans.subscribers')],
        ['title' => __('labels.subscription_details'), 'url' => null],
    ];
    $currency = $currencySymbol ?? '$';
@endphp

@section('admin-content')
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.subscription_details') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                <a href="{{ url()->previous() }}" class="btn btn-outline-primary">{{ __('labels.back') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="list list-row list-separated">
                                <div class="list-item">
                                    <div class="text-muted">{{ __('labels.seller') }}</div>
                                    <div class="fw-medium">{{ $subscription->seller?->user?->name }} <span class="text-muted">({{ $subscription->seller?->user?->email }})</span></div>
                                </div>
                                <div class="list-item">
                                    <div class="text-muted">{{ __('labels.plan') }}</div>
                                    <div class="fw-medium">{{ $subscription->plan?->name ?? $subscription->snapshot?->plan_name }}</div>
                                </div>
                                <div class="list-item">
                                    <div class="text-muted">{{ __('labels.price_paid') }}</div>
                                    <div class="fw-medium">{{ $currency }} {{ number_format((float)($subscription->price_paid ?? $subscription->snapshot?->price ?? 0), 2) }}</div>
                                </div>
                                <div class="list-item">
                                    <div class="text-muted">{{ __('labels.period') }}</div>
                                    <div class="fw-medium">{{ optional($subscription->start_date)->format('Y-m-d') ?? '-' }} / {{ optional($subscription->end_date)->format('Y-m-d') ?? __('labels.unlimited') }}</div>
                                </div>
                                <div class="list-item">
                                    <div class="text-muted">{{ __('labels.status') }}</div>
                                    <div>{!! view('partials.status', ['status' => (string)$subscription->status])->render() !!}</div>
                                </div>
                                <div class="list-item">
                                    <div class="text-muted">{{ __('labels.created_at') }}</div>
                                    <div class="fw-medium">{{ optional($subscription->created_at)->format('Y-m-d H:i') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('labels.configuration_usage') }}</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                        <tr>
                                            <th>{{ __('labels.configuration') }}</th>
                                            <th class="text-end">{{ __('labels.limit') }}</th>
                                            <th class="text-end">{{ __('labels.used') }}</th>
                                            <th class="text-end">{{ __('labels.remaining') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($usage as $key => $row)
                                            <tr>
                                                <td class="text-capitalize">{{ str_replace('_', ' ', $key) }}</td>
                                                <td class="text-end">{{ $row['limit'] === null ? __('labels.unlimited') : (int)$row['limit'] }}</td>
                                                <td class="text-end">{{ (int)$row['used'] }}</td>
                                                <td class="text-end">{{ $row['remaining'] === null ? __('labels.unlimited') : (int)$row['remaining'] }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 mt-1">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('labels.transaction_details') }}</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                        <tr>
                                            <th>{{ __('labels.id') }}</th>
                                            <th>{{ __('labels.payment_gateway') }}</th>
                                            <th>{{ __('labels.transaction_id') }}</th>
                                            <th class="text-end">{{ __('labels.amount') }}</th>
                                            <th class="text-center">{{ __('labels.status') }}</th>
                                            <th class="text-end">{{ __('labels.created_at') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($subscription->transactions as $txn)
                                            <tr>
                                                <td>#{{ $txn->id }}</td>
                                                <td class="text-uppercase">{{ $txn->payment_gateway ?? '-' }}</td>
                                                <td class="font-monospace">{{ $txn->transaction_id ?? '-' }}</td>
                                                <td class="text-end">{{ $currency }} {{ number_format((float)($txn->amount ?? 0), 2) }}</td>
                                                <td class="text-center">{!! view('partials.status', ['status' => (string)($txn->status ?? 'pending')])->render() !!}</td>
                                                <td class="text-end">{{ optional($txn->created_at)->format('Y-m-d H:i') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">{{ __('labels.no_transactions_found') }}</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
