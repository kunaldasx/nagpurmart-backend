@php use App\Enums\ActiveInactiveStatusEnum; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['products']['active'] ?? "", 'sub_page' => $menuAdmin['products']['route']['product_faqs']['sub_active']])

@section('title', __('labels.product_faqs'))
@section('header_data')
    @php
        $page_title =  __('labels.product_faqs');
        $page_pretitle = __('labels.admin') . " " . __('labels.products');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.products'), 'url' => route('admin.products.index')],
        ['title' =>  __('labels.product_faqs'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <!-- Page body -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.product_faqs') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                <select class="form-select" name="product_id_filter" id="select-product">
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select" id="faqStatusFilter">
                                    <option value="">{{ __('labels.status') }}</option>
                                    @foreach(ActiveInactiveStatusEnum::values() as $type)
                                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
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
                <div class="card-table">
                    <div class="row w-full p-3">
                        <x-datatable id="product-faqs-table" :columns="$columns"
                                     route="{{ route('admin.product_faqs.datatable') }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{hyperAsset('assets/js/product.js')}}" defer></script>
@endpush
