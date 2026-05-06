@extends('layouts.admin.app', ['page' => $menuAdmin['roles_permissions']['active'] ?? "", 'sub_page' => $menuAdmin['roles_permissions']['route']['system_users']['sub_active'] ?? "" ])

@section('title', __('labels.system_user'))

@section('header_data')
    @php
        $page_title = __('labels.system_user');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.system_user'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <!-- BEGIN PAGE HEADER -->
        <div class="page-body">
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
                        <div class="card-table">
                            <div class="row w-full p-3">
                                <x-datatable id="system-user-table" :columns="$columns"
                                             route="{{ route('admin.system-users.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(($createPermission ?? false) || ($editPermission ?? false))

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
                    <form class="form-submit" action="{{route('admin.system-users.store')}}" method="POST">
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
                                        <input type="text" class="form-control" name="name"
                                               placeholder="Enter full name"
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
                                                   placeholder="{{ __('labels.enter_password') }}"  autocomplete="off" id="password"/>
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
    @endif
@endsection
