@php use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['roles_permissions']['active'] ?? ""])

@section('title', __('labels.permissions'))

@section('header_data')
    @php
        $page_title = __('labels.permissions');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.permissions'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        @include('components.page_header', ['title' => "Add Permission to $role->name", 'step' => 2])
        <div class="page-body">
            <div class="row row-cards">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title fs-2">{{__('labels.permissions')}} for: {{$role->name}}</h2>
                    </div>
                    <div class="card-body">
                        <form class="form-submit" method="POST" action="{{route('admin.permissions.store')}}">
                            @csrf
                            <input type="hidden" name="role" value="{{ $role->name }}">
                            @foreach ($permissionModule as $key => $perm)
                                <div class="mb-3">
                                    <div class="form-label fs-3">{{$perm['name']}}</div>
                                    <div>
                                        <label class="form-check form-check-inline">
                                            <input class="form-check-input select-all" type="checkbox" {{ !($canEditThisRole ?? false) ? 'disabled' : '' }}
                                                   data-group-id="{{ $key }}"/>
                                            <span
                                                class="form-check-label">Select All</span>
                                        </label>
                                        @foreach($perm['permissions'] as $permissions)
                                            <label class="form-check form-check-inline">
                                                <input class="form-check-input permission-checkbox" type="checkbox"
                                                       name="permissions[]" {{ !($canEditThisRole ?? false) ? 'disabled' : '' }}
                                                       value="{{ $permissions }}"
                                                       data-group-id="{{ $key }}" {{ in_array($permissions, $rolePermissions) ? 'checked' : '' }}/>
                                                <span
                                                    class="form-check-label">{{ Str::replace($key . ".", " ",$permissions) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            @if(($canEditThisRole ?? false))
                                <button type="submit" class="btn btn-primary">Add Permissions</button>
                            @endif
                            </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
