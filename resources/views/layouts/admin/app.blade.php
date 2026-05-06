@extends('layouts.main')

{{-- @section('title', 'Admin Dashboard') --}}

@section('header_data')
    @php
        $page_title = $page_title ?? 'Admin Dashboard';
        $page_pretitle = $page_pretitle ?? 'Overview';
    @endphp
@endsection
@section('content')
    @include('layouts.partials._header', [
        'page_title' => $page_title ?? 'Admin Dashboard',
        'page_pretitle' => $page_pretitle ?? 'Overview',
    ])
    <div class="page-body">
        <div class="container-xl">
            @yield('admin-content')
        </div>
    </div>
@endsection
