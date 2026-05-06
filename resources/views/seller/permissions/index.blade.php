@php use Illuminate\Support\Str; @endphp
@extends('layouts.seller.app', [
    'page' => $menuSeller['roles_permissions']['active'] ?? "",
])

@section('title', __('labels.permissions'))

@section('header_data')
    @php
        $page_title = __('labels.permissions');
        $page_pretitle = __('labels.roles_permissions');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.roles_permissions'), 'url' => route('seller.roles.index')],
        ['title' => __('labels.permissions'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="page-wrapper">
        @include('components.page_header', ['title' => "Add Permission to $role->name", 'step' => 2])
        <div class="page-body">
            <div class="container-xl">
                <div class="row row-cards">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title fs-2">Permissions for: {{$role->name}}</h2>
                        </div>
                        <div class="card-body">
                            <form class="form-submit" method="POST" action="{{route('seller.permissions.store')}}">
                                @csrf
                                <input type="hidden" name="role" value="{{ $role->name }}">
                                @foreach ($permissionModule as $key => $perm)
                                    <div class="mb-3">
                                        <div class="form-label fs-3">{{$perm['name']}}</div>
                                        <div>
                                            <label class="form-check form-check-inline">
                                                <input class="form-check-input select-all" type="checkbox" {{ !($canEditThisRole ?? true) ? 'disabled' : '' }}
                                                       data-group-id="{{ $key }}"/>
                                                <span
                                                    class="form-check-label">Select All</span>
                                            </label>
                                            @foreach($perm['permissions'] as $permissions)
                                                <label class="form-check form-check-inline">
                                                    <input class="form-check-input permission-checkbox" type="checkbox"
                                                           name="permissions[]" {{ !($canEditThisRole ?? true) ? 'disabled' : '' }}
                                                           value="{{ $permissions }}"
                                                           data-group-id="{{ $key }}" {{ in_array($permissions, $rolePermissions) ? 'checked' : '' }}/>
                                                    <span
                                                        class="form-check-label">{{ Str::replace($key . ".", " ",$permissions) }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                @if(($canEditThisRole ?? true))
                                    <button type="submit" class="btn btn-primary">Add Permissions</button>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
