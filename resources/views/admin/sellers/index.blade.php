@extends('layouts.admin.app', ['page' => $menuAdmin['seller_management']['active'] ?? "", 'sub_page' => $menuAdmin['seller_management']['route']['sellers']['sub_active'] ?? "" ])

@section('title', __('labels.sellers'))

@section('header_data')
    @php
        $page_title = __('labels.sellers');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.sellers'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">

        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">{{ __('labels.sellers') }} <span class="order-count"></span>
                                </h3>
                                <x-breadcrumb :items="$breadcrumbs"/>
                            </div>
                            <div class="card-actions">
                                <div class="row g-2">
                                    @if($createPermission ?? false)
                                        <div class="col-auto">
                                            <a href="{{route('admin.sellers.create')}}"
                                               class="btn btn-6 btn-outline-primary">
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
                                                {{ __('labels.add_seller') }}
                                            </a>
                                        </div>
                                    @endif
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
                                <x-datatable id="sellers-table" :columns="$columns"
                                             route="{{ route('admin.sellers.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
