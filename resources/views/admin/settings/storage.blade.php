@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['storage']['sub_active'] ?? "" ])

@section('title', __('labels.storage_settings'))

@section('header_data')
    @php
        $page_title = __('labels.storage_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.storage_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.storage_settings') }}</h2>
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
                            <a class="nav-link" href="#pills-aws">{{ __('labels.aws_s3') }}</a>
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="storage">
                                <div class="card mb-4" id="pills-aws">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.aws_s3') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.aws_access_key_id') }}</label>
                                            <input type="text" class="form-control" name="awsAccessKeyId"
                                                   placeholder="{{ __('labels.aws_access_key_id_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['awsAccessKeyId'] ?? '****'), '****', 3, 8) : ($settings['awsAccessKeyId'] ?? '') }}" required/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.aws_secret_access_key') }}</label>
                                            <input type="text" class="form-control" name="awsSecretAccessKey"
                                                   placeholder="{{ __('labels.aws_secret_access_key_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['awsSecretAccessKey'] ?? '****'), '****', 3, 8) : ($settings['awsSecretAccessKey'] ?? '') }}"
                                                   required/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.aws_region') }}</label>
                                            <input type="text" class="form-control" name="awsRegion"
                                                   placeholder="{{ __('labels.aws_region_placeholder') }}"
                                                   value="{{ $settings['awsRegion'] ?? '' }}" required/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.aws_bucket') }}</label>
                                            <input type="text" class="form-control" name="awsBucket"
                                                   placeholder="{{ __('labels.aws_bucket_placeholder') }}"
                                                   value="{{ $settings['awsBucket'] ?? '' }}" required/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.aws_asset_url') }}</label>
                                            <input type="url" class="form-control" name="awsAssetUrl"
                                                   placeholder="{{ __('labels.aws_asset_url_placeholder') }}"
                                                   value="{{ $settings['awsAssetUrl'] ?? '' }}" required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'storage'])
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
