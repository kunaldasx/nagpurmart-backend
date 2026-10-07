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
                            <h5 class="modal-title" id="seller-user-approval-title">Confirm access change</h5>
                            <div class="text-muted mt-1" id="seller-user-approval-user"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="seller-user-approval-message"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" id="seller-user-approval-confirm">Confirm</button>
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
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('seller-user-approval-modal');
            const approvalForm = document.getElementById('seller-user-approval-form');
            const approvalModal = bootstrap.Modal.getOrCreateInstance(modalElement);
            const titleElement = document.getElementById('seller-user-approval-title');
            const userElement = document.getElementById('seller-user-approval-user');
            const messageElement = document.getElementById('seller-user-approval-message');
            const confirmButton = document.getElementById('seller-user-approval-confirm');

            function openApprovalModal(trigger) {
                const approve = trigger.dataset.approvalAction === 'approve';
                approvalForm.action = trigger.dataset.actionUrl;
                titleElement.textContent = approve ? 'Approve system user' : 'Disapprove system user';
                userElement.textContent = trigger.dataset.userName;
                messageElement.textContent = approve
                    ? 'This user will be able to access the seller account for 24 hours.'
                    : 'This user will lose seller access immediately, including active API sessions.';
                confirmButton.textContent = approve ? 'Approve access' : 'Disapprove access';
                confirmButton.className = approve ? 'btn btn-success' : 'btn btn-danger';
                approvalModal.show();
            }

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('.seller-user-approval-trigger');
                if (trigger) {
                    event.preventDefault();
                    openApprovalModal(trigger);
                }
            });

            if (window.jQuery) {
                window.jQuery(document).on('draw.dt', '#system-user-table', function () {
                    const autoOpen = document.querySelector('.seller-user-approval-trigger[data-auto-open="true"]');
                    if (autoOpen) {
                        autoOpen.dataset.autoOpen = 'false';
                        openApprovalModal(autoOpen);
                    }
                });
            }
        });
    </script>
@endsection
