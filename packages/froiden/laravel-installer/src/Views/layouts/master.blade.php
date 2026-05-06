<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{config('app.name')}}</title>
    <link rel="icon" type="image/png" href="{{ asset('logos/hyper-local-favicon.png') }}" sizes="16x16"/>
    <link rel="icon" type="image/png" href="{{ asset('logos/hyper-local-favicon.png') }}" sizes="32x32"/>
    <link rel="icon" type="image/png" href="{{ asset('logos/hyper-local-favicon.png') }}" sizes="96x96"/>

    <link href="{{ asset('installer/css/style.min.css') }}" rel="stylesheet"/>
    @include('layouts.partials._head')
    <script src="{{hyperAsset('assets/theme/js/tabler-theme.min.js')}}"></script>
    @yield('style')
    <style>
        .form-control {
            font-size: 0.875rem !important;
        }

        .alert-danger {
            color: var(--tblr-red);
            font-size: inherit;
            font-weight: 500;
        }

        .alert-success {
            color: var(--tblr-green);
            font-size: inherit;
            font-weight: 500;
        }
    </style>

</head>
<body>
<div class="master" style="
    background-image: none;
">
    <div class="box shadow-sm border rounded-4">
        <div class="header" style="
                background: none;
            ">
            <img src="{{ asset('logos/hyper-local-logo.png') }}" class="mb-4" width="170px" alt="">
            <h1 class="header__title text-dark">HyperMart Installer – Hyperlocal Multivendor Commerce Setup</h1>
        </div>
        <ul class="step">
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::final') }}"><i class="step__icon database"></i></li>
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::permissions') }}"><i
                    class="step__icon permissions"></i></li>
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::requirements') }}"><i
                    class="step__icon requirements"></i></li>
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::superAdmin') }}"><i class="step__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="icon icon-tabler icons-tabler-outline icon-tabler-user">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/>
                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                    </svg>
                </i></li>
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::environment') }}"><i class="step__icon update"></i>
            </li>
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::welcome') }}"><i class="step__icon welcome"></i></li>
            <li class="step__divider"></li>
        </ul>
        <div class="main">
            @yield('container')
        </div>
    </div>
</div>
</body>
@yield('scripts')
</html>
