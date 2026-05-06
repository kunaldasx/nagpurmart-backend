@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['app']['sub_active'] ?? "" ])

@section('title', __('labels.app_settings'))

@section('header_data')
    @php
        $page_title = __('labels.app_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.app_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.app_settings') }}</h2>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-5">
                <div class="col-sm-2 d-none d-lg-block">
                    <div class="sticky-top">
                        <h3>{{ __('labels.menu') }}</h3>
                        <nav class="nav nav-vertical nav-pills" id="pills">
                            <a class="nav-link" href="#pills-customer">{{ __('labels.customer_app') }}</a>
                            <a class="nav-link" href="#pills-seller">{{ __('labels.seller_app') }}</a>
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="app">
                                <div class="card mb-4" id="pills-customer">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.customer_app') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.appstore_link') }}</label>
                                            <input type="url" class="form-control" name="customerAppstoreLink"
                                                   placeholder="{{ __('labels.appstore_link_placeholder') }}"
                                                   value="{{ $settings['customerAppstoreLink'] ?? '' }}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.playstore_link') }}</label>
                                            <input type="url" class="form-control" name="customerPlaystoreLink"
                                                   placeholder="{{ __('labels.playstore_link_placeholder') }}"
                                                   value="{{ $settings['customerPlaystoreLink'] ?? '' }}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.app_scheme') }}</label>
                                            <input type="text" class="form-control" name="customerAppScheme"
                                                   placeholder="{{ __('labels.app_scheme_placeholder') }}"
                                                   value="{{ $settings['customerAppScheme'] ?? '' }}"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4" id="pills-seller">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.seller_app') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.appstore_link') }}</label>
                                            <input type="url" class="form-control" name="sellerAppstoreLink"
                                                   placeholder="{{ __('labels.appstore_link_placeholder') }}"
                                                   value="{{ $settings['sellerAppstoreLink'] ?? '' }}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.playstore_link') }}</label>
                                            <input type="url" class="form-control" name="sellerPlaystoreLink"
                                                   placeholder="{{ __('labels.playstore_link_placeholder') }}"
                                                   value="{{ $settings['sellerPlaystoreLink'] ?? '' }}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.app_scheme') }}</label>
                                            <input type="text" class="form-control" name="sellerAppScheme"
                                                   placeholder="{{ __('labels.app_scheme_placeholder') }}"
                                                   value="{{ $settings['sellerAppScheme'] ?? '' }}"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'app'])
                                            <button type="submit"
                                                    class="btn btn-primary ms-auto">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
