@extends('layouts.seller.app', ['page' => $menuSeller['stores']['active'] ?? ""])

@section('title', __('labels.stores'))

@section('header_data')
    @php
        $page_title = __('labels.stores');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.stores'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.stores') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                <select class="form-select text-capitalize" id="verificationStatus">
                                    <option value="">{{ __('labels.select_verification_status')}}</option>
                                    @foreach($verificationStatus as $status)
                                        <option
                                            value="{{ $status }}">{{ Str::replace('_', ' ', $status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select text-capitalize" id="visibilityStatus">
                                    <option value="">{{ __('labels.select_visibility_status')}}</option>
                                    @foreach($visibilityStatus as $vStatus)
                                        <option
                                            value="{{ $vStatus }}">{{ Str::replace('_', ' ', $vStatus) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                @if($createPermission && ($storeCreateLimitReached ?? false))
                                    <button type="button" class="btn btn-6 btn-outline-primary" disabled>
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
                                            class="icon icon-2"
                                        >
                                            <path d="M12 5l0 14"/>
                                            <path d="M5 12l14 0"/>
                                        </svg>
                                        {{ __('labels.add_new_store') }}
                                    </button>
                                @elseif($createPermission)
                                    <a href="{{route('seller.stores.create')}}" class="btn btn-6 btn-outline-primary">
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
                                            class="icon icon-2"
                                        >
                                            <path d="M12 5l0 14"/>
                                            <path d="M5 12l14 0"/>
                                        </svg>
                                        {{ __('labels.add_new_store') }}
                                    </a>
                                @endif
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-outline-primary refresh-table">
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
                @if($storeCreateLimitReached ?? false)
                    <div class="card-body border-bottom py-3">
                        <div class="alert alert-warning mb-0" role="alert">
                            {{ $storeCreateLimitMessage }}
                        </div>
                    </div>
                @endif
                <div class="card-table">
                    <div class="row w-full p-3">
                        <x-datatable id="stores-table" :columns="$columns"
                                     route="{{ route('seller.stores.datatable') }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ hyperAsset('assets/js/stores.js') }}" defer></script>
@endpush
