@extends('layouts.admin.app',['page' => $menuAdmin['roles_permissions']['active'] ?? "", 'sub_page' => $menuAdmin['roles_permissions']['route']['roles']['sub_active'] ?? "" ])

@section('title', __('labels.roles'))

@section('header_data')
    @php
        $page_title = __('labels.roles');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.roles'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">{{ __('labels.roles') }}</h3>
                                <x-breadcrumb :items="$breadcrumbs"/>
                            </div>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        @if($createPermission)
                                            <div class="col text-end">
                                                <a href="#" class="btn btn-6 btn-outline-primary" data-bs-toggle="modal"
                                                   data-bs-target="#role-modal">
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
                                                    {{ __('labels.add_new_role') }}
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
                                <x-datatable id="roles-table" :columns="$columns"
                                             route="{{ route('admin.roles.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(($createPermission ?? false) || ($editPermission ?? false))
            <div
                class="modal modal-blur fade"
                id="role-modal"
                tabindex="-1"
                role="dialog"
                aria-hidden="true"
                data-bs-backdrop="static"
            >
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <form class="form-submit" action="{{url('admin/roles')}}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Add New Role</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label required">Role Name</label>
                                    <input type="text" class="form-control" name="name"
                                           placeholder="Administrator, Editor, etc."
                                           required/>
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
                                    Add New Role
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
