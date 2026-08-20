@extends('layouts.admin.app')

@section('title', isset($banner) ? __('labels.edit_offer_banner') : __('labels.create_offer_banner'))

@section('admin-content')
    <div class="page-body">
        <div class="row row-deck row-cards">
            <form
                class="form-submit"
                action="{{ isset($banner)
                    ? route('admin.offer-banners.update', $banner->id)
                    : route('admin.offer-banners.store') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title mb-1">
                                {{ isset($banner)
                                    ? __('labels.edit_offer_banner')
                                    : __('labels.create_offer_banner') }}
                            </h3>

                            <div class="text-secondary">
                                Configure where the offer banner appears and add the products or categories to display.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- Basic Information --}}
                        <div class="mb-4">
                            <h4 class="mb-1">Basic Information</h4>
                            <p class="text-secondary mb-0">
                                Set the main details and placement for this offer banner.
                            </p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div>
                                    <label class="form-label required">
                                        {{ __('labels.title') }}
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        name="title"
                                        value="{{ old('title', $banner->title ?? '') }}"
                                        placeholder="{{ __('labels.enter_banner_title') }}"
                                    >
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div>
                                    <label class="form-label required">
                                        {{ __('labels.position') }}
                                    </label>

                                    <select class="form-select" name="position">
                                        <option
                                            value="top"
                                            {{ old('position', $banner->position ?? '') === 'top' ? 'selected' : '' }}
                                        >
                                            Top
                                        </option>

                                        <option
                                            value="carousel"
                                            {{ old('position', $banner->position ?? '') === 'carousel' ? 'selected' : '' }}
                                        >
                                            Carousel
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Display Scope --}}
                        <div class="mt-5 pt-4 border-top">
                            <div class="mb-4">
                                <h4 class="mb-1">Display Scope</h4>
                                <p class="text-secondary mb-0">
                                    Choose whether this banner should be shown globally or only for a specific category.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div>
                                        <label class="form-label required">
                                            {{ __('labels.scope_type') }}
                                        </label>

                                        <select class="form-select" name="scope_type" id="scopeType">
                                            <option
                                                value="global"
                                                {{ old('scope_type', $banner->scope_type ?? 'global') === 'global' ? 'selected' : '' }}
                                            >
                                                Global
                                            </option>

                                            <option
                                                value="category"
                                                {{ old('scope_type', $banner->scope_type ?? '') === 'category' ? 'selected' : '' }}
                                            >
                                                Category
                                            </option>
                                        </select>

                                        <div class="form-hint">
                                            Global banners appear everywhere. Category banners appear only within the selected category.
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="col-md-6"
                                    id="scopeCategoryField"
                                    style="display: {{ old('scope_type', $banner->scope_type ?? '') === 'category' ? 'block' : 'none' }};"
                                >
                                    <div>
                                        <label class="form-label">
                                            {{ __('labels.scope_category') }}
                                        </label>

                                        <select
                                            class="form-select"
                                            name="scope_id"
                                            id="select-root-category"
                                        >
                                            <option value="">
                                                {{ __('labels.select_category') }}
                                            </option>

                                            @if(!empty($scopeCategory))
                                                <option value="{{ $scopeCategory->id }}" selected>
                                                    {{ $scopeCategory->title }}
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Offer Items --}}
                        <div class="mt-5 pt-4 border-top">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                                <div>
                                    <h4 class="mb-1">Offer Items</h4>
                                    <p class="text-secondary mb-0">
                                        Add the products or categories that should appear in this offer banner.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    id="add-offer-item"
                                    class="btn btn-outline-primary"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        fill="none"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 5l0 14"/>
                                        <path d="M5 12l14 0"/>
                                    </svg>

                                    {{ __('labels.add_offer_item') }}
                                </button>
                            </div>

                            <div id="offer-items" class="d-flex flex-column gap-3">
                                @if(isset($banner) && $banner->items && $banner->items->count())
                                    @foreach($banner->items as $item)
                                        <div class="offer-item-row border rounded-3 p-3">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-3">
                                                    <label class="form-label">Offer Title</label>

                                                    <input
                                                        type="text"
                                                        name="offer_items[][title]"
                                                        class="form-control"
                                                        value="{{ $item->title }}"
                                                        placeholder="e.g. 60% OFF"
                                                    >
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="form-label">Offer Subtitle</label>

                                                    <input
                                                        type="text"
                                                        name="offer_items[][subtitle]"
                                                        class="form-control offer-item-subtitle"
                                                        value="{{ $item->subtitle }}"
                                                        placeholder="Optional subtitle"
                                                    >
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label">Item Type</label>

                                                    <select
                                                        name="offer_items[][item_type]"
                                                        class="form-select item-type-select"
                                                    >
                                                        <option
                                                            value="product"
                                                            {{ $item->item_type === 'product' ? 'selected' : '' }}
                                                        >
                                                            Product
                                                        </option>

                                                        <option
                                                            value="category"
                                                            {{ $item->item_type === 'category' ? 'selected' : '' }}
                                                        >
                                                            Category
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="form-label">Select Item</label>

                                                    <select
                                                        name="offer_items[][item_id]"
                                                        class="form-select tom-select-ajax"
                                                        data-type="{{ $item->item_type }}"
                                                    >
                                                        @if($item->item_id)
                                                            <option value="{{ $item->item_id }}" selected>
                                                                {{ $item->product->title ?? $item->category->title ?? '' }}
                                                            </option>
                                                        @endif
                                                    </select>
                                                </div>

                                                <div class="col-md-1">
                                                    <button
                                                        type="button"
                                                        class="btn btn-danger w-100 remove-offer-item"
                                                        title="Remove item"
                                                        aria-label="Remove item"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="icon"
                                                            width="24"
                                                            height="24"
                                                            viewBox="0 0 24 24"
                                                            stroke-width="2"
                                                            stroke="currentColor"
                                                            fill="none"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                        >
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M18 6l-12 12"/>
                                                            <path d="M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div
                                        id="offer-items-empty"
                                        class="border rounded-3 p-4 text-center text-secondary"
                                    >
                                        No offer items added yet. Click
                                        <strong>Add Offer Item</strong>
                                        to get started.
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Banner Settings --}}
                        <div class="mt-5 pt-4 border-top">
                            <div class="mb-4">
                                <h4 class="mb-1">Banner Settings</h4>
                                <p class="text-secondary mb-0">
                                    Control the visibility and display priority of this banner.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div>
                                        <label class="form-label required">
                                            {{ __('labels.visibility_status') }}
                                        </label>

                                        <select class="form-select" name="visibility_status">
                                            <option
                                                value="draft"
                                                {{ old('visibility_status', $banner->visibility_status ?? 'draft') === 'draft' ? 'selected' : '' }}
                                            >
                                                {{ __('labels.draft') }}
                                            </option>

                                            <option
                                                value="published"
                                                {{ old('visibility_status', $banner->visibility_status ?? '') === 'published' ? 'selected' : '' }}
                                            >
                                                {{ __('labels.published') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div>
                                        <label class="form-label">
                                            {{ __('labels.display_order') }}
                                        </label>

                                        <input
                                            type="number"
                                            class="form-control"
                                            name="display_order"
                                            min="0"
                                            value="{{ old('display_order', $banner->display_order ?? 0) }}"
                                        >

                                        <div class="form-hint">
                                            Lower numbers are displayed first.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Images --}}
                        <div class="mt-5 pt-4 border-top">
                            <div class="mb-4">
                                <h4 class="mb-1">{{ __('labels.banner_images') }}</h4>
                                <p class="text-secondary mb-0">
                                    Upload up to 5 images for this offer banner.
                                </p>
                            </div>

                            <input
                                type="file"
                                name="images[]"
                                multiple
                                class="form-control filepond"
                                accept="image/*"
                                data-max-files="5"
                                data-images='@json($banner->images ?? [])'
                            >
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex align-items-center gap-3">
                            <a
                                href="{{ route('admin.offer-banners.index') }}"
                                class="btn btn-link"
                            >
                                {{ __('labels.cancel') }}
                            </a>

                            <button type="submit" class="btn btn-primary ms-auto">
                                {{ isset($banner)
                                    ? __('labels.update_offer_banner')
                                    : __('labels.create_offer_banner') }}
                            </button>
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