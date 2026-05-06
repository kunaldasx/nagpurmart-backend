@php use App\Enums\SettingTypeEnum;use Carbon\Carbon; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['subscriptions']['active'] ?? "", 'sub_page' => $menuAdmin['subscriptions']['route']['plans']['sub_active'] ?? null])

@section('title', __('labels.subscription_plans'))

@section('header_data')
    @php
        $page_title = __('labels.subscription_plans');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.subscription_plans'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-body">
        <div class="container-xl">
            @if(!$subscriptionEnabled)
                @include('components.subscription-feature', ['settings' => $subscriptionSettings])
            @else
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('labels.subscription_settings') }}</h3>
                    </div>
                    <div class="card-body">
                        @php
                            // Use dynamic paths so this works across environments
                            $schedulePath = storage_path('logs/schedule.txt');
                            $scheduleExists = file_exists($schedulePath);
                            $scheduleMTime = $scheduleExists ? @filemtime($schedulePath) : null;
                        @endphp
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.subscription_expiry_scheduler') }}</label>

                            <div class="text-muted mb-2">
                                {{ __('messages.subscription_expiry_scheduler_instruction') }}
                            </div>

                            <pre class="p-3 rounded bg-gray-700 text-white" style="overflow:auto; font-size:0.9rem;">
                                                * * * * * /usr/bin/php {{ base_path('artisan') }} schedule:run >> {{ $schedulePath }} 2>&1
                                            </pre>

                            <div class="small text-muted">
                                {{ __('messages.php_path_note') }}
                            </div>

                            <div class="mt-3">
                                @if($scheduleExists)
                                    <div class="alert alert-success" role="alert">
                                        {{ __('labels.cron_status') }}
                                        : {{ __('messages.cron_detected', ['path' => $schedulePath]) }}
                                        @if($scheduleMTime)
                                            <div class="small mt-1">
                                                {{ __('labels.last_updated') }}:
                                                {{ Carbon::createFromTimestamp($scheduleMTime)->toDayDateTimeString() }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="alert alert-warning flex-column" role="alert">
                                        <div>{{ __('messages.subscription_cron_not_detected_full') }}</div>
                                        <div>{{ __('labels.cron_not_detected') }}
                                            . {{ __('messages.log_file_not_found') }}</div>
                                        <code>{{ $schedulePath }}</code>
                                        <div>{{ __('messages.cron_has_not_run_yet') }}</div>

                                        <div class="mt-2">
                                            {{ __('messages.view_documentation') }}:
                                            <a href="https://docs-hyper-local.vercel.app/introduction" target="_blank"
                                               class="alert-link">
                                                {{ __('labels.please_refer_to_docs') }}
                                            </a>.
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <form action="{{route('admin.settings.store')}}" method="POST" class="form-submit">
                            @csrf
                            <input type="hidden" name="redirect_url"
                                   value="{{route('admin.subscription-plans.index')}}">
                            <input type="hidden" name="type" value="{{SettingTypeEnum::SUBSCRIPTION()}}">
                            <div class="mb-3">
                                <label for="subscription_heading"
                                       class="form-label">{{ __('labels.subscription_heading') }}</label>
                                <input type="text" name="subscriptionHeading" id="subscription_heading"
                                       class="form-control" value="{{$subscriptionSettings['subscriptionHeading']}}"
                                       {{ $editPermission ? '' : 'readonly disabled' }}>
                            </div>
                            <div class="mb-3">
                                <label for="subscription_description"
                                       class="form-label">{{ __('labels.subscription_description') }}</label>
                                <input type="text" name="subscriptionDescription" id="subscription_description"
                                       class="form-control"
                                       value="{{$subscriptionSettings['subscriptionDescription']}}"
                                       {{ $editPermission ? '' : 'readonly disabled' }}>
                            </div>
                            @if($editPermission)
                                <button type="submit" class="btn btn-primary">{{ __('labels.save_settings') }}</button>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('labels.subscription_plans') }}</h3>
                            <x-breadcrumb :items="$breadcrumbs"/>
                        </div>
                        <div class="card-actions">
                            <div class="row g-2">
                                @if($subscriberViewPermission)
                                    <div class="col-auto ms-auto d-print-none">
                                        <a href="{{ route('admin.subscription-plans.subscribers') }}"
                                           class="btn btn-outline-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                 stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-list">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M9 6l11 0"/>
                                                <path d="M9 12l11 0"/>
                                                <path d="M9 18l11 0"/>
                                                <path d="M5 6l0 .01"/>
                                                <path d="M5 12l0 .01"/>
                                                <path d="M5 18l0 .01"/>
                                            </svg>
                                            {{ __('labels.subscribers') }}
                                        </a>
                                    </div>
                                @endif
                                @if($createPermission)
                                    <div class="col-auto ms-auto d-print-none">
                                        <a href="{{ route('admin.subscription-plans.create') }}" class="btn btn-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                 stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <line x1="12" y1="5" x2="12" y2="19"/>
                                                <line x1="5" y1="12" x2="19" y2="12"/>
                                            </svg>
                                            {{ __('labels.add_plan') }}
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
                            <x-datatable id="subscription-plans-table" :columns="$columns"
                                         route="{{ route('admin.subscription-plans.datatable') }}"
                                         :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('labels.preview') }}</h3>
                    </div>
                    @include('components.subscription-pricing', ['subscriptionSettings' => $subscriptionSettings])
                </div>

            @endif
        </div>
    </div>
@endsection
