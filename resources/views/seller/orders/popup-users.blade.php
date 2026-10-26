@extends('layouts.seller.app')

@section('title', 'System user availability')

@section('header_data')
    @php
        $page_title = 'System user availability';
        $page_pretitle = 'Seller operations';
    @endphp
@endsection

@section('seller-content')
    <div class="page-wrapper" id="seller-popup-users" data-url="{{ $dataUrl }}">
        <div class="page-body">
            <div class="mb-3">
                <div class="text-secondary small text-uppercase fw-bold">Order operations</div>
                <h2 class="page-title mb-1">System user availability</h2>
                <div class="text-secondary">Eligible system users receive incoming orders first. You receive an order alert only when no eligible system user is nearby and available.</div>
            </div>
            @include('seller.orders._popup-users-dashboard')
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ hyperAsset('assets/js/seller-order-popup-dashboard.js') }}" defer></script>
@endpush
