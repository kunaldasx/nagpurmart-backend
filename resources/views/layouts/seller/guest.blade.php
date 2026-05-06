<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ !empty($systemSettings['favicon']) ? $systemSettings['favicon'] : "" }}"
          sizes="image/x-icon">
    <title>@yield('title', config('app.name'))</title>

    @include('layouts.partials._head')
</head>
<input type="hidden" name="base_url" id="base_url" value="{{url('/')}}">
<input type="hidden" name="user_id" id="user_id" value="{{$user->id ?? ""}}">
<input type="hidden" name="panel" id="panel" data-panel="{{ $panel ?? 'seller' }}">
<input type="hidden" id="selected-currency-symbol" value="{{ $systemSettings['currencySymbol'] ?? '$' }}">

<!-- BEGIN GLOBAL THEME SCRIPT -->
<script src="{{hyperAsset('assets/theme/js/tabler-theme.min.js')}}"></script>
<body>
<div class="page-wrapper">
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-body">
                @yield('content')
            </div>
            @yield('footer')
        </div>
    </div>
</div>
@include('layouts.partials._scripts')
@stack('scripts')
</body>

</html>
