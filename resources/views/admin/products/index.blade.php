@php
    use App\Enums\Product\ProductVarificationStatusEnum;
    $isApprovalView = request()->query('verification_status') === ProductVarificationStatusEnum::PENDING();
@endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['products']['active'] ?? "", 'sub_page' => $menuAdmin['products']['route'][$isApprovalView ? 'pending_approval_products' : 'products']['sub_active']])


@section('title', __('labels.products'))

@section('header_data')
    @php
        $page_title = __('labels.products');
        $page_pretitle = __('labels.admin') . " " . __('labels.products');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.products'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">{{ __('labels.products') }}</h3>
                                <x-breadcrumb :items="$breadcrumbs"/>
                            </div>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <select class="form-select" id="productTypeFilter">
                                            <option value="">{{ __('labels.product_type') }}</option>
                                            @foreach(\App\Enums\Product\ProductTypeEnum::values() as $type)
                                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productStatusFilter">
                                            <option value="">{{ __('labels.product_status') }}</option>
                                            @foreach(\App\Enums\Product\ProductStatusEnum::values() as $type)
                                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productVerificationStatusFilter">
                                            <option value="">{{ __('labels.verification_status') }}</option>
                                            @foreach(ProductVarificationStatusEnum::values() as $type)
                                                <option
                                                    value="{{ $type }}">{{ ucfirst(\Illuminate\Support\Str::replace("_", " ",$type)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productCategoryFilter" placeholder="{{ __('labels.category') }}">
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
                                <x-datatable id="products-table" :columns="$columns"
                                             route="{{ route('admin.products.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="view-product-offcanvas" aria-labelledby="offcanvasEndLabel">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title" id="offcanvasEndLabel">Product Details</h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="card card-sm border-0">
                <label class="fw-medium pb-1">Image</label>
                <div class="img-box-200px-h card-img">
                    <img id="product-image" src=""/>
                </div>
                <div class="card-body px-0">
                    <div>
                        <h4 id="product-name" class="fs-3"></h4>
                        <p id="product-description" class="fs-4"></p>
                        <p class="col-md-8 d-flex justify-content-between">Status: <span id="product-status"
                                                                                         class="badge bg-green-lt text-uppercase fw-medium"></span>
                        </p>
                        <p class="col-md-8 d-flex justify-content-between">Category: <span id="product-category"
                                                                                           class="fw-medium"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{hyperAsset('assets/js/product.js')}}" defer></script>
@endpush
