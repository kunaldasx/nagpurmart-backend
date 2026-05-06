@extends('layouts.main')

@section('title', 'Customer Dashboard')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    @yield('page-title', 'Customer Dashboard')
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        @yield('customer-content')
    </div>
</div>
@endsection 