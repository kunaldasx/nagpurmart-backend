@extends('layouts.seller.app', ['page' => $menuSeller['earnings']['active'] ?? ""])

@section('title', __('labels.payment_return_settlements'))

@section('header_data')
    @php
        $page_title = __('labels.payment_return_settlements');
        $page_pretitle = __('labels.admin') . " " . __('labels.payment_return_settlements');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.payment_return_settlements'), 'url' => '']
    ];
@endphp
@section('seller-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.earning_deductions') }}</h3>
                            <div class="card-actions">
                                <div class="row g-2 align-items-center">
                                    <div class="col-auto">
                                        <select class="form-select"  id="storeFilter" placeholder="{{ __('labels.store') }}" autocomplete="off"></select>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('seller.commissions.history') }}"
                                           class="btn btn-outline-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-history">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M12 8l0 4l2 2"/>
                                                <path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"/>
                                            </svg>
                                            {{ __('labels.settlement_history') }}
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
                            <div class="row w-full p-3">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-credits" role="tab"
                                           aria-selected="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon me-2 icon-tabler icons-tabler-outline icon-tabler-bell-dollar">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path
                                                    d="M13 17h-9a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6a2 2 0 1 1 4 0a7 7 0 0 1 3.911 5.17"/>
                                                <path d="M9 17v1a3 3 0 0 0 4.02 2.822"/>
                                                <path d="M21 15h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5"/>
                                                <path d="M19 21v1m0 -8v1"/>
                                            </svg>
                                            {{ __('labels.payouts') }}
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-bs-toggle="tab" href="#tab-debits" role="tab"
                                           aria-selected="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon me-2 icon-tabler icons-tabler-outline icon-tabler-bell-minus">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path
                                                    d="M12.5 17h-8.5a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3c.047 .386 .149 .758 .3 1.107"/>
                                                <path d="M9 17v1a3 3 0 0 0 3.504 2.958"/>
                                                <path d="M16 19h6"/>
                                            </svg>
                                            {{ __('labels.returns_deductions') }}
                                        </a>
                                    </li>
                                </ul>
                                <div class="tab-content pt-3">
                                    <div class="tab-pane active" id="tab-credits" role="tabpanel">
                                        <x-datatable id="commissions-table" :columns="$columns"
                                                     route="{{ route('seller.commissions.datatable') }}"
                                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                                    </div>
                                    <div class="tab-pane" id="tab-debits" role="tabpanel">
                                        <x-datatable id="debits-table" :columns="$columnsReturn"
                                                     route="{{ route('seller.commissions.debits.datatable') }}"
                                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
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
    <script src="{{asset('assets/js/manage-seller.js')}}" defer></script>
@endpush
