@extends('layouts.admin.guest')

@section('title', __('labels.subscription_feature'))

@section('content')
    <div>
        <div class="page page-center">
            <div class="container-fluid py-4">
                <div class="text-center mb-4">

                    <!-- LOGO -->
                    <a href="." class="navbar-brand navbar-brand-autodark">
                        @if(($systemSettings['demoMode'] ?? false))
                            <img src="{{ asset('logos/hyper-local-logo.png') }}"
                                 alt="{{ $systemSettings['appName'] ?? '' }}" width="150px">
                        @else
                            <img
                                src="{{ !empty($systemSettings['logo']) ? $systemSettings['logo'] : asset('logos/hyper-local-logo.png') }}"
                                alt="{{ $systemSettings['appName'] ?? '' }}" width="150px">
                        @endif
                    </a>
                    @include('components.subscription-feature', ['settings' => $settings])

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ hyperAsset('assets/js/subscription-feature.js') }}" defer></script>
@endpush
