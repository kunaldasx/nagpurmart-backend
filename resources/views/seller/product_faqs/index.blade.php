@extends('layouts.seller.app', ['page' => $menuSeller['products']['active'] ?? "", 'sub_page' => $menuSeller['products']['route']['product_faqs']['sub_active']])

@section('title', __('labels.product_faqs'))
@section('header_data')
    @php
        $page_title =  __('labels.product_faqs');
        $page_pretitle = __('labels.seller') . " " . __('labels.products');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.products'), 'url' => route('seller.products.index')],
        ['title' =>  __('labels.product_faqs'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <!-- Page body -->
    <div class="page-body">
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
                                    <select class="form-select" id="faqStatusFilter">
                                        <option value="">{{ __('labels.status') }}</option>
                                        @foreach(\App\Enums\ActiveInactiveStatusEnum::values() as $type)
                                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    @if($createPermission ?? false)
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#product-faq-modal">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                 stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <line x1="12" y1="5" x2="12" y2="19"/>
                                                <line x1="5" y1="12" x2="19" y2="12"/>
                                            </svg>
                                            {{ __('labels.add_product_faq') }}
                                        </button>
                                    @endif
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
                                         route="{{ route('seller.product_faqs.datatable') }}"
                                         :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product FAQ Modal -->
    @if(($createPermission ?? false) || ($editPermission ?? false))
        <div class="modal modal-blur fade" id="product-faq-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="product-faq-modal-title">{{ __('labels.add_product_faq') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form class="form-submit" id="product-faq-form" method="POST"
                          action="{{ route('seller.product_faqs.store') }}">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('labels.product') }}</label>
                                        <select class="form-select" id="select-product" name="product_id" required>
                                            <option value="">{{ __('labels.select_product') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('labels.question') }}</label>
                                        <textarea class="form-control" id="question" name="question" rows="3"
                                                  placeholder="{{ __('labels.enter_question') }}" required></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('labels.answer') }}</label>
                                        <textarea class="form-control" id="answer" name="answer" rows="4"
                                                  placeholder="{{ __('labels.enter_answer') }}" required></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('labels.status') }}</label>
                                        <select class="form-select text-capitalize" id="status" name="status">
                                            @foreach(\App\Enums\ActiveInactiveStatusEnum::values() as $status)
                                                <option value="{{$status}}">{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#" class="btn" data-bs-dismiss="modal">
                                {{ __('labels.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary ms-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                     viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                {{ __('labels.submit') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
@push('scripts')
    <script src="{{hyperAsset('assets/js/product.js')}}" defer></script>
@endpush
