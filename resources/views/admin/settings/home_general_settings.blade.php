@php use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['home_general_settings']['sub_active'] ?? "" ])

@section('title', __('labels.home_general_settings'))

@section('header_data')
    @php
        $page_title = __('labels.home_general_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.home_general_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.home_general_settings') }}</h2>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
        </div>
    </div>
    <!-- BEGIN PAGE BODY -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-5">
                <div class="col-sm-2 d-none d-lg-block">
                    <div class="sticky-top">
                        <h3>{{ __('labels.menu') }}</h3>
                        <nav class="nav nav-vertical nav-pills" id="pills">
                            <a class="nav-link" href="#pills-general">{{ __('labels.general') }}</a>
                            <a class="nav-link" href="#pills-appearance">{{ __('labels.appearance') }}</a>
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{route('admin.settings.store')}}" class="form-submit" method="post"
                                  enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="type" value="home_general_settings">

                                <div class="card mb-4" id="pills-general">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.general') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.category_title') }}</label>
                                            <input type="text" class="form-control" name="title"
                                                   placeholder="{{ __('labels.enter_category_title') }}"
                                                   value="{{ $settings['title'] ?? 'All Categories' }}"/>
                                            <small
                                                class="form-text text-muted">{{ __('labels.category_title_help') }}</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.search_labels') }}</label>
                                            <div class="input-group mb-2">
                                                <select class="form-select search-labels" name="searchLabels[]"
                                                        multiple>
                                                    <option value="">{{ __('labels.eg_search_labels') }}</option>
                                                    @if(!empty($settings['searchLabels']))
                                                        @foreach($settings['searchLabels'] as $label)
                                                            <option value="{{ $label }}"
                                                                    selected>{{ Str::replace("_", " ",$label) }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <button class="btn generate-search-labels-button" type="button"
                                                        title="generate search labels">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                         height="24"
                                                         viewBox="0 0 24 24" fill="none"
                                                         stroke="currentColor"
                                                         stroke-width="2" stroke-linecap="round"
                                                         stroke-linejoin="round"
                                                         class="icon icon-tabler icons-tabler-outline icon-tabler-sparkles m-0">
                                                        <path stroke="none" d="M0 0h24v24H0z"
                                                              fill="none"/>
                                                        <path
                                                            d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <small class="form-hint">{{ __('labels.search_labels_help') }}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4" id="pills-appearance">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.appearance') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.background_type') }}</label>
                                            <select class="form-select" name="backgroundType"
                                                    id="background-type-select">
                                                <option value="">{{ __('labels.select_background_type') }}</option>
                                                <option
                                                    value="color" {{ isset($settings['backgroundType']) && $settings['backgroundType'] === 'color' ? 'selected' : '' }}>{{ __('labels.color') }}</option>
                                                <option
                                                    value="image" {{ isset($settings['backgroundType']) && $settings['backgroundType'] === 'image' ? 'selected' : '' }}>{{ __('labels.image') }}</option>
                                            </select>
                                            <small
                                                class="form-text text-muted">{{ __('labels.background_type_help') }}</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.icon') }}</label>
                                            <input type="file" class="form-control" id="icon-upload" name="icon"
                                                   data-image-url="{{$settings['icon'] ?? ''}}"/>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.active_icon') }}</label>
                                            <input type="file" class="form-control" id="active-icon-upload"
                                                   name="activeIcon"
                                                   data-image-url="{{$settings['activeIcon'] ?? ''}}"/>
                                        </div>
                                        <div class="mb-3" id="background-color-field"
                                             style="{{ isset($settings['backgroundType']) && $settings['backgroundType'] === 'color' ? 'display: block;' : 'display: none;' }}">
                                            <label class="form-label">{{ __('labels.background_color') }}</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                   name="backgroundColor" id="background-color-input"
                                                   value="{{ $settings['backgroundColor'] ?? '#ffffff' }}"/>
                                            <small
                                                class="form-text text-muted">{{ __('labels.background_color_help') }}</small>
                                        </div>
                                        <div class="mb-3" id="background-image-field"
                                             style="{{ isset($settings['backgroundType']) && $settings['backgroundType'] === 'image' ? 'display: block;' : 'display: none;' }}">
                                            <label class="form-label">{{ __('labels.background_image') }}</label>
                                            <input type="file" class="form-control" id="background-image-upload"
                                                   name="backgroundImage"
                                                   data-image-url="{{$settings['backgroundImage'] ?? ''}}"/>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.font_color') }}</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                   name="fontColor" id="font-color-input"
                                                   value="{{ $settings['fontColor'] ?? '#000000' }}"/>
                                            <small
                                                class="form-text text-muted">{{ __('labels.font_color_help') }}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer text-end">
                                    @can('updateSetting', [\App\Models\Setting::class, 'home_general_settings'])
                                        <button type="submit" class="btn btn-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-device-floppy">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path
                                                    d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"/>
                                                <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                                                <path d="M14 4l0 4l-6 0l0 -4"/>
                                            </svg>
                                            {{ __('labels.save') }}
                                        </button>
                                    @endcan
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
