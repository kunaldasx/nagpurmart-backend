@extends('layouts.admin.app',['page' => $menuAdmin['offer-banners']['active'] ?? "", 'sub_page' => $menuAdmin['offer-banners']['route']['index']['sub_active'] ?? "" ])

@section('title', __('labels.offer_banners'))

@section('header_data')
    @php
        $page_title = __('labels.offer_banners');
        $page_pretitle = __('labels.admin') . " " . __('labels.management');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.offer_banners'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-body">
        <div class="row row-deck row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('labels.offer_banners_list') }}</h3>
                            <x-breadcrumb :items="$breadcrumbs"/>
                        </div>
                        <div class="card-actions">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <select class="form-select" id="scopeTypeFilter">
                                        <option value="">{{ __('labels.all_scopes') }}</option>
                                        @foreach($scopeTypes as $scopeType)
                                            <option value="{{ $scopeType }}">{{ ucfirst($scopeType) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <div class="btn-list">
                                        <a href="{{ route('admin.offer-banners.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                                            {{ __('labels.create_offer_banner') }}
                                        </a>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-outline-primary" id="refresh">{{ __('labels.refresh') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <x-datatable id="offer-banners-table" :columns="$columns" route="{{ route('admin.offer-banners.datatable') }}" :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{hyperAsset('assets/js/offer-banner.js')}}" defer></script>
@endpush
