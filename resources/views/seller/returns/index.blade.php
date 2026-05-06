@extends('layouts.seller.app', ['page' => $menuSeller['return_orders']['active'] ?? "", 'sub_page' => $menuSeller['return_orders']['route']['return_requests']['sub_active']])

@section('title', __('labels.return_requests'))

@section('header_data')
    @php
        $page_title = __('labels.return_requests');
        $page_pretitle = __('labels.seller') . " " . __('labels.return_requests');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.return_requests'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.return_requests') }}</h3>
                            <div class="card-actions">
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

                        <div class="card-body">
                            <div class="table-responsive">
                                <x-datatable id="returns-table" :columns="$columns"
                                             route="{{ route('seller.returns.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('seller.returns.partials.return-accept-modal')
    {{--    @include('seller.returns.partials.order-preparing-modal')--}}
    @include('seller.returns.partials.returns-reject-modal')
@endsection

@push('scripts')
    <script src="{{ hyperAsset('assets/js/seller-returns.js') }}" defer></script>
@endpush
