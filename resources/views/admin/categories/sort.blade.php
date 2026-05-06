@extends('layouts.admin.app', ['page' => $menuAdmin['categories']['active'] ?? "", 'sub_page' => $menuAdmin['categories']['route']['sort']['sub_active'] ?? "" ])

@section('title', __('labels.sort_categories'))
@section('header_data')
    @php
        $page_title =  __('labels.sort_categories');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
            ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
            ['title' => __('labels.categories'), 'url' => route('admin.categories.index')],
            ['title' => __('labels.sort_categories'), 'url' => null],
        ];
@endphp
@section('admin-content')
    <div class="page-body">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('labels.sort_categories') . " (" . ($parentCategories->count()) . ")" }}</h3>
                            <x-breadcrumb :items="$breadcrumbs"/>
                        </div>
                        <div class="card-actions">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                                {{ __('labels.back') ?? 'Back' }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <form class="row g-3 form-submit"
                                      action="{{ route('admin.categories.home-categories.update') }}" method="post">
                                    @csrf
                                    <div class="w-100">
                                        <label for="home-parent-categories"
                                               class="form-label">{{ __('labels.home_parent_categories') }}</label>
                                        <select id="select-root-category" name="home_category_ids[]" class="form-select"
                                                multiple placeholder="{{ __('labels.select_parent_categories') }}"
                                                autocomplete="off">
                                            @foreach($parentCategories as $category)
                                                <option value="{{ $category->id }}"
                                                        selected>{{ $category->title }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-hint">{{ __('labels.choose_home_categories_hint') }}</div>
                                    </div>
                                    <div class="pb-1 text-end">
                                        <button type="submit" class="btn btn-primary text-end">
                                            {{ __('labels.save') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="sort-info">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-info-circle"></i> {{ __('labels.sorting_instructions') }}
                            </h6>
                            <p class="mb-0">
                                {{ __('labels.drag_drop_instruction') }}
                            </p>
                        </div>
                        @if($parentCategories->isEmpty())
                            <div class="text-center py-5">
                                <i class="fas fa-inbox text-gray-300" style="font-size: 3rem;"></i>
                                <h5 class="text-gray-500 mt-3">{{ __('labels.no_categories_found') }}</h5>
                                <p class="text-gray-400">{{ __('labels.create_category_first') }}</p>
                                <a href="{{ route('admin.categories.index') }}" class="btn btn-primary">
                                    {{ __('labels.add_category') }}
                                </a>
                            </div>
                        @else
                            <div class="section-group mb-4">
                                <div class="mt-3">
                                    <div id="categories-sortable-list" class="sortable-container"
                                         data-group="categories">
                                        @foreach($parentCategories as $category)
                                            <div class="sortable-item" data-id="{{ $category->id }}">
                                                <div class="section-info">
                                                    <div class="d-flex align-items-center flex-grow-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                             stroke-width="2" stroke-linecap="round"
                                                             stroke-linejoin="round"
                                                             class="icon icon-tabler icons-tabler-outline icon-tabler-grip-vertical">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M9 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                                            <path d="M9 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                                            <path d="M9 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                                            <path d="M15 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                                            <path d="M15 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                                            <path d="M15 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                                        </svg>
                                                        <div class="section-details">
                                                            <h5><span
                                                                    class="section-order">{{ $category->sort_order }}</span> {{ $category->title }}
                                                            </h5>
                                                        </div>
                                                    </div>
                                                    <div class="section-meta">
                                                        <div class="mb-2">
                                                            <span
                                                                class="badge {{ $category->status === 'active' ? 'bg-primary-lt' : 'bg-danger-lt' }} ms-2">
                                                                {{ $category->status === 'active' ? __('labels.active') : __('labels.inactive') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer text-end">
                        <button type="button" class="btn btn-outline-secondary"
                                id="reset-order">{{ __('labels.reset_order') }}</button>
                        <button type="submit" class="btn btn-primary" id="save-category-order">
                            <i class="fas fa-save"></i> {{ __('labels.save_order') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/sortablejs/sortable.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/sortablejs/jquery-sortable.js') }}"></script>
    <script src="{{ asset('assets/js/category-sort.js') }}"></script>
@endpush
