@php use App\Enums\FeaturedSection\FeaturedSectionStyleEnum;use App\Enums\FeaturedSection\FeaturedSectionTypeEnum;use App\Enums\HomePageScopeEnum;use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['featured_section']['active'] ?? "", 'sub_page' => $menuAdmin['featured_section']['route']['featured_section']['sub_active'] ?? "" ])

@section('title', __('labels.featured_sections'))
@section('header_data')
    @php
        $page_title =  __('labels.featured_sections');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' =>  __('labels.featured_sections'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('labels.featured_sections') }}</h3>
                        </div>
                        <div class="card-actions">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <select class="form-select" id="typeFilter">
                                        <option value="">{{ __('labels.all_types') }}</option>
                                        @foreach(FeaturedSectionTypeEnum::values() as $type)
                                            <option
                                                value="{{ $type }}">{{ ucfirst(Str::replace("_"," ", $type)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <select class="form-select text-capitalize" id="statusFilter">
                                        <option value="">{{ __('labels.all_status') }}</option>
                                        @foreach(\App\Enums\ActiveInactiveStatusEnum::values() as $value)
                                            <option value="{{$value}}">{{$value}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <select class="form-select" id="scopeTypeFilter">
                                        <option value="">{{ __('labels.all_scopes') }}</option>
                                        @foreach(HomePageScopeEnum::values() as $scopeType)
                                            <option value="{{ $scopeType }}">{{ ucfirst($scopeType) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    @if($createPermission ?? false)
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#featured-section-modal">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                 stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <line x1="12" y1="5" x2="12" y2="19"/>
                                                <line x1="5" y1="12" x2="19" y2="12"/>
                                            </svg> {{ __('labels.add_featured_section') }}
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
                    <div class="card-body">
                        <x-datatable id="featured-table" :columns="$columns"
                                     route="{{ route('admin.featured-sections.datatable') }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(($createPermission ?? false) || ($editPermission ?? false))
        <!-- Edit Featured Section Modal -->
        <div class="modal fade" id="featured-section-modal" tabindex="-1"
             aria-labelledby="FeaturedSectionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"
                            id="FeaturedSectionModalLabel">{{ __('labels.add_featured_section') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form class="form-submit" method="POST" action="{{route('admin.featured-sections.store')}}"
                          enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="edit_featured_section_id" name="featured_section_id">
                        <div class="modal-body">
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="scope_type"
                                               class="form-label required">{{ __('labels.scope_type') }}</label>
                                        <select class="form-select text-capitalize" id="scopeType" name="scope_type">
                                            @foreach(HomePageScopeEnum::values() as $value)
                                                <option value="{{$value}}">{{ Str::replace("_", " ", $value) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" id="scopeCategoryField" style="display: none;">
                                    <div class="mb-3">
                                        <label for="scope_id"
                                               class="form-label">{{ __('labels.scope_category') }}</label>
                                        <select class="form-select" id="select-root-category" name="scope_id">
                                            <option value="">{{ __('labels.select_category') }}</option>
                                            <!-- Categories will be loaded via AJAX -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="title"
                                               class="form-label required">{{ __('labels.title') }}</label>
                                        <input type="text" class="form-control" id="title" name="title"
                                               placeholder="{{ __('labels.enter_featured_section_name') }}"
                                               required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_section_type"
                                               class="form-label required">{{ __('labels.section_type') }} </label>
                                        <select class="form-select text-capitalize" id="section_type"
                                                name="section_type">
                                            <option value="">{{ __('labels.select_section_type') }}</option>
                                            @foreach(FeaturedSectionTypeEnum::values() as $type)
                                                <option
                                                    value="{{$type}}">{{ Str::replace("_", " ", $type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sort_order"
                                               class="form-label">{{ __('labels.sort_order') }}</label>
                                        <input type="number" class="form-control" id="sort_order" name="sort_order"
                                               placeholder="{{ __('labels.enter_sort_order') }}" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="style"
                                               class="form-label required">{{ __('labels.style') }}</label>
                                        <select class="form-select text-capitalize" id="style"
                                                name="style">
                                            <option value="">{{ __('labels.select_section_type') }}</option>
                                            @foreach(FeaturedSectionStyleEnum::values() as $style)
                                                <option
                                                    value="{{$style}}">{{ Str::replace("_", " ", $style) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="categories"
                                               class="form-label">{{ __('labels.categories') }}</label>
                                        <select class="form-select" id="select-category" name="categories[]"
                                                multiple>
                                            <!-- Categories will be loaded via AJAX -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="short_description"
                                               class="form-label">{{ __('labels.short_description') }}</label>
                                        <textarea class="form-control required" id="short_description"
                                                  name="short_description" rows="3"
                                                  placeholder="{{ __('labels.short_description') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="background_type"
                                               class="form-label">{{ __('labels.background_type') }}</label>
                                        <select class="form-select" id="background_type" name="background_type">
                                            <option value="">{{ __('labels.select_background_type') }}</option>
                                            <option value="image">{{ __('labels.image') }}</option>
                                            <option value="color">{{ __('labels.color') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" id="background-color-field" style="display: none;">
                                    <div class="mb-3">
                                        <label for="background_color"
                                               class="form-label">{{ __('labels.background_color') }}</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                               id="background_color"
                                               name="background_color" placeholder="#ffffff">
                                    </div>
                                </div>
                                <div class="col-md-6" id="text-color-field">
                                    <div class="mb-3">
                                        <label for="text_color"
                                               class="form-label">{{ __('labels.text_color') }}</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                               id="text_color"
                                               name="text_color" placeholder="#00000">
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="background-image-field" style="display: none;">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="desktop_4k_background_image"
                                               class="form-label">{{ __('labels.desktop_4k_background_image') }}
                                            (3840x2160)</label>
                                        <x-filepond_image name="desktop_4k_background_image" id="desktop_4k_background_image"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="desktop_fdh_background_image"
                                               class="form-label">{{ __('labels.desktop_fdh_background_image') }}
                                            (1920x1080)</label>
                                        <x-filepond_image name="desktop_fdh_background_image" id="desktop_fdh_background_image"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tablet_background_image"
                                               class="form-label">{{ __('labels.tablet_background_image') }}
                                            (768x1024)</label>
                                        <x-filepond_image name="tablet_background_image" id="tablet_background_image"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mobile_background_image"
                                               class="form-label">{{ __('labels.mobile_background_image') }}
                                            (375x812)</label>
                                        <x-filepond_image name="mobile_background_image" id="mobile_background_image"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="status"
                                               id="status-switch"
                                               value="active" checked>
                                        <label class="form-check-label"
                                               for="status-switch">{{ __('labels.status') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __('labels.add') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{asset('assets/js/featured-section.js')}}" defer></script>
@endpush
