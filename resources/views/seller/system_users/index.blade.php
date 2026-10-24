@extends('layouts.seller.app', ['page' => $menuSeller['roles_permissions']['active'] ?? "", "sub_page" => $menuSeller['roles_permissions']['route']['system_users']['sub_active'] ?? ""])

@section('title', __('labels.system_user'))

@section('header_data')
    @php
        $page_title = __('labels.system_user');
        $page_pretitle = __('labels.roles_permissions');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.roles_permissions'), 'url' => route('seller.roles.index')],
        ['title' => __('labels.system_user'), 'url' => '']
    ];
@endphp

@section('seller-content')
    @push('styles')
        <style>
            .seller-user-approval-control { display: inline-flex; align-items: center; gap: .55rem; white-space: nowrap; }
            .seller-user-approval-status { padding: .45rem .7rem; border-radius: .55rem; font-size: .9rem; font-weight: 700; letter-spacing: .01em; }
            .seller-user-approval-edit { width: 2.35rem; height: 2.35rem; flex: 0 0 2.35rem; border: 1px solid #8fb6ff; border-radius: .55rem; color: #206bc4; background: #fff; font-size: 1.15rem; transition: background-color .15s ease, color .15s ease, box-shadow .15s ease; }
            .seller-user-approval-edit:hover, .seller-user-approval-edit:focus-visible { color: #fff; background: #206bc4; box-shadow: 0 0 0 .2rem rgba(32, 107, 196, .15); }
            #seller-user-approval-modal .approval-duration-panel { padding: 1rem; border: 1px solid #dce5ef; border-radius: .75rem; background: #f8fafc; }
            #seller-user-approval-modal .approval-duration-panel .form-control { min-height: 2.8rem; font-size: 1rem; font-weight: 600; }
            #seller-user-approval-modal .modal-footer .btn { min-width: 8rem; }
            @media (max-width: 575.98px) { #seller-user-approval-modal .modal-footer { display: grid; grid-template-columns: 1fr 1fr; } #seller-user-approval-modal .modal-footer .btn { width: 100%; } }
        </style>
    @endpush
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.system_users') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                @if($createPermission)
                                    <div class="col text-end">
                                        @if($systemUserCreateLimitReached ?? false)
                                            <button type="button" class="btn btn-6 btn-outline-primary" disabled>
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
                                                {{ __('labels.add_new_user') }}
                                            </button>
                                        @else
                                            <a href="#" class="btn btn-6 btn-outline-primary" data-bs-toggle="modal"
                                               data-bs-target="#system-user-modal">
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
                                                {{ __('labels.add_new_user') }}
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
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
                @if($systemUserCreateLimitReached ?? false)
                    <div class="card-body border-bottom py-3">
                        <div class="alert alert-warning mb-0" role="alert">
                            {{ $systemUserCreateLimitMessage }}
                        </div>
                    </div>
                @endif
                <div class="card-table">
                    <div class="row w-full p-3">
                        <x-datatable id="system-user-table" :columns="$columns"
                                     route="{{ route('seller.system-users.datatable', array_filter(['approval_user_id' => $approvalUserId])) }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal modal-blur fade" id="seller-user-approval-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="seller-user-approval-form" method="POST">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="seller-user-approval-title">Login approval status</h5>
                            <div class="text-muted mt-1" id="seller-user-approval-user"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">Current status: <span class="badge" id="seller-user-approval-status"></span></div>
                        <div class="text-muted mb-3" id="seller-user-approval-message"></div>
                        <div class="approval-duration-panel mb-3">
                            <label class="form-label" for="seller-user-approval-hours">Approval duration (hours)</label>
                            <input id="seller-user-approval-hours" name="duration_hours" type="number" class="form-control" min="1" max="{{ app(\App\Services\DeliveryBoyLoginService::class)->durationHours() }}" value="{{ min(12, app(\App\Services\DeliveryBoyLoginService::class)->durationHours()) }}" required>
                            <div class="form-hint">Default: 12 hours. Maximum allowed by platform: {{ app(\App\Services\DeliveryBoyLoginService::class)->durationHours() }} hours.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-lg" id="seller-user-approve"
                                formaction="">Approve</button>
                        <button type="submit" class="btn btn-outline-danger btn-lg" id="seller-user-disapprove"
                                formaction="">Disapprove</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div
        class="modal modal-blur fade"
        id="system-user-modal"
        tabindex="-1"
        role="dialog"
        aria-hidden="true"
        data-bs-backdrop="static"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form class="form-submit" action="{{route('seller.system-users.store')}}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Name</label>
                                    <input type="text" class="form-control" name="name" placeholder="Enter full name"
                                           required/>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Email</label>
                                    <input type="email" class="form-control" name="email"
                                           placeholder="Enter email address"
                                           required/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Mobile</label>
                                    <input type="tel" class="form-control" name="mobile"
                                           placeholder="Enter mobile number" required/>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Password</label>
                                    <div class="input-group mb-2">
                                        <input type="password"
                                               class="form-control @error('password') is-invalid @enderror"
                                               name="password"
                                               placeholder="{{ __('labels.enter_password') }}" autocomplete="off"
                                               id="password"/>
                                        <span class="input-group-text">
                                                <a href="#" class="link-secondary"
                                                   title="Show password" id="password-toggle"
                                                   data-bs-toggle="tooltip">
                                                    <!-- Download SVG icon from http://tabler.io/icons/icon/eye -->
                                                    Show
                                                </a>
                                            </span>
                                        <button class="btn password-button" type="button">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                 height="24"
                                                 viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor"
                                                 stroke-width="2" stroke-linecap="round"
                                                 stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-sparkles m-0">
                                                <path stroke="none" d="M0 0h24v24H0z"
                                                      fill="none"/>
                                                <path
                                                    d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Role</label>
                            <select class="form-select" name="roles[]" id="select-roles" multiple required>
                                <option value="">Select a role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn"
                           data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                        <button type="submit" class="btn btn-primary">
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
                            Add New User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const modalElement = document.getElementById('seller-user-approval-modal');
            const approvalForm = document.getElementById('seller-user-approval-form');
            const userElement = document.getElementById('seller-user-approval-user');
            const messageElement = document.getElementById('seller-user-approval-message');
            const statusElement = document.getElementById('seller-user-approval-status');
            const approveButton = document.getElementById('seller-user-approve');
            const disapproveButton = document.getElementById('seller-user-disapprove');
            const durationInput = document.getElementById('seller-user-approval-hours');
            let currentApprovalActive = false;

            modalElement.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const status = trigger.dataset.status || 'not_requested';
                const approved = trigger.dataset.approvalActive === 'true';
                currentApprovalActive = approved;
                const maxHours = Number(durationInput.max || 12);
                const defaultHours = Math.min(12, maxHours || 12);
                durationInput.value = defaultHours;
                approvalForm.action = trigger.dataset.disapproveUrl;
                approveButton.setAttribute('formaction', trigger.dataset.approveUrl);
                disapproveButton.setAttribute('formaction', trigger.dataset.disapproveUrl);
                userElement.textContent = trigger.dataset.userName;
                statusElement.textContent = approved && trigger.dataset.approvedUntil
                    ? `Approved until ${trigger.dataset.approvedUntil}`
                    : status === 'approved' ? 'Expired' : status.replaceAll('_', ' ').replace(/\b\w/g, character => character.toUpperCase());
                statusElement.className = `badge ${approved ? 'bg-green-lt' : status === 'pending' ? 'bg-yellow-lt' : 'bg-red-lt'}`;
                messageElement.textContent = approved
                    ? 'You can extend this approval or disapprove to revoke the user’s active API access immediately.'
                    : `Approving grants this user access for ${durationInput.value} hours.`;
                disapproveButton.disabled = !approved && status === 'disapproved';
                approveButton.textContent = approved ? `Extend for ${durationInput.value} hours` : `Approve for ${durationInput.value} hours`;
            });

            durationInput.addEventListener('input', function () {
                const hours = Number(durationInput.value);
                if (Number.isInteger(hours) && hours >= 1) {
                    messageElement.textContent = `${currentApprovalActive ? 'Extending' : 'Approving'} grants this user access for ${hours} hours.`;
                    approveButton.textContent = `${currentApprovalActive ? 'Extend' : 'Approve'} for ${hours} hours`;
                }
            });

            if (window.jQuery) {
                window.jQuery(document).on('draw.dt', '#system-user-table', function () {
                    const autoOpen = document.querySelector('.seller-user-approval-trigger[data-auto-open="true"]');
                    if (autoOpen) {
                        autoOpen.dataset.autoOpen = 'false';
                        autoOpen.click();
                    }
                });
            }
        })();
    </script>
@endsection
