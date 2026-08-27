@extends('layouts.admin.app', ['page' => 'popular_searches', 'sub_page' => ''])

@section('title', __('labels.popular_searches'))
@section('header_data')
    @php($page_title = __('labels.popular_searches'))
@endsection

@section('admin-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">{{ __('labels.popular_searches') }}</h3>
                    <div>
                        <a href="{{ route('admin.popular-searches.sort') }}" class="btn btn-outline-primary me-2">
                            <i class="fas fa-sort"></i> {{ __('labels.sort_popular_searches') }}
                        </a>
                        @if($editPermission)
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#popular-search-modal">
                                <i class="fas fa-plus"></i> {{ __('labels.add_popular_search') }}
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <x-datatable id="popular-searches-table" :columns="[
                        ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
                        ['data' => 'category', 'name' => 'category', 'title' => __('labels.category')],
                        ['data' => 'parent', 'name' => 'parent', 'title' => __('labels.parent')],
                        ['data' => 'sort_order', 'name' => 'sort_order', 'title' => __('labels.sort_order')],
                        ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
                        ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
                    ]" route="{{ route('admin.popular-searches.datatable') }}" :options="['order' => [[3, 'asc']], 'pageLength' => 10]" />
                </div>
            </div>
        </div>
    </div>

    @if($editPermission)
        <div class="modal fade" id="popular-search-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('labels.add_popular_search') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="popular-search-form" method="POST" action="{{ route('admin.popular-searches.store') }}">
                        @csrf
                        <input type="hidden" name="popular_search_id" id="popular-search-id">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required" for="popular-search-category">{{ __('labels.category_or_subcategory') }}</label>
                                <select class="form-select" id="popular-search-category" name="category_id" required>
                                    <option value="">{{ __('labels.search_category') }}</option>
                                </select>
                                <small class="form-hint">{{ __('labels.select_category_or_subcategory_hint') }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="popular-search-sort-order">{{ __('labels.sort_order') }}</label>
                                <input class="form-control" type="number" min="0" id="popular-search-sort-order" name="sort_order" value="0">
                            </div>
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" value="active" checked>
                                <span class="form-check-label">{{ __('labels.active') }}</span>
                            </label>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('labels.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/popular-searches.js') }}" defer></script>
@endpush