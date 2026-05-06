@extends('layouts.main')

@section('title', 'Vendor Dashboard')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    @yield('page-title', 'Vendor Dashboard')
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        @yield('vendor-content')
    </div>
</div>
@endsection
