@php use App\Models\Setting; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['categories']['active'] ?? "", 'sub_page' => $menuAdmin['categories']['route']['categories']['sub_active']])

@section('title', __('labels.categories'))

@section('header_data')
    @php
        $page_title = __('labels.categories');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.categories'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.categories') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                @if($createPermission ?? false)
                                    <div class="col text-end">
                                        <a href="#" class="btn btn-6 btn-outline-primary" data-bs-toggle="modal"
                                           data-bs-target="#category-modal">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="24"
                                                height="24"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="icon icon-2"
                                            >
                                                <path d="M12 5l0 14"/>
                                                <path d="M5 12l14 0"/>
                                            </svg>
                                            {{ __('labels.add_category') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                            @if($createPermission ?? false)
                                <div class="col-auto">
                                    <a href="{{ route('admin.categories.bulk-upload.page') }}"
                                       class="btn btn-outline-success">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                             stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                                            <path d="M7 9l5 -5l5 5"/>
                                            <path d="M12 4l0 12"/>
                                        </svg>
                                        {{ __('labels.bulk_upload') }}
                                    </a>
                                </div>
                            @endif
                            <div class="col-auto">
                                <a href="{{ route('admin.categories.sort') }}" class="btn btn-outline-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round"
                                         class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-sort">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 6l7 0"/>
                                        <path d="M4 12l7 0"/>
                                        <path d="M4 18l9 0"/>
                                        <path d="M15 9l3 -3l3 3"/>
                                        <path d="M18 6l0 12"/>
                                    </svg>
                                    {{ __('labels.sort') }}
                                </a>
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
                <div class="card-table">
                    <div class="row w-full p-3">
                        <div class="alert alert-info alert-dismissible" role="alert">
                            <div class="alert-icon">
                                <!-- Download SVG icon from http://tabler.io/icons/icon/info-circle -->
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="icon alert-icon icon-2"
                                >
                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/>
                                    <path d="M12 9h.01"/>
                                    <path d="M11 12h1v4h1"/>
                                </svg>
                            </div>
                            {{ __('labels.global_scope_config_note') }}
                            <a href="{{route('admin.settings.show', ['home_general_settings'])}}" class="alert-action">
                                Link </a>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                        <x-datatable id="categories-table" :columns="$columns"
                                     route="{{ route('admin.categories.datatable') }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(($createPermission ?? false))
        <div class="modal modal-blur fade" id="bulk-upload-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('labels.bulk_upload_categories') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="bulk-upload-form" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label required">{{ __('labels.select_csv_file') }}</label>
                                <input type="file" name="file" accept=".csv" class="form-control" required/>
                                <small class="form-hint">{{ __('labels.bulk_upload_hint') }}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-primary" id="bulk-upload-submit">
                                    <span class="upload-text">{{ __('labels.upload') }}</span>
                                    <span class="spinner-border spinner-border-sm d-none" role="status"
                                          aria-hidden="true"></span>
                                </button>
                                <a href="{{ route('admin.categories.bulk-template') }}"
                                   class="btn btn-link">{{ __('labels.download_template') }}</a>
                            </div>
                        </form>
                        <div class="mt-3 d-none" id="bulk-upload-result">
                            <div class="alert alert-success" id="bulk-success" role="alert"></div>
                            <div class="alert alert-danger d-none" id="bulk-failed" role="alert"></div>
                            <div class="table-responsive d-none" id="bulk-failed-table-wrap">
                                <table class="table table-sm">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('labels.title') }}</th>
                                        <th>{{ __('labels.error') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody id="bulk-failed-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(($createPermission ?? false) || ($editPermission ?? false))
        <div
            class="modal modal-blur fade"
            id="category-modal"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            data-bs-backdrop="static"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form class="form-submit" action="{{route('admin.categories.store')}}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" id="category-id" value=""/>
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('labels.create_category') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required">{{ __('labels.category_name') }}</label>
                                <input type="text" class="form-control" name="title"
                                       placeholder="{{ __('labels.enter_category_name') }}"
                                />
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.description') }}</label>
                                <textarea class="form-control" name="description" rows="3"
                                          placeholder="{{ __('labels.enter_description') }}"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.parent_category') }}</label>
                                <select type="text" class="form-select" id="select-parent-category" name="parent_id">
                                    <!-- Options will be dynamically loaded -->
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">{{ __('labels.image') }}</label>
                                <input type="file" class="form-control" id="image-upload" name="image"
                                       data-image-url=""/>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.banner') }}</label>
                                <input type="file" class="form-control" id="banner-upload" name="banner"
                                       data-image-url=""/>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.icon') }}</label>
                                <input type="file" class="form-control" id="icon-upload" name="icon"
                                       data-image-url=""/>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.active_icon') }}</label>
                                <input type="file" class="form-control" id="active-icon-upload" name="active_icon"
                                       data-image-url=""/>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.background_type') }}</label>
                                <select class="form-select" name="background_type" id="background-type-select">
                                    <option value="">{{ __('labels.select_background_type') }}</option>
                                    <option value="color">{{ __('labels.color') }}</option>
                                    <option value="image">{{ __('labels.image') }}</option>
                                </select>
                                <small class="form-text text-muted">{{ __('labels.background_home_page_note') }}</small>
                            </div>

                            <div class="mb-3" id="background-color-field" style="display: none;">
                                <label class="form-label required">{{ __('labels.background_color') }}</label>
                                <input type="color" class="form-control form-control-color w-100"
                                       name="background_color" id="background-color-input"/>
                            </div>
                            <div class="mb-3" id="background-image-field" style="display: none;">
                                <label class="form-label required">{{ __('labels.background_image') }}</label>
                                <input type="file" class="form-control" id="background-image-upload"
                                       name="background_image"
                                       data-image-url=""/>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.font_color') }}</label>
                                <input type="color" class="form-control form-control-color w-100" name="font_color"
                                       id="font-color-input"/>
                            </div>


                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.commission') }}</label>
                                @if(Setting::isSubscriptionEnabled() && Setting::isSystemVendorTypeMultiple())
                                    <div class="alert alert-info alert-dismissible" role="alert">
                                        <div class="alert-icon">
                                            <!-- Download SVG icon from http://tabler.io/icons/icon/info-circle -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon alert-icon icon-2">
                                                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                                <path d="M12 9h.01"></path>
                                                <path d="M11 12h1v4h1"></path>
                                            </svg>
                                        </div>
                                        {{ __('labels.subscription_commission_note') }}
                                    </div>
                                @endif
                                <input type="number" class="form-control" name="commission" id="commission"
                                       placeholder="{{ __('labels.commission_placeholder') }}" step="0.01" min="0"
                                       max="100" value="0"/>
                                <small class="form-text text-muted">Enter commission percentage (0-100)</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="status" id="status-switch"
                                               value="active" checked>
                                        <label class="form-check-label"
                                               for="status-switch">{{ __('labels.status') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="requires_approval"
                                               id="approval-switch" value="1" checked>
                                        <label class="form-check-label"
                                               for="approval-switch">{{ __('labels.requires_approval') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <a href="#" class="btn"
                               data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="icon icon-2"
                                >
                                    <path d="M12 5l0 14"/>
                                    <path d="M5 12l14 0"/>
                                </svg>
                                {{ __('labels.create_new_category') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    <div class="offcanvas offcanvas-end" tabindex="-1" id="view-category-offcanvas" aria-labelledby="offcanvasEndLabel">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title" id="offcanvasEndLabel">Category Details</h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="card card-sm border-0">
                <label class="fw-medium pb-1">Banner</label>
                <div class="img-box-200px-h card-img">
                    <img id="banner-image" src=""/>
                </div>
                <label class="fw-medium pb-1 pt-3">Image</label>
                <div class="img-box-200px-h card-img">
                    <img id="card-image" src=""/>
                </div>
                <label class="fw-medium pb-1 pt-3">Icon</label>
                <div class="img-box-200px-h card-img">
                    <img id="icon-image" src=""/>
                </div>
                <label class="fw-medium pb-1 pt-3">Active Icon</label>
                <div class="img-box-200px-h card-img">
                    <img id="active-icon-image" src=""/>
                </div>
                <label class="fw-medium pb-1 pt-3">Background</label>
                <div id="background-display">
                    <p class="col-md-8 d-flex justify-content-between">Type: <span id="background-type"
                                                                                   class="fw-medium"></span></p>
                    <div id="background-color-display" style="display: none;">
                        <p class="col-md-8 d-flex justify-content-between">Color: <span id="background-color-value"
                                                                                        class="fw-medium"></span></p>
                        <div class="color-preview" id="background-color-preview"
                             style="width: 50px; height: 50px; border: 1px solid #ccc; border-radius: 4px;"></div>
                    </div>
                    <div id="background-image-display" style="display: none;">
                        <div class="img-box-200px-h card-img">
                            <img id="background-image-preview" src=""/>
                        </div>
                    </div>
                </div>
                <label class="fw-medium pb-1 pt-3">Font Color</label>
                <div id="font-color-display">
                    <p class="col-md-8 d-flex justify-content-between">Color: <span id="font-color-value"
                                                                                    class="fw-medium"></span></p>
                    <div class="color-preview form-control form-control-color w-100" id="font-color-preview"></div>
                </div>
                <div class="card-body px-0">
                    <div>
                        <h4 id="category-name" class="fs-3"></h4>
                        <p id="category-description" class="fs-4"></p>
                        <p class="col-md-8 d-flex justify-content-between">Status: <span id="category-status"
                                                                                         class="badge bg-green-lt text-uppercase fw-medium"></span>
                        </p>
                        <p class="col-md-8 d-flex justify-content-between">Parent Category: <span id="parent-category"
                                                                                                  class="fw-medium"></span>
                        </p>
                        <p class="col-md-8 d-flex justify-content-between">Commission: <span class="fw-medium"></span>%
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
