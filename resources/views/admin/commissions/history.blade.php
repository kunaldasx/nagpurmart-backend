@extends('layouts.admin.app', ['page' => $menuAdmin['seller_management']['active'] ?? "", 'sub_page' => $menuAdmin['seller_management']['route']['earning_settlement']['sub_active']])

@section('title', __('labels.settlement_history'))

@section('header_data')
    @php
        $page_title = __('labels.settlement_history');
        $page_pretitle = __('labels.admin') . " " . __('labels.settlement_history');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.commission_settlements'), 'url' => route('admin.commissions.index')],
        ['title' => __('labels.settlement_history'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.settlement_history') }}</h3>
                            <div class="card-actions">
                                <div class="row g-2 align-items-center">
                                    <div class="col-auto">
                                        <select class="form-select" id="storeFilter"
                                                placeholder="{{ __('labels.store') }}" autocomplete="off"></select>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('admin.commissions.index') }}" class="btn btn-outline-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-left">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M5 12l14 0"/>
                                                <path d="M5 12l6 6"/>
                                                <path d="M5 12l6 -6"/>
                                            </svg>
                                            {{ __('labels.back_to_unsettled') }}
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
                                <x-datatable id="commissions-history-table" :columns="$columns"
                                             route="{{ route('admin.commissions.history.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
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
