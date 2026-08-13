@extends('layouts.admin.app')

@section('title', isset($banner) ? __('labels.edit_offer_banner') : __('labels.create_offer_banner'))

@section('admin-content')
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
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.position') }}</label>
                                    <select class="form-select" name="position">
                                        <option value="top" {{ (old('position', $banner->position ?? '')=='top')?'selected':'' }}>Top</option>
                                        <option value="carousel" {{ (old('position', $banner->position ?? '')=='carousel')?'selected':'' }}>Carousel</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.scope_type') }}</label>
                                    <select class="form-select" name="scope_type" id="scopeType">
                                        <option value="global" {{ (old('scope_type', $banner->scope_type ?? 'global')=='global')?'selected':'' }}>Global</option>
                                        <option value="category" {{ (old('scope_type', $banner->scope_type ?? '')=='category')?'selected':'' }}>Category</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="scopeCategoryField" style="display: {{ (old('scope_type', $banner->scope_type ?? '')=='category')?'block':'none' }};">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.scope_category') }}</label>
                                    <select class="form-select" name="scope_id" id="select-root-category">
                                        <option value="">{{ __('labels.select_category') }}</option>
                                        @if(!empty($scopeCategory))
                                            <option value="{{$scopeCategory->id}}" selected>{{$scopeCategory->title}}</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Offer Items</label>
                            <div id="offer-items">
                                @if(isset($banner) && $banner->items)
                                    @foreach($banner->items as $item)
                                        <div class="row offer-item-row mb-2">
                                            <div class="col-md-4">
                                                <input type="text" name="offer_items[][title]" class="form-control" value="{{ $item->title }}" placeholder="Offer Title">
                                            </div>
                                            <div class="col-md-3">
                                                <select name="offer_items[][item_type]" class="form-select item-type-select">
                                                    <option value="product" {{ $item->item_type=='product'?'selected':'' }}>Product</option>
                                                    <option value="category" {{ $item->item_type=='category'?'selected':'' }}>Category</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <select name="offer_items[][item_id]" class="form-select tom-select-ajax" data-type="{{ $item->item_type }}">
                                                    @if($item->item_id)
                                                        <option value="{{ $item->item_id }}" selected>{{ $item->product->title ?? $item->category->title ?? '' }}</option>
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-md-1"><button type="button" class="btn btn-danger remove-offer-item">x</button></div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" id="add-offer-item" class="btn btn-outline-primary mt-2">{{ __('labels.add_offer_item') }}</button>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.visibility_status') }}</label>
                                    <select class="form-select" name="visibility_status">
                                        <option value="draft" {{ (old('visibility_status', $banner->visibility_status ?? 'draft')=='draft')?'selected':'' }}>{{ __('labels.draft') }}</option>
                                        <option value="published" {{ (old('visibility_status', $banner->visibility_status ?? '')=='published')?'selected':'' }}>{{ __('labels.published') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.display_order') }}</label>
                                    <input type="number" class="form-control" name="display_order" value="{{ old('display_order', $banner->display_order ?? 0) }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.banner_images') }}</label>
                            <input type="file" name="images[]" multiple class="form-control filepond" accept="image/*" data-max-files="5" data-images='@json($banner->images ?? [])'>
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
    <script src="{{hyperAsset('assets/js/offer-banner.js')}}" defer></script>
@endpush
