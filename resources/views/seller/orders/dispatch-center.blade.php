@extends('layouts.seller.app')

@section('title', 'Rider Dispatch')

@section('header_data')
    @php
        $page_title = 'Rider Dispatch';
        $page_pretitle = 'Order operations';
    @endphp
@endsection

@section('seller-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
                <div>
                    <div class="text-secondary small text-uppercase fw-bold">Dispatch control</div>
                    <h2 class="page-title mb-1">Rider dispatch</h2>
                    <div class="text-secondary">Orders are reserved to one active rider within 200 m of the pickup store.</div>
                </div>
            </div>

            @include('seller.orders._rider-availability')
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ hyperAsset('assets/js/seller-rider-availability.js') }}" defer></script>
@endpush
