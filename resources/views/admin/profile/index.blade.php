@extends('layouts.admin.app')

@section('title', __('labels.profile'))

@section('header_data')
    @php
        $page_title = __('labels.profile');
        $page_pretitle = __('labels.manage_your_profile');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.profile'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-body">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="col">
                            <h2 class="page-title">{{ __('labels.profile') }}</h2>
                            <x-breadcrumb :items="$breadcrumbs"/>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                <a href="{{ route('admin.profile.edit') }}" class="btn btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/>
                                        <path
                                            d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/>
                                        <path d="M16 5l3 3"/>
                                    </svg>
                                    {{ __('labels.edit_profile') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="mb-3">
                                    @if($user->profile_image)
                                        <img src="{{ $user->profile_image }}" alt="Profile Image"
                                             class="avatar avatar-xl rounded-circle object-cover">
                                    @else
                                        <div class="avatar avatar-xl rounded-circle bg-primary text-white">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.name') }}</label>
                                            <div class="form-control-plaintext">{{ $user->name }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.email') }}</label>
                                            <div class="form-control-plaintext">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.mobile') }}</label>
                                            <div class="form-control-plaintext">{{ $user->mobile }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.user_id') }}</label>
                                            <div class="form-control-plaintext">{{ $user->id }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.created_at') }}</label>
                                            <div
                                                class="form-control-plaintext">{{ $user->created_at->format('M d, Y') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.last_updated') }}</label>
                                            <div
                                                class="form-control-plaintext">{{ $user->updated_at->format('M d, Y') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
