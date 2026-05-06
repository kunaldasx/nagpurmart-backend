@extends('layouts.seller.app', ['page' => $menuSeller['wallet']['active'] ?? "", 'sub_page' => $menuSeller['wallet']['route']['balance']['sub_active']])

@section('title', __('labels.wallet_balance'))

@section('header_data')
    @php
        $page_title = __('labels.wallet_balance');
        $page_pretitle = __('labels.seller') . " " . __('labels.wallet');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.wallet'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.wallet_balance') }}</h3>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <a href="{{ route('seller.wallet.transactions') }}"
                                           class="btn btn-outline-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-history">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M12 8l0 4l2 2"/>
                                                <path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"/>
                                            </svg>
                                            {{ __('labels.transaction_history') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body p-4 text-center">
                                            <h3 class="m-0 mb-1">{{ __('labels.current_balance') }}</h3>
                                            <div class="text-muted">{{ __('labels.available_for_withdrawal') }}</div>
                                            <div class="display-5 fw-bold my-3">
                                                {{$systemSettings['currencySymbol'] ?? ''}}{{ $wallet ? number_format($wallet->balance - $wallet->blocked_balance, 2) : '0.00' }}
                                            </div>
                                            @if($requestWithdrawPermission ?? false)

                                                <div class="d-flex align-items-center justify-content-center">
                                                    <a href="{{ route('seller.withdrawals.index') }}"
                                                       class="btn btn-primary">
                                                        {{ __('labels.request_withdrawal') }}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">{{ __('labels.wallet_information') }}</h3>
                                        </div>
                                        {{--                                        @dd($systemSettings['currencySymbol'])--}}
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table mb-0" style="border: none;">
                                                    <tbody>
                                                    <tr>
                                                        <td class="fw-bold border-0"
                                                            style="width: 180px;">{{ __('labels.wallet_id') }}</td>
                                                        <td class="border-0">{{ $wallet ? $wallet->id : 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.blocked_balance') }}</td>
                                                        <td class="border-0">{{$systemSettings['currencySymbol'] ?? ''}}{{ $wallet ? $wallet->blocked_balance : '0.00' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.currency') }}</td>
                                                        <td class="border-0">{{ $systemSettings['currency'] ?? '' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold border-0">{{ __('labels.last_updated') }}</td>
                                                        <td class="border-0">{{ $wallet ? $wallet->updated_at->format('d M Y, H:i') : 'N/A' }}</td>
                                                    </tr>
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
            </div>
        </div>
    </div>
@endsection
