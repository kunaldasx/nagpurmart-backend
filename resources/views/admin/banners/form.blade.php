@extends('layouts.admin.app',['page' => $menuAdmin['banners']['active'] ?? "", 'sub_page' => $menuAdmin['banners']['route']['create']['sub_active'] ?? "" ])

@section('title', isset($banner) ? __('labels.edit_banner') : __('labels.create_banner'))

@section('header_data')
    @php
        $page_title = isset($banner) ? __('labels.edit_banner') : __('labels.create_banner');
        $page_pretitle = __('labels.admin') . " " . __('labels.management');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.banners'), 'url' => route('admin.banners.index')],
        ['title' => isset($banner) ? __('labels.edit_banner') : __('labels.create_banner'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ isset($banner) ? __('labels.edit_banner') : __('labels.create_banner') }}</h2>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('admin.banners.index') }}"
                       class="btn btn-outline-secondary d-none d-sm-inline-block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                             stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                             stroke-linejoin="round">
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
            <form class="form-submit"
                  action="{{ isset($banner) ? route('admin.banners.update', $banner->id) : route('admin.banners.store') }}"
                  method="POST" enctype="multipart/form-data">
                @csrf

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('labels.banner_information') }}</h3>
                    </div>
                    <div class="card-body">
                        <!-- Scope fields -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.scope_type') }}</label>
                                    <select class="form-select" name="scope_type" id="scopeType">
                                        <option value="">{{ __('labels.select_scope_type') }}</option>
                                        @foreach($scopeTypes as $scopeType)
                                            <option value="{{ $scopeType }}"
                                                {{ old('scope_type', $banner->scope_type ?? 'global') == $scopeType ? 'selected' : '' }}>
                                                {{ ucfirst($scopeType) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('scope_type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3" id="scopeCategoryField" style="display: none;">
                                    <label class="form-label">{{ __('labels.scope_category') }}</label>
                                    <select class="form-select" name="scope_id" id="select-root-category">
                                        <option value="">{{ __('labels.select_category') }}</option>
                                        @if(!empty($scopeCategory))
                                            <option value="{{$scopeCategory->id}}" selected>{{$scopeCategory->title}}</option>
                                        @endif
                                    </select>
                                    @error('scope_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.title') }}</label>
                                    <input type="text" class="form-control" name="title"
                                           value="{{ old('title', $banner->title ?? '') }}"
                                           placeholder="{{ __('labels.enter_banner_title') }}">
                                    @error('title')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.type') }}</label>
                                    <select class="form-select" name="type" id="bannerType">
                                        <option value="">{{ __('labels.select_banner_type') }}</option>
                                        @foreach($bannerTypes as $type)
                                            <option value="{{ $type->value }}"
                                                {{ old('type', $banner->type ?? '') == $type->value ? 'selected' : '' }}>
                                                {{ ucfirst($type->value) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic fields based on banner type -->
                        <div id="productField" class="row" style="display: none;">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.product') }}</label>
                                    <select class="form-select" name="product_id" id="select-product">
                                        <option value="">{{ __('labels.select_product') }}</option>
                                        @if(isset($banner) && $banner->product_id)
                                            <option value="{{ $banner->product_id }}" selected>
                                                {{ $banner->product->title ?? '' }}
                                            </option>
                                        @endif
                                    </select>
                                    @error('product_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div id="categoryField" class="row" style="display: none;">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.category') }}</label>
                                    <select class="form-select" name="category_id" id="select-category">
                                        <option value="">{{ __('labels.select_category') }}</option>
                                        @if(isset($banner) && $banner->category_id)
                                            <option value="{{ $banner->category_id }}" selected>
                                                {{ $banner->category->title ?? '' }}
                                            </option>
                                        @endif
                                    </select>
                                    @error('category_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div id="brandField" class="row" style="display: none;">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.brand') }}</label>
                                    <select class="form-select" name="brand_id" id="select-brand">
                                        <option value="">{{ __('labels.select_brand') }}</option>
                                        @if(isset($banner) && $banner->brand_id)
                                            <option value="{{ $banner->brand_id }}" selected>
                                                {{ $banner->brand->title ?? '' }}
                                            </option>
                                        @endif
                                    </select>
                                    @error('brand_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div id="customField" class="row" style="display: none;">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.custom_url') }}</label>
                                    <input type="text" class="form-control" name="custom_url" value="{{ $banner->custom_url ?? '' }}"/>
                                    @error('brand_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.position') }}</label>
                                    <select class="form-select" name="position" id="bannerPosition">
                                        <option value="">{{ __('labels.select_banner_position') }}</option>
                                        @foreach($bannerPositions as $position)
                                            <option value="{{ $position->value }}"
                                                {{ old('type', $banner->position ?? '') == $position->value ? 'selected' : '' }}>
                                                {{ ucfirst($position->value) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.visibility_status') }}</label>
                                    <select class="form-select" name="visibility_status">
                                        <option
                                            value="draft" {{ old('visibility_status', $banner->visibility_status ?? 'draft') == 'draft' ? 'selected' : '' }}>
                                            {{ __('labels.draft') }}
                                        </option>
                                        <option
                                            value="published" {{ old('visibility_status', $banner->visibility_status ?? '') == 'published' ? 'selected' : '' }}>
                                            {{ __('labels.published') }}
                                        </option>
                                    </select>
                                    @error('visibility_status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.display_order') }}</label>
                                    <input type="number" class="form-control" name="display_order" min="0"
                                           value="{{ old('display_order', $banner->display_order ?? 0) }}"
                                           placeholder="{{ __('labels.enter_display_order') }}">
                                    @error('display_order')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.banner_image') }}</label>
                            <input type="file" class="filepond" name="banner_image" accept="image/*"
                                   data-max-files="1" data-image-url="{{ $banner->banner_image ?? "" }}">
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <a href="{{ route('admin.banners.index') }}"
                               class="btn btn-link">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary ms-auto">
                                {{ isset($banner) ? __('labels.update_banner') : __('labels.create_banner') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{hyperAsset('assets/js/banner.js')}}" defer></script>
@endpush
