@php use App\Models\Setting; @endphp
@extends('layouts.admin.app',  ['page' => $menuAdmin['stores']['active'] ?? ""])

@section('title', __('labels.stores'))

@section('header_data')
    @php
        $page_title = __('labels.stores');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.stores'), 'url' => null],
    ];
@endphp

@section('admin-content')
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
                @if(Setting::isSystemVendorTypeMultiple())
                    <div class="card-table">
                        <div class="align-items-center d-lg-flex d-block justify-content-between pt-3 px-3">
                            <div class="row w-100">
                                <div class="col-md-3">
                                    <label
                                        class="form-label">{{ __('labels.search_by_seller') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           name="name"
                                           id="select-seller"
                                           multiple
                                           placeholder="{{ __('labels.enter_seller_name') }}"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label
                                        class="form-label">{{ __('labels.filter_by_verification_status') }}</label>
                                    <select class="form-select text-capitalize" id="verification-status">
                                        <option value="">{{ __('labels.select_status')}}</option>
                                        @foreach($verificationStatus as $status)
                                            <option
                                                value="{{ $status }}">{{ Str::replace('_', ' ', $status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label
                                        class="form-label">{{ __('labels.filter_by_visibility_status') }}</label>
                                    <select class="form-select text-capitalize" id="visibility-status">
                                        <option value="">{{ __('labels.select_status')}}</option>
                                        @foreach($visibilityStatus as $vStatus)
                                            <option
                                                value="{{ $vStatus }}">{{ Str::replace('_', ' ', $vStatus) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary mt-2" id="btn-filter">Filter</button>
                        </div>
                        @endif
                        <div class="alert alert-info alert-dismissible m-3" role="alert">
                            <div class="alert-icon">
                                <!-- Download SVG icon from http://tabler.io/icons/icon/info-circle -->
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
                                    class="icon alert-icon icon-2"
                                >
                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/>
                                    <path d="M12 9h.01"/>
                                    <path d="M11 12h1v4h1"/>
                                </svg>
                            </div>
                            {{__('messages.verify_store_info')}}
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                        <div class="row w-full p-3">
                            <x-datatable id="stores-table" :columns="$columns"
                                         route="{{ route('admin.sellers.store.datatable', isset($seller) ? ['seller_id' => $seller->id] : []) }}"
                                         :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                        </div>
                    </div>
            </div>
        </div>
    </div>
@endsection
