@extends('layouts.admin.app', ['page' => $menuAdmin['brands']['active'] ?? ""])

@section('title', __('labels.brands'))

@section('header_data')
    @php
        $page_title = __('labels.brands');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.brands'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.brands') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                @if($createPermission ?? false)
                                    <div class="col text-end">
                                        <a href="#" class="btn btn-6 btn-outline-primary" data-bs-toggle="modal"
                                           data-bs-target="#brand-modal">
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
                                            {{ __('labels.add_brand') }}
                                        </a>
                                        <a href="{{ route('admin.brands.bulk-upload.page') }}"
                                           class="btn btn-outline-success">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                 height="24" viewBox="0 0 24 24" stroke-width="2"
                                                 stroke="currentColor" fill="none" stroke-linecap="round"
                                                 stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                                                <path d="M7 9l5 -5l5 5"/>
                                                <path d="M12 4l0 12"/>
                                            </svg>
                                            {{ __('labels.bulk_upload') }}
                                        </a>
                                    </div>
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
                <div class="card-table">
                    <div class="row w-full p-3">
                        <x-datatable id="brands-table" :columns="$columns"
                                     route="{{ route('admin.brands.datatable') }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(($createPermission ?? false) || ($editPermission ?? false))
        <div
            class="modal modal-blur fade"
            id="brand-modal"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            data-bs-backdrop="static"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form class="form-submit" action="{{route('admin.brands.store')}}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('labels.create_brand') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required">{{ __('labels.scope_type') }}</label>
                                <select class="form-select" name="scope_type" id="scopeType">
                                    <option value="">{{ __('labels.select_scope_type') }}</option>
                                    @foreach(\App\Enums\HomePageScopeEnum::values() as $scopeType)
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
                            <div class="mb-3" id="scopeCategoryField" style="display: none;">
                                <label class="form-label">{{ __('labels.scope_category') }}</label>
                                <select class="form-select" name="scope_id" id="select-root-category">
                                    <option value="">{{ __('labels.select_category') }}</option>
                                    @if(!empty($scopeCategory))
                                        <option value="{{$scopeCategory->id}}"
                                                selected>{{$scopeCategory->title}}</option>
                                    @endif
                                </select>
                                @error('scope_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label required">{{ __('labels.brand_name') }}</label>
                                <input type="text" class="form-control" name="title"
                                       placeholder="{{ __('labels.enter_brand_name') }}"
                                />
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.description') }}</label>
                                <textarea class="form-control" name="description" rows="3"
                                          placeholder="{{ __('labels.enter_description') }}"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">{{ __('labels.logo') }}</label>
                                <input type="file" class="form-control" id="logo-upload" name="logo" data-logo-url=""/>
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
                                {{ __('labels.create_new_brand') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{asset('assets/js/brands.js')}}"></script>
@endpush
