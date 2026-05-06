@extends('layouts.seller.app', ['page' => $menuSeller['products']['active'] ?? ""])

@section('title', __('labels.product_details'))

@section('header_data')
    @php
        $page_title = __('labels.product_details');
        $page_pretitle = __('labels.seller') . " " . __('labels.product_details');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.products'), 'url' => route('seller.products.index')],
        ['title' => __('labels.product_details'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="page-wrapper">
        <!-- BEGIN PAGE HEADER -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <h2 class="page-title">{{ __('labels.product_details') }}</h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <a href="{{ route('seller.products.index') }}"
                               class="btn btn-secondary d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-left"
                                     width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                     fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M5 12l14 0"></path>
                                    <path d="M5 12l6 6"></path>
                                    <path d="M5 12l6 -6"></path>
                                </svg>
                                {{ __('labels.back_to_products') }}
                            </a>
                            <a href="{{ route('seller.products.edit', $product->id) }}"
                               class="btn btn-primary d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit"
                                     width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                     fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                    <path
                                        d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                    <path d="M16 5l3 3"></path>
                                </svg>
                                {{ __('labels.edit_product') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END PAGE HEADER -->

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-cards">
                    <!-- Product Summary Card -->
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.product_summary') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="datagrid">
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.product_name') }}</div>
                                        <div class="datagrid-content">{{ $product->title }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.product_type') }}</div>
                                        <div class="datagrid-content text-capitalize">{{ $product->type }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.status') }}</div>
                                        <div class="datagrid-content text-capitalize">
                                            <span
                                                class="badge {{ $product->status === 'active' ? 'bg-green-lt' : 'bg-red-lt' }}">
                                                {{ $product->status }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.verification_status') }}</div>
                                        <div class="datagrid-content text-capitalize">
                                            <span
                                                class="badge {{ $product->verification_status === 'verified' ? 'bg-green-lt' : 'bg-yellow-lt' }}">
                                                {{ $product->verification_status }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.category') }}</div>
                                        <div class="datagrid-content">{{ $product->category->title ?? 'N/A' }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.brand') }}</div>
                                        <div class="datagrid-content">{{ $product->brand->title ?? 'N/A' }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.created_at') }}</div>
                                        <div class="datagrid-content">{{ $product->created_at }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.updated_at') }}</div>
                                        <div class="datagrid-content">{{ $product->updated_at }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Image Card -->
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.product_image') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-center align-items-center">
                                    <a href="{{ $product->main_image }}" class="img-box-w-300" target="_blank"
                                       data-fslightbox="gallery">
                                        <img src="{{ $product->main_image }}" alt="{{ $product->title }}"
                                             class="rounded">
                                    </a>
                                </div>
                                @if($product->additional_images && count($product->additional_images) > 0)
                                    <div class="mt-3">
                                        <h4>{{ __('labels.additional_images') }}</h4>
                                        <div class="row g-2 mt-1">
                                            @foreach($product->additional_images as $image)
                                                <div class="col-4">
                                                    <a href="{{ $image }}" class="" target="_blank"
                                                       data-fslightbox="gallery">
                                                        <img src="{{ $image }}" alt="{{ $product->title }}"
                                                             class="rounded">
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @php
                        $customFields = is_array($product->custom_fields ?? null) ? $product->custom_fields : [];
                    @endphp
                    @if(!empty($customFields))
                        <!-- Custom Fields Card -->
                        <div class="col-12 mt-3">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('labels.custom_fields') ?? 'Custom Fields' }}</h3>
                                </div>
                                <div class="card-body">
                                    <div class="datagrid">
                                        @foreach($customFields as $cfKey => $cfValue)
                                            <div class="datagrid-item">
                                                <div class="datagrid-title text-capitalize">{{ ucwords(str_replace('_',' ', (string)$cfKey)) }}</div>
                                                <div class="datagrid-content">{{ is_array($cfValue) ? implode(', ', $cfValue) : $cfValue }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Product Description Card -->
                    <div class="col-12 mt-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.product_description') }}</h3>
                            </div>
                            <div class="card-body">
                                {!! $product->description !!}
                            </div>
                        </div>
                    </div>

                    <!-- Store-wise Pricing Card -->
                    @if(isset($storeVariantPricing) && count($storeVariantPricing) > 0)
                        <div class="col-12 mt-3">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('labels.store_wise_pricing') }}</h3>
                                </div>
                                <div class="card-body">
                                    @foreach($storeVariantPricing as $variantId => $variantData)
                                        <div class="mb-4">
                                            <h4>{{ __('labels.variant_name') . " : " . $variantData['title'] }}</h4>

                                            @if(count($variantData['attributes']) > 0)
                                                <div class="mb-2">
                                                    <strong>{{ __('labels.attributes') }}:</strong>
                                                    @php
                                                        $attributeTexts = [];
                                                        foreach($variantData['attributes'] as $attr) {
                                                            $attributeTexts[] = $attr['attribute_name'] . ': ' . $attr['attribute_value'];
                                                        }
                                                        echo implode(', ', $attributeTexts);
                                                    @endphp
                                                </div>
                                            @endif

                                            @if(count($variantData['store_pricing']) > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-vcenter card-table">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('labels.store') }}</th>
                                                                <th>{{ __('labels.sku') }}</th>
                                                                <th>{{ __('labels.price') }}</th>
                                                                <th>{{ __('labels.special_price') }}</th>
                                                                <th>{{ __('labels.cost') }}</th>
                                                                <th>{{ __('labels.stock') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($variantData['store_pricing'] as $storePricing)
                                                                <tr>
                                                                    <td>{{ $storePricing['store_name'] }}</td>
                                                                    <td>{{ $storePricing['sku'] }}</td>
                                                                    <td>{{ $systemSettings['currencySymbol'] . number_format($storePricing['price'], 2) }}</td>
                                                                    <td>{{ $storePricing['special_price'] ? $systemSettings['currencySymbol'] . number_format($storePricing['special_price'], 2) : 'N/A' }}</td>
                                                                    <td>{{ $storePricing['cost'] ? $systemSettings['currencySymbol'] . number_format($storePricing['cost'], 2) : 'N/A' }}</td>
                                                                    <td>{{ $storePricing['stock'] }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="text-muted">{{ __('labels.no_store_pricing_available') }}</p>
                                            @endif
                                        </div>
                                        @if(!$loop->last)
                                            <hr>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Product FAQs Card -->
                    @if($product->faqs->count() > 0)
                        <div class="col-12 mt-3">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('labels.product_faqs') }}</h3>
                                </div>
                                <div class="card-body">
                                    <div class="accordion" id="product-faqs">
                                        @foreach($product->faqs as $index => $faq)
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="faq-heading-{{ $index }}">
                                                    <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}"
                                                            type="button" data-bs-toggle="collapse"
                                                            data-bs-target="#faq-collapse-{{ $index }}"
                                                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                                            aria-controls="faq-collapse-{{ $index }}">
                                                        {{ $faq->question }}
                                                    </button>
                                                </h2>
                                                <div id="faq-collapse-{{ $index }}"
                                                     class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                                     aria-labelledby="faq-heading-{{ $index }}"
                                                     data-bs-parent="#product-faqs">
                                                    <div class="accordion-body">
                                                        {{ $faq->answer }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/product.js') }}"></script>
@endpush
