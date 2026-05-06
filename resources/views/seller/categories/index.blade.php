@extends('layouts.seller.app', ['page' => $menuSeller['categories']['active'] ?? ""])

@section('title', __('labels.categories'))

@section('header_data')
    @php
        $page_title = __('labels.categories');
        $page_pretitle = __('labels.seller') . " " . __('labels.categories');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.categories'), 'url' => '']
    ];
@endphp

@section('seller-content')

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
                        <x-datatable id="categories-table" :columns="$columns"
                                     route="{{ route('seller.categories.datatable') }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    <p class="col-md-8 d-flex justify-content-between">Type: <span id="background-type" class="fw-medium"></span></p>
                    <div id="background-color-display" style="display: none;">
                        <p class="col-md-8 d-flex justify-content-between">Color: <span id="background-color-value" class="fw-medium"></span></p>
                        <div class="color-preview" id="background-color-preview" style="width: 50px; height: 50px; border: 1px solid #ccc; border-radius: 4px;"></div>
                    </div>
                    <div id="background-image-display" style="display: none;">
                        <div class="img-box-200px-h card-img">
                            <img id="background-image-preview" src=""/>
                        </div>
                    </div>
                </div>
                <label class="fw-medium pb-1 pt-3">Font Color</label>
                <div id="font-color-display">
                    <p class="col-md-8 d-flex justify-content-between">Color: <span id="font-color-value" class="fw-medium"></span></p>
                    <div class="color-preview" id="font-color-preview" style="width: 50px; height: 50px; border: 1px solid #ccc; border-radius: 4px;"></div>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
