<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')
        | {{ !empty($systemSettings['appName']) ? $systemSettings['appName'] : config('app.name') }}</title>
    <link rel="icon" href="{{ $systemSettings['favicon'] ?? asset('logos/hyper-local-favicon.png') }}"
          sizes="image/x-icon">
    @include('layouts.partials._head')
</head>

<body class="layout-fluid">
<input type="hidden" name="base_url" id="base_url" value="{{url('/')}}">
<input type="hidden" name="user_id" id="user_id" value="{{$user->id ?? ""}}">
<input type="hidden" name="panel" id="panel" data-panel="{{ $panel ?? 'admin' }}">
<input type="hidden" id="selected-currency-symbol" value="{{ $systemSettings['currencySymbol'] ?? '$' }}">
<script src="{{asset('assets/theme/js/tabler-theme.min.js')}}"></script>
<div class="page overflow-hidden">
    @include('layouts.partials._sidebar')
    <div class="page-wrapper">

        @include('layouts.partials._alerts')
        @yield('content')

        @include('layouts.partials._footer')
    </div>
</div>

@include('layouts.partials._scripts')
@stack('script')
{{--<script src="https://infinitietech.com/js/newsletter-popup.js?v={{time()}}`"></script>--}}
</body>

</html>
