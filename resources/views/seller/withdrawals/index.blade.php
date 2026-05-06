@extends('layouts.seller.app', ['page' => $menuSeller['wallet']['active'] ?? "", 'sub_page' => $menuSeller['wallet']['route']['withdrawals']['sub_active']])

@section('title', __('labels.withdrawals'))

@section('header_data')
    @php
        $page_title = __('labels.withdrawals');
        $page_pretitle = __('labels.seller') . " " . __('labels.wallet');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.wallet'), 'url' => route('seller.wallet.index')],
        ['title' => __('labels.withdrawals'), 'url' => '']
    ];
@endphp


@section('seller-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.request_withdrawal') }}</h3>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <a href="{{ route('seller.withdrawals.history') }}"
                                           class="btn btn-outline-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-history">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M12 8l0 4l2 2"/>
                                                <path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"/>
                                            </svg>
                                            {{ __('labels.withdrawal_history') }}
                                        </a>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('seller.wallet.index') }}" class="btn btn-outline-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-left">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M5 12l14 0"/>
                                                <path d="M5 12l6 6"/>
                                                <path d="M5 12l6 -6"/>
                                            </svg>
                                            {{ __('labels.back_to_wallet') }}
                                        </a>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-outline-primary" id="refresh">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                                <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                            </svg>
                                            {{ __('labels.refresh') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                        <div class="card-body">
                            <div class="row">
                                @if($requestWithdrawPermission ?? false)
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h3 class="card-title">{{ __('labels.request_withdrawal') }}</h3>
                                            </div>
                                            <div class="card-body">
                                                <form class="form-submit" method="POST"
                                                      action="{{ route('seller.withdrawals.store') }}">
                                                    @csrf
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label">{{ __('labels.available_for_withdrawal') }}</label>
                                                        <div
                                                            class="form-control-plaintext">{{$systemSettings['currencySymbol'] ?? ''}}{{ $wallet ? number_format($wallet->balance - $wallet->blocked_balance, 2) : '0.00' }}</div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label">{{ __('labels.blocked_balance') }}</label>
                                                        <div
                                                            class="form-control-plaintext">{{$systemSettings['currencySymbol'] ?? ''}}{{ $wallet ? number_format($wallet->blocked_balance , 2) : '0.00' }}</div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label required">{{ __('labels.withdrawal_amount') }}</label>
                                                        <input type="number" class="form-control" name="amount"
                                                               id="amount"
                                                               min="1" max="{{ $wallet ? $wallet->balance : 0 }}"
                                                               step="0.01" required>
                                                        <div class="invalid-feedback" id="amount-error"></div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label">{{ __('labels.request_note') }}</label>
                                                        <textarea class="form-control" name="note" id="note"
                                                                  rows="3"></textarea>
                                                        <div class="invalid-feedback" id="note-error"></div>
                                                    </div>
                                                    <div class="form-footer">
                                                        <button type="submit" class="btn btn-primary"
                                                                id="submit-btn">{{ __('labels.submit_request') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="{{$requestWithdrawPermission ? 'col-md-6' : 'col-md-12' }}">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">{{ __('labels.pending_withdrawal_requests') }}</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <x-datatable id="withdrawal-requests-table" :columns="$columns"
                                                             route="{{ route('seller.withdrawals.datatable') }}"
                                                             :options="['order' => [[0, 'desc']],'pageLength' => 5,]"/>
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

@push('scripts')
    <script src="{{asset('assets/js/seller-withdrawals.js')}}"></script>
@endpush
