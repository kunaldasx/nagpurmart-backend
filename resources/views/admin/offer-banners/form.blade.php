@extends('layouts.admin.app',['page' => $menuAdmin['banners']['active'] ?? '', 'sub_page' => $menuAdmin['banners']['route']['index']['sub_active'] ?? ''])

@section('title', isset($banner) ? __('labels.edit_offer_banner') : __('labels.create_offer_banner'))

@section('header_data')
    @php
        $page_title = isset($banner) ? __('labels.edit_offer_banner') : __('labels.create_offer_banner');
        $page_pretitle = __('labels.admin') . ' ' . __('labels.management');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.offer_banners'), 'url' => route('admin.offer-banners.index')],
        ['title' => isset($banner) ? __('labels.edit_offer_banner') : __('labels.create_offer_banner'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ isset($banner) ? __('labels.edit_offer_banner') : __('labels.create_offer_banner') }}</h2>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('admin.offer-banners.index') }}" class="btn btn-outline-secondary d-none d-sm-inline-block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <polyline points="9,14 4,9 9,4"/>
                            <path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                        </svg>
                        {{ __('labels.back_to_list') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="row row-deck row-cards">
            <form class="form-submit" action="{{ isset($banner) ? route('admin.offer-banners.update', $banner->id) : route('admin.offer-banners.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('labels.offer_banner_information') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.title') }}</label>
                                    <input type="text" class="form-control" name="title" value="{{ old('title', $banner->title ?? '') }}" placeholder="{{ __('labels.enter_banner_title') }}">
                                    @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.position') }}</label>
                                    <select class="form-select" name="position">
                                        <option value="">{{ __('labels.select_banner_position') }}</option>
                                        @foreach($bannerPositions as $position)
                                            <option value="{{ $position->value }}" {{ old('position', $banner->position ?? '') == $position->value ? 'selected' : '' }}>{{ ucfirst($position->value) }}</option>
                                        @endforeach
                                    </select>
                                    @error('position')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.scope_type') }}</label>
                                    <select class="form-select" name="scope_type" id="scopeType">
                                        <option value="">{{ __('labels.select_scope_type') }}</option>
                                        @foreach($scopeTypes as $scopeType)
                                            <option value="{{ $scopeType }}" {{ old('scope_type', $banner->scope_type ?? 'global') == $scopeType ? 'selected' : '' }}>{{ ucfirst($scopeType) }}</option>
                                        @endforeach
                                    </select>
                                    @error('scope_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6" id="scopeCategoryField" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.scope_category') }}</label>
                                    <select class="form-select" name="scope_id" id="select-root-category">
                                        <option value="">{{ __('labels.select_category') }}</option>
                                        @if(isset($banner) && $banner->scope_id)
                                            <option value="{{ $banner->scope_id }}" selected>{{ $banner->scopeCategory->title ?? '' }}</option>
                                        @endif
                                    </select>
                                    @error('scope_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.offer_items') }}</label>
                                    <div id="offerItemsContainer">
                                        @php
                                            $existingItems = old('metadata.images', $banner->metadata['images'] ?? []);
                                        @endphp
                                        @foreach($existingItems as $index => $item)
                                            <div class="offer-item-row row g-2 mb-3" data-index="{{ $index }}">
                                                <div class="col-md-2">
                                                    <input type="text" class="form-control" name="metadata[images][{{ $index }}][title]" placeholder="{{ __('labels.offer_title') }}" value="{{ $item['title'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <select class="form-select" name="metadata[images][{{ $index }}][type]" data-offer-type>
                                                        <option value="product" {{ ($item['type'] ?? '') === 'product' ? 'selected' : '' }}>{{ __('labels.product') }}</option>
                                                        <option value="category" {{ ($item['type'] ?? '') === 'category' ? 'selected' : '' }}>{{ __('labels.category') }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3" data-product-field style="display: {{ ($item['type'] ?? '') === 'product' ? 'block' : 'none' }};">
                                                    <select class="form-select" name="metadata[images][{{ $index }}][product_id]" data-select-product>
                                                        <option value="">{{ __('labels.select_product') }}</option>
                                                        @if(!empty($item['product_id']))
                                                            <option value="{{ $item['product_id'] }}" selected>{{ $item['product_title'] ?? '' }}</option>
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-md-3" data-category-field style="display: {{ ($item['type'] ?? '') === 'category' ? 'block' : 'none' }};">
                                                    <select class="form-select" name="metadata[images][{{ $index }}][category_id]" data-select-category>
                                                        <option value="">{{ __('labels.select_category') }}</option>
                                                        @if(!empty($item['category_id']))
                                                            <option value="{{ $item['category_id'] }}" selected>{{ $item['category_title'] ?? '' }}</option>
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-md-3" data-custom-url-field style="display: none;">
                                                    <input type="text" class="form-control" name="metadata[images][{{ $index }}][custom_url]" placeholder="{{ __('labels.custom_url') }}" value="{{ $item['custom_url'] ?? '' }}">
                                                </div>
                                                <div class="col-md-1 d-flex align-items-center">
                                                    <button type="button" class="btn btn-danger remove-offer-item">&times;</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-outline-primary" id="addOfferItem">{{ __('labels.add_offer_item') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.visibility_status') }}</label>
                                    <select class="form-select" name="visibility_status">
                                        <option value="draft" {{ old('visibility_status', $banner->visibility_status ?? 'draft') == 'draft' ? 'selected' : '' }}>{{ __('labels.draft') }}</option>
                                        <option value="published" {{ old('visibility_status', $banner->visibility_status ?? '') == 'published' ? 'selected' : '' }}>{{ __('labels.published') }}</option>
                                    </select>
                                    @error('visibility_status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.display_order') }}</label>
                                    <input type="number" class="form-control" name="display_order" value="{{ old('display_order', $banner->display_order ?? 0) }}" min="0" placeholder="{{ __('labels.enter_display_order') }}">
                                    @error('display_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.banner_images') }}</label>
                            <input type="file" class="filepond" name="banner_images[]" accept="image/*" multiple data-images='{{ json_encode($banner->banner_images ?? []) }}'>
                            <input type="hidden" name="deleted_banner_images" id="deletedBannerImages" value="{{ old('deleted_banner_images') ? json_encode(old('deleted_banner_images')) : '[]' }}">
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <a href="{{ route('admin.offer-banners.index') }}" class="btn btn-link">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary ms-auto">{{ isset($banner) ? __('labels.update_offer_banner') : __('labels.create_offer_banner') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ hyperAsset('assets/js/offer-banner.js') }}" defer></script>
@endpush
