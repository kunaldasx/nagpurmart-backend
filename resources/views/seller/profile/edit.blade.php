@extends('layouts.seller.app')

@section('title', __('labels.edit_profile'))

@section('header_data')
    @php
        $page_title = __('labels.edit_profile');
        $page_pretitle = __('labels.update_your_profile_information');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.profile'), 'url' => route('seller.profile.index')],
        ['title' => __('labels.edit_profile'), 'url' => null],
    ];
@endphp

@section('seller-content')
    <div class="page-body">
        <div class="row row-cards">
            <div class="col-12">
                <form class="form-submit" action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="card">
                        <div class="card-header">
                            <div class="col">
                                <h2 class="page-title">{{ __('labels.edit_profile') }}</h2>
                                <x-breadcrumb :items="$breadcrumbs"/>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('labels.profile_image') }}</label>
                                        <div class="mb-2">
                                            @if($user->profile_image)
                                                <img src="{{ $user->profile_image }}" alt="Profile Image"
                                                     class="avatar avatar-xl rounded-circle object-cover"
                                                     id="profile-preview">
                                            @else
                                                <div class="avatar avatar-xl rounded-circle bg-primary text-white"
                                                     id="profile-preview">
                                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                                </div>
                                            @endif
                                        </div>
                                        <input type="file"
                                               class="form-control"
                                               name="profile_image" accept="image/jpeg,image/png,image/jpg,image/webp">
                                        <small class="form-hint">{{ __('labels.profile_image_hint') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.name') }}</label>
                                                <input type="text"
                                                       class="form-control"
                                                       name="name" value="{{ old('name', $user->name) }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('labels.email') }}</label>
                                                <input type="email" class="form-control" value="{{ $user->email }}"
                                                       disabled>
                                                <small
                                                    class="form-hint">{{ __('labels.email_cannot_be_changed') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('labels.mobile') }}</label>
                                                <input type="number" class="form-control" value="{{ $user->mobile }}"
                                                       disabled>
                                                <small
                                                    class="form-hint">{{ __('labels.mobile_cannot_be_changed') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <div class="d-flex">
                                <a href="{{ route('seller.profile.index') }}"
                                   class="btn btn-link">{{ __('labels.cancel') }}</a>
                                <button type="submit"
                                        class="btn btn-primary ms-auto">{{ __('labels.update_profile') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-12 mt-3">
                <form class="form-submit" action="{{ route('seller.profile.password.update') }}" method="POST">
                    @csrf

                    <div class="card">
                        <div class="card-header">
                            <div class="col">
                                <h2 class="page-title">{{ __('labels.update_password') }}</h2>
                                <x-breadcrumb :items="$breadcrumbs"/>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('labels.current_password') }}</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('labels.new_password') }}</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('labels.confirm_password') }}</label>
                                        <input type="password" class="form-control" name="password_confirmation" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <div class="d-flex">
                                <a href="{{ route('seller.profile.index') }}"
                                   class="btn btn-link">{{ __('labels.cancel') }}</a>
                                <button type="submit"
                                        class="btn btn-primary ms-auto">{{ __('labels.update_password') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
