@extends('layouts.admin.app', ['page' => $menuAdmin['notifications']['active'] ?? ""])

@section('title', __('labels.notifications'))

@section('header_data')
    @php
        $page_title = __('labels.notifications');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.notifications'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.notifications') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            @if($editPermission)
                                <div class="col-auto">
                                    <button class="btn btn-outline-success" id="mark-all-read-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                             stroke-linecap="round" stroke-linejoin="round"
                                             class="icon icon-tabler icons-tabler-outline icon-tabler-check-all">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M7 12l5 5l10 -10"/>
                                            <path d="M2 12l5 5m5 -5l5 -5"/>
                                        </svg>
                                        {{ __('labels.mark_all_as_read') }}
                                    </button>
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
                        <x-datatable id="notifications-table" :columns="$columns"
                                     route="{{ route('admin.notifications.datatable') }}"
                                     :options="['order' => [[5, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Notification Modal -->
    <div class="modal modal-blur fade" id="viewNotificationModal" tabindex="-1" role="dialog" aria-hidden="true"
         data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('labels.notification_details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.title') }}</label>
                                <div class="form-control-plaintext" id="modal-title"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.type') }}</label>
                                <div class="form-control-plaintext" id="modal-type"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.sent_to') }}</label>
                                <div class="form-control-plaintext" id="modal-sent-to"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{ __('labels.status') }}</label>
                                <div class="form-control-plaintext" id="modal-status"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('labels.message') }}</label>
                        <div class="form-control-plaintext" id="modal-message" style="white-space: pre-wrap;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('labels.created_at') }}</label>
                        <div class="form-control-plaintext" id="modal-created-at"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('labels.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mark All as Read Confirmation Modal -->
    <div class="modal modal-blur fade" id="markAllReadModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-title">{{ __('labels.confirm_mark_all_read') }}</div>
                    <div>{{ __('labels.mark_all_read_confirmation') }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                    <button type="button" class="btn btn-success"
                            id="confirmMarkAllRead">{{ __('labels.yes_mark_all') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal modal-blur fade" id="deleteNotificationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-title">{{ __('labels.are_you_sure') }}</div>
                    <div>{{ __('labels.this_action_cannot_be_undone') }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                    <button type="button" class="btn btn-danger"
                            id="confirmDeleteNotification">{{ __('labels.yes_delete') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/notification.js') }}"></script>
@endpush
