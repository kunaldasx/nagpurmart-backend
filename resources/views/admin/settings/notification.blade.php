@extends('layouts.admin.app',['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['notification']['sub_active'] ?? "" ])

@section('title', __('labels.notification_settings'))

@section('header_data')
    @php
        $page_title = __('labels.notification_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.notification_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.notification_settings') }}</h2>
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
                            <a class="nav-link" href="#pills-general">{{ __('labels.general') }}</a>
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post"
                                  enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="type" value="notification">
                                <div class="card mb-4" id="pills-general">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.general') }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.firebase_project_id') }}</label>
                                            <input type="text" class="form-control" name="firebaseProjectId"
                                                   placeholder="{{ __('labels.firebase_project_id_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['firebaseProjectId'] ?? '****'), '****', 3, 8) : ($settings['firebaseProjectId'] ?? '') }}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.service_account_file') }}</label>
                                            @if((!$systemSettings['demoMode'] ?? true) && $settings['serviceAccountFileExist'])
                                                <pre class="p-3 rounded bg-gray-700"
                                                     style="max-height: 400px; overflow: auto; font-size: 0.9rem;">
                                                {{ json_encode($settings['serviceAccountFileData'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
                                                </pre>
                                            @endif
                                            <input type="file" class="form-control" name="serviceAccountFile"/>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.vap_id_key') }}</label>
                                            <input type="text" class="form-control" name="vapIdKey"
                                                   placeholder="{{ __('labels.vap_id_key_placeholder') }}"
                                            value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['vapIdKey'] ?? '****'), '****', 3, 8) : ($settings['vapIdKey'] ?? '') }}"/>
                                        </div>

                                        @php
                                            // Use dynamic paths so this works across environments
                                            $cronLogPath = storage_path('logs/cron-log.txt');
                                            $cronLogExists = file_exists($cronLogPath);
                                            $cronLogMTime = $cronLogExists ? @filemtime($cronLogPath) : null;
                                        @endphp
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.cron_job_queue_worker') }}</label>

                                            <div class="text-muted mb-2">
                                                {{ __('messages.add_cron_instruction') }}
                                            </div>

                                            <pre class="p-3 rounded bg-gray-700 text-white" style="overflow:auto; font-size:0.9rem;">
                                                * * * * * /usr/bin/php {{ base_path('artisan') }} queue:work --stop-when-empty >> {{ storage_path('logs/cron-log.txt') }} 2>&1
                                            </pre>

                                            <div class="small text-muted">
                                                {{ __('messages.php_path_note') }}
                                            </div>

                                            <div class="mt-3">
                                                @if($cronLogExists)
                                                    <div class="alert alert-success" role="alert">
                                                        {{ __('labels.cron_status') }}: {{ __('messages.cron_detected', ['path' => $cronLogPath]) }}
                                                        @if($cronLogMTime)
                                                            <div class="small mt-1">
                                                                {{ __('labels.last_updated') }}:
                                                                {{ \Carbon\Carbon::createFromTimestamp($cronLogMTime)->toDayDateTimeString() }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="alert alert-warning flex-column" role="alert">
                                                        <div>{{ __('messages.cron_not_detected_full') }}</div>
                                                        <div>{{ __('labels.cron_not_detected') }}. {{ __('messages.log_file_not_found') }}</div>
                                                        <code>{{ $cronLogPath }}</code>
                                                        <div>{{ __('messages.cron_has_not_run_yet') }}</div>

                                                        <div class="mt-2">
                                                            {{ __('messages.view_documentation') }}:
                                                            <a href="https://docs-hyper-local.vercel.app/introduction" target="_blank" class="alert-link">
                                                                {{ __('labels.please_refer_to_docs') }}
                                                            </a>.
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'notification'])
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
