@extends('layouts.seller.app', ['page' => $menuSeller['product_conditions']['active'] ?? "", 'sub_page' => $menuSeller['product_conditions']['route']['product_conditions']['sub_active'] ?? ""])

@section('title', __('labels.product_conditions'))

@section('header_data')
    @php
        $page_title = __('labels.product_conditions');
        $page_pretitle = __('labels.seller') . " " . __('labels.product_conditions');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.product_conditions'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('labels.product_conditions') }}</h3>
            <div class="card-actions">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#product-condition-modal">
                    <i class="ti ti-plus me-1"></i> {{ __('labels.add_product_condition') }}
                </button>
            </div>
        </div>
        <div class="card-body">
            <x-datatable id="product-conditions-table" :columns="$columns"
                         route="{{ route('seller.product_conditions.datatable') }}"
                         :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
        </div>
    </div>

    <!-- Add Product Condition Modal -->
    <div class="modal modal-blur fade" id="product-condition-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('labels.add_product_condition') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('seller.product_conditions.store') }}" class="form-submit">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">{{ __('labels.title') }}</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">{{ __('labels.category') }}</label>
                            <select class="form-select" id="select-category" name="category_id" type="text">
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">{{ __('labels.alignment') }}</label>
                            <select class="form-select" name="alignment" required>
                                <option value="strip">{{ __('labels.strip') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" data-bs-dismiss="modal">
                            {{ __('labels.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary ms-auto">
                            <i class="ti ti-plus me-1"></i> {{ __('labels.create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{hyperAsset('assets/js/product.js')}}" defer></script>
@endpush
