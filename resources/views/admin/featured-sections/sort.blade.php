@php use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['featured_section']['active'] ?? "", 'sub_page' => $menuAdmin['featured_section']['route']['sort_featured_section']['sub_active'] ?? "" ])

@section('title', __('labels.sort_featured_sections'))
@section('header_data')
    @php
        $page_title =  __('labels.sort_featured_sections');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' =>  __('labels.sort_featured_sections'), 'url' => '']
    ];
@endphp
@section('admin-content')
    <div class="page-body">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">{{ __('labels.sort_featured_sections') . " (" . ($globalSections->count() + $categorySections->flatten()->count()) . ")" }}</h3>
                        <div>
                            <a href="{{ route('admin.featured-sections.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> {{ __('labels.back_to_list') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Instructions -->
                        <div class="sort-info">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-info-circle"></i> {{ __('labels.sorting_instructions') }}
                            </h6>
                            <p class="mb-0">
                                {{ __('labels.drag_drop_instruction') }}
                            </p>
                        </div>
                        @if($globalSections->isEmpty() && $categorySections->isEmpty())
                            <div class="text-center py-5">
                                <i class="fas fa-inbox text-gray-300" style="font-size: 3rem;"></i>
                                <h5 class="text-gray-500 mt-3">{{ __('labels.no_featured_sections_found') }}</h5>
                                <p class="text-gray-400">{{ __('labels.create_featured_sections_first') }}</p>
                                <a href="{{ route('admin.featured-sections.index') }}" class="btn btn-primary">
                                    {{ __('labels.create_featured_section') }}
                                </a>
                            </div>
                        @else
                            <!-- Global Sections -->
                            @if($globalSections->isNotEmpty())
                                <div class="section-group mb-4">
                                    <div
                                        class="section-group-header d-flex justify-content-between align-items-center cursor-pointer"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#global-sections-collapse"
                                        aria-expanded="false"
                                        aria-controls="global-sections-collapse">
                                        <h4 class="text-primary mb-0">
                                            <i class="fas fa-globe"></i> {{ __('labels.global_sections') }}
                                            ({{ $globalSections->count() }})
                                        </h4>
                                        <button class="btn btn-sm btn-outline-primary border-0" type="button">
                                            <i class="fas fa-chevron-up collapse-icon"></i>
                                        </button>
                                    </div>
                                    <div class="collapse mt-3" id="global-sections-collapse">
                                        <div id="global-sortable-list" class="sortable-container" data-group="global">
                                            @foreach($globalSections as $section)
                                                <div class="sortable-item" data-id="{{ $section->id }}">
                                                    <div class="section-info">
                                                        <div class="d-flex align-items-center flex-grow-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                                 height="24"
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
                                                                        class="section-order">{{ $section->sort_order }}</span> {{ $section->title }}
                                                                </h5>
                                                            </div>
                                                        </div>
                                                        <div class="section-meta">
                                                            <div class="mb-2">
                                                                <span
                                                                    class="badge badge-lg bg-info-lt text-capitalize">{{ Str::replace("_", " ",$section->section_type) }}</span>
                                                                <span
                                                                    class="badge badge-lg bg-success-lt">{{ __('labels.global') }}</span>
                                                                <span
                                                                    class="badge {{ $section->status === 'active' ? 'bg-primary-lt' : 'bg-danger-lt' }} ms-2">
                                                                {{ $section->status === 'active' ? __('labels.active') : __('labels.inactive') }}
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

                            <!-- Category Sections -->
                            @if($categorySections->isNotEmpty())
                                @foreach($categorySections as $categoryId => $sections)
                                    <div class="section-group mb-4">
                                        <div
                                            class="section-group-header d-flex justify-content-between align-items-center cursor-pointer"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#category-{{ $categoryId }}-sections-collapse"
                                            aria-expanded="false"
                                            aria-controls="category-{{ $categoryId }}-sections-collapse">
                                            <h4 class="text-primary mb-0">
                                                <i class="fas fa-tags"></i> {{ $sections->first()->scopeCategory->title ?? __('labels.unknown_category') }}
                                                ({{ $sections->count() }})
                                            </h4>
                                            <button class="btn btn-sm btn-outline-info border-0" type="button">
                                                <i class="fas fa-chevron-up collapse-icon"></i>
                                            </button>
                                        </div>
                                        <div class="collapse mt-3" id="category-{{ $categoryId }}-sections-collapse">
                                            <div id="category-{{ $categoryId }}-sortable-list"
                                                 class="sortable-container" data-group="category"
                                                 data-category-id="{{ $categoryId }}">
                                                @foreach($sections as $section)
                                                    <div class="sortable-item" data-id="{{ $section->id }}">
                                                        <div class="section-info">
                                                            <div class="d-flex align-items-center flex-grow-1">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                                     height="24"
                                                                     viewBox="0 0 24 24" fill="none"
                                                                     stroke="currentColor"
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
                                                                            class="section-order">{{ $section->sort_order }}</span> {{ $section->title }}
                                                                    </h5>
                                                                </div>
                                                            </div>
                                                            <div class="section-meta">
                                                                <div class="mb-2">
                                                                    <span
                                                                        class="badge badge-lg bg-info-lt">{{ $section->section_type }}</span>
                                                                    <span
                                                                        class="badge badge-lg bg-warning-lt">{{ $section->scopeCategory->name ?? __('labels.category') }}</span>
                                                                    <span
                                                                        class="badge {{ $section->status === 'active' ? 'bg-primary-lt' : 'bg-danger-lt' }} ms-2">
                                                                    {{ $section->status === 'active' ? __('labels.active') : __('labels.inactive') }}
                                                                </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        @endif
                    </div>
                    <div class="card-footer text-end">
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">{{ __('labels.reset_order') }}</button>
                        <button type="submit" class="btn btn-primary" id="get-section-order">
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
    <script src="{{ asset('assets/js/featured-section.js') }}"></script>
@endpush
