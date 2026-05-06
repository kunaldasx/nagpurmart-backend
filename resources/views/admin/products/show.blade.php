@php use App\Enums\Product\ProductVarificationStatusEnum;use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['products']['active'] ?? "", 'sub_page' => $menuAdmin['products']['route']['products']['sub_active']])


@section('title', __('labels.product_details'))

@section('header_data')
    @php
        $page_title = __('labels.product_details');
        $page_pretitle = __('labels.admin') . " " . __('labels.product_details');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.products'), 'url' => route('admin.products.index')],
        ['title' => __('labels.product_details'), 'url' => '']
    ];
@endphp

@section('admin-content')
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
                            <a href="{{ route('admin.products.index') }}"
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
                                        <div class="datagrid-title">{{ __('labels.sold_by') }}</div>
                                        <div
                                            class="datagrid-content text-capitalize">{{ $product->seller->user->name ?? "" }}</div>
                                    </div>
                                    @if(!empty($product->taxClasses) && $product->taxClasses->count() > 0)

                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.tax_rate') }}</div>
                                            <div
                                                class="datagrid-content text-capitalize">{{ $product->taxClasses->pluck('title')->implode(', ')}}</div>
                                        </div>
                                    @endif
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
                                            @php $vs = $product->verification_status; @endphp
                                            <span
                                                class="badge {{ $vs === 'approved' ? 'bg-green-lt' : ($vs === 'rejected' ? 'bg-red-lt' : 'bg-yellow-lt') }}">
                                                {{ Str::replace("_", " ",$vs) }}
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
                    <!-- Custom Product Sections -->
                    @if($product->customProductSections && $product->customProductSections->count())
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('labels.custom_product_sections') }}</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        @foreach($product->customProductSections as $section)
                                            <div class="col-12">
                                                <div class="card card-borderless">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h4 class="mb-0">{{ $section->title }}</h4>
                                                            <span class="badge bg-blue-lt">#{{ $section->sort_order }}</span>
                                                        </div>
                                                        @if(!empty($section->description))
                                                            <p class="text-muted">{{ $section->description }}</p>
                                                        @endif
                                                        @if($section->fields && $section->fields->count())
                                                            <div class="row row-cards">
                                                                @foreach($section->fields as $field)
                                                                    <div class="col-md-4 col-sm-6">
                                                                        <div class="card">
                                                                            <div class="row g-0">
                                                                                @if($field->image)
                                                                                    <div class="col-md-4 p-2">
                                                                                        <img src="{{ $field->image }}"
                                                                                             alt="{{ $field->title }}"
                                                                                             class="img-fluid rounded-start object-contain" style="max-width: 100px;">
                                                                                    </div>
                                                                                @endif
                                                                                <div
                                                                                    class="col-md-{{ $field->image ? '8' : '12' }}">
                                                                                    <div class="card-body">
                                                                                        <div
                                                                                            class="d-flex justify-content-between align-items-center">
                                                                                            <h5 class="card-title mb-0">{{ $field->title }}</h5>
                                                                                            @if(isset($field->pivot))
                                                                                                <span
                                                                                                    class="badge bg-azure-lt">#{{ $field->pivot->sort_order }}</span>
                                                                                            @endif
                                                                                        </div>
                                                                                        @if(!empty($field->description))
                                                                                            <p class="card-text text-muted small mt-2">{{ $field->description }}</p>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div class="text-muted">{{ __('labels.no_fields_found') }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Product Specifications Card -->
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.product_specifications') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="datagrid">
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.is_cancelable') }}</div>
                                        <div class="datagrid-content">
                                            <span
                                                class="badge {{ $product->is_cancelable ? 'bg-green-lt' : 'bg-red-lt' }}">
                                                {{ $product->is_cancelable ? __('labels.yes') : __('labels.no') }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($product->is_cancelable)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.cancelable_till') }}</div>
                                            <div class="datagrid-content">
                                            <span
                                                class="badge bg-primary-lt text-capitalize">
                                                {{ Str::replace("_" , " ", $product->cancelable_till) }}
                                            </span>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.is_returnable') }}</div>
                                        <div class="datagrid-content">
                                            <span
                                                class="badge {{ $product->is_returnable ? 'bg-green-lt' : 'bg-red-lt' }}">
                                                {{ $product->is_returnable ? __('labels.yes') : __('labels.no') }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($product->is_returnable && $product->returnable_days)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.returnable_days') }}</div>
                                            <div
                                                class="datagrid-content">{{ $product->returnable_days }} {{ __('labels.days') }}</div>
                                        </div>
                                    @endif
                                    @if($product->warranty_period)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.warranty_period') }}</div>
                                            <div
                                                class="datagrid-content">{{ $product->warranty_period }} {{ __('labels.days') }}</div>
                                        </div>
                                    @endif
                                    @if($product->guarantee_period)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.guarantee_period') }}</div>
                                            <div
                                                class="datagrid-content">{{ $product->guarantee_period }} {{ __('labels.days') }}</div>
                                        </div>
                                    @endif
                                    @if($product->made_in)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.made_in') }}</div>
                                            <div class="datagrid-content">{{ $product->made_in }}</div>
                                        </div>
                                    @endif
                                    @if($product->hsn_code)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.hsn_code') }}</div>
                                            <div class="datagrid-content">{{ $product->hsn_code }}</div>
                                        </div>
                                    @endif
                                    @if($product->minimum_order_quantity)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.minimum_order_quantity') }}</div>
                                            <div class="datagrid-content">{{ $product->minimum_order_quantity }}</div>
                                        </div>
                                    @endif
                                    @if($product->quantity_step_size)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.quantity_step_size') }}</div>
                                            <div class="datagrid-content">{{ $product->quantity_step_size }}</div>
                                        </div>
                                    @endif
                                    @if($product->total_allowed_quantity)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.total_allowed_quantity') }}</div>
                                            <div class="datagrid-content">{{ $product->total_allowed_quantity }}</div>
                                        </div>
                                    @endif
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.is_inclusive_tax') }}</div>
                                        <div class="datagrid-content">
                                            <span
                                                class="badge {{ $product->is_inclusive_tax ? 'bg-green-lt' : 'bg-red-lt' }}">
                                                {{ $product->is_inclusive_tax ? __('labels.yes') : __('labels.no') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.is_attachment_required') }}</div>
                                        <div class="datagrid-content">
                                            <span
                                                class="badge {{ $product->is_attachment_required ? 'bg-green-lt' : 'bg-red-lt' }}">
                                                {{ $product->is_attachment_required ? __('labels.yes') : __('labels.no') }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($product->base_prep_time)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.base_prep_time') }}</div>
                                            <div
                                                class="datagrid-content">{{ $product->base_prep_time }} {{ __('labels.minutes') }}</div>
                                        </div>
                                    @endif
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.requires_otp') }}</div>
                                        <div class="datagrid-content">
                                            <span
                                                class="badge {{ $product->requires_otp ? 'bg-green-lt' : 'bg-red-lt' }}">
                                                {{ $product->requires_otp ? __('labels.yes') : __('labels.no') }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($product->video_type && $product->video_link)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.video_type') }}</div>
                                            <div
                                                class="datagrid-content text-capitalize">{{ $product->video_type }}</div>
                                        </div>
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.video_link') }}</div>
                                            <div class="datagrid-content">
                                                <a href="{{ $product->video_link }}" target="_blank"
                                                   class="btn btn-sm btn-outline-primary">
                                                    {{ __('labels.video_link') }}
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                    @if(!empty($product->tags) && count($product->tags) > 0)
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.tags') }}</div>
                                            <div class="datagrid-content">{{ implode(', ', $product->tags) }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Verification Status Card -->
                    @if($updateStatusPermission ?? false)
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Admin Approval</h3>
                                </div>
                                <div class="card-body">
                                    <form class="form-submit" method="POST"
                                          action="{{ route('admin.products.update-verification-status', $product->id) }}">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label">Verification Status</label>
                                            <select class="form-select" name="verification_status"
                                                    id="verification_status">
                                                @foreach(ProductVarificationStatusEnum::values() as $vs)
                                                    <option
                                                        value="{{ $vs }}" {{ $product->verification_status === $vs ? 'selected' : '' }}>{{ Str::title(str_replace('_',' ', $vs)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3" id="rejection-reason-wrapper"
                                             style="display: {{ $product->verification_status === ProductVarificationStatusEnum::REJECTED() ? 'block' : 'none' }};">
                                            <label class="form-label">Rejection Reason</label>
                                            <textarea class="form-control" name="rejection_reason" id="rejection_reason"
                                                      rows="3"
                                                      placeholder="Enter rejection reason">{{ old('rejection_reason', $product->rejection_reason) }}</textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary" id="update-verification-status">
                                            Update
                                            Status
                                        </button>
                                    </form>
                                    <div class="mt-2" id="verification-status-result" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                    @endif
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
                                                <div class="datagrid-title text-capitalize">{{ Str::of($cfKey)->replace('_',' ')->toString() }}</div>
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
                                            <h4>{{ ($product->type === "variant" ? __('labels.variant_name') : __('labels.product_name')) . " : " . $variantData['title'] }}</h4>

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
