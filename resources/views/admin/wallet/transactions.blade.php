@extends('layouts.admin.app', ['page' => $menuAdmin['customers']['active'] ?? "", 'sub_page' => $menuAdmin['customers']['route']['transactions']['sub_active'] ?? null])

@section('title', __('labels.wallet_transactions'))

@section('header_data')
    @php
        $page_title = __('labels.wallet_transactions');
        $page_pretitle = __('labels.admin') . " " . __('labels.wallet');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.wallet_transactions'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.wallet_transactions') }}</h3>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <button class="btn btn-outline-primary" id="refresh">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
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
                            <div class="row w-full p-3">
                                <x-datatable id="admin-wallet-transactions-table" :columns="$columns"
                                             route="{{ route('admin.wallet.transactions.datatable') }}"
                                             :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const refresh = document.getElementById('refresh');
            if (refresh) refresh.addEventListener('click', function () { window.LarabergDataTables?.reload?.('admin-wallet-transactions-table') || window.location.reload(); });
        });
    </script>
@endpush
