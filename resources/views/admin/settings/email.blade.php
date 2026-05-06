@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['email']['sub_active'] ?? "" ])

@section('title', __('labels.email_settings'))

@section('header_data')
    @php
        $page_title = __('labels.email_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.email_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.email_settings') }}</h2>
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
                            <a class="nav-link" href="#pills-smtp">{{ __('labels.smtp') }}</a>
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="email">
                                <div class="card mb-4" id="pills-smtp">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.smtp') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.smtp_host') }}</label>
                                            <input type="text" class="form-control" name="smtpHost"
                                                   placeholder="{{ __('labels.smtp_host_placeholder') }}"
                                                   value="{{ $settings['smtpHost'] ?? '' }}" required/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.smtp_port') }}</label>
                                            <input type="number" class="form-control" name="smtpPort"
                                                   placeholder="{{ __('labels.smtp_port_placeholder') }}"
                                                   value="{{ $settings['smtpPort'] ?? '' }}" required/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.smtp_email') }}</label>
                                            <input type="email" class="form-control" name="smtpEmail"
                                                   placeholder="{{ __('labels.smtp_email_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['smtpEmail'] ?? '****'), '****', 3, 8) : ($settings['smtpEmail'] ?? '') }}" required/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.smtp_password') }}</label>
                                            <input type="password" class="form-control" name="smtpPassword"
                                                   placeholder="{{ __('labels.smtp_password_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['smtpPassword'] ?? '****'), '****', 3, 8) : ($settings['smtpPassword'] ?? '') }}" required/>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.smtp_encryption') }}</label>
                                            <select class="form-select" name="smtpEncryption" required>
                                                <option value=""
                                                        disabled {{ !isset($settings['smtpEncryption']) ? 'selected' : '' }}>{{ __('labels.smtp_encryption_placeholder') }}</option>
                                                <option
                                                    value="tls" {{ isset($settings['smtpEncryption']) && $settings['smtpEncryption'] === 'tls' ? 'selected' : '' }}>
                                                    TLS
                                                </option>
                                                <option
                                                    value="ssl" {{ isset($settings['smtpEncryption']) && $settings['smtpEncryption'] === 'ssl' ? 'selected' : '' }}>
                                                    SSL
                                                </option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label required">{{ __('labels.smtp_content_type') }}</label>
                                            <select class="form-select" name="smtpContentType" required>
                                                <option value=""
                                                        disabled {{ !isset($settings['smtpContentType']) ? 'selected' : '' }}>{{ __('labels.smtp_content_type_placeholder') }}</option>
                                                <option
                                                    value="text" {{ isset($settings['smtpContentType']) && $settings['smtpContentType'] === 'text' ? 'selected' : '' }}>
                                                    Text
                                                </option>
                                                <option
                                                    value="html" {{ isset($settings['smtpContentType']) && $settings['smtpContentType'] === 'html' ? 'selected' : '' }}>
                                                    HTML
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'email'])
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
