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
                            @if($createPermission)
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-primary" id="create-broadcast-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-bell-plus">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M10 5a4 4 0 0 1 8 0a7 7 0 0 1 4 6v3a3 3 0 0 0 1 2h-6a4 4 0 0 1 -8 0"/>
                                            <path d="M9 17h-3"/>
                                            <path d="M9 13h-3"/>
                                            <path d="M9 9h-3"/>
                                            <path d="M15 6h6"/>
                                            <path d="M18 3v6"/>
                                        </svg>
                                        Create campaign
                                    </button>
                                </div>
                            @endif
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
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h4 class="mb-1">Customer notification campaigns</h4>
                                        <p class="text-muted mb-0">Create, review, and resend announcements for the customer app.</p>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table" id="broadcasts-table">
                                        <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Recipients</th>
                                            <th>Sent at</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
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

    <div class="modal modal-blur fade" id="broadcastModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create customer notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="broadcast-form" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Campaign title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4" required></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Image URL</label>
                                <input type="url" class="form-control" name="image_url" placeholder="https://example.com/image.png">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Image upload</label>
                                <input type="file" class="form-control" name="image_file" accept="image/*">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Action URL</label>
                                <input type="url" class="form-control" name="action_url" placeholder="https://example.com/offers">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Deep link</label>
                                <input type="text" class="form-control" name="deep_link" placeholder="product/123">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">Target categories</label>
                                <input type="text" class="form-control" name="target_categories" placeholder="offers,seasonal">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Expiry date</label>
                                <input type="date" class="form-control" name="expires_at">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority">
                                    <option value="0">Normal</option>
                                    <option value="1">High</option>
                                    <option value="2">Critical</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">Active immediately</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-broadcast-btn">Send to customers</button>
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
