@extends('layouts.admin.app',['page' => $menuAdmin['banners']['active'] ?? "", 'sub_page' => $menuAdmin['banners']['route']['index']['sub_active'] ?? "" ])

@section('title', __('labels.banners'))

@section('header_data')
    @php
        $page_title = __('labels.banners');
        $page_pretitle = __('labels.admin') . " " . __('labels.management');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.banners'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-body">
        <div class="row row-deck row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('labels.banners_list') }}</h3>
                            <x-breadcrumb :items="$breadcrumbs"/>
                        </div>
                        <div class="card-actions">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <select class="form-select" id="typeFilter">
                                        <option value="">{{ __('labels.all_types') }}</option>
                                        @foreach($bannerTypes as $type)
                                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <select class="form-select" id="positionFilter">
                                        <option value="">{{ __('labels.all_positions') }}</option>
                                        @foreach($bannerPositions as $position)
                                            <option value="{{ $position }}">{{ ucfirst($position) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <select class="form-select" id="statusFilter">
                                        <option value="">{{ __('labels.all_status') }}</option>
                                        <option value="published">{{ __('labels.published') }}</option>
                                        <option value="draft">{{ __('labels.draft') }}</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <select class="form-select" id="scopeTypeFilter">
                                        <option value="">{{ __('labels.all_scopes') }}</option>
                                        @foreach($scopeTypes as $scopeType)
                                            <option value="{{ $scopeType }}">{{ ucfirst($scopeType) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    @if($createPermission)
                                        <div class="btn-list">
                                            <a href="{{ route('admin.banners.create') }}"
                                               class="btn btn-primary d-none d-sm-inline-block">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                     height="24"
                                                     viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                     fill="none"
                                                     stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                                </svg>
                                                {{ __('labels.create_banner') }}
                                            </a>
                                            <a href="{{ route('admin.banners.create') }}"
                                               class="btn btn-primary d-sm-none btn-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                     height="24"
                                                     viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                     fill="none"
                                                     stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                                </svg>
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
                    <div class="card-body">
                        <x-datatable id="banners-table" :columns="$columns"
                                     route="{{ route('admin.banners.datatable') }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{hyperAsset('assets/js/banner.js')}}" defer></script>
@endpush
