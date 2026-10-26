@extends('layouts.seller.app')

@section('title', 'Order popup users')

@section('header_data')
    @php
        $page_title = 'Order popup users';
        $page_pretitle = 'Seller operations';
    @endphp
@endsection

@section('seller-content')
    <div class="page-wrapper" id="seller-popup-users" data-url="{{ $dataUrl }}">
        <div class="page-body">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
                <div>
                    <div class="text-secondary small text-uppercase fw-bold">Order operations</div>
                    <h2 class="page-title mb-1">System user availability</h2>
                    <div class="text-secondary">Eligible system users receive incoming orders first. You receive an order alert only when no eligible system user is nearby and available.</div>
                </div>
                <button class="btn btn-outline-primary" id="seller-popup-users-refresh" type="button">
                    <i class="ti ti-refresh" aria-hidden="true"></i> Refresh
                </button>
            </div>

            <div class="alert alert-danger d-none" id="seller-popup-users-error" role="alert"></div>
            <div class="row row-cards mb-3">
                <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-secondary">System users</div><div class="h1 mb-0" id="popup-total-count">0</div></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-secondary">Active</div><div class="h1 mb-0 text-success" id="popup-active-count">0</div></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-secondary">Eligible for a new order</div><div class="h1 mb-0 text-primary" id="popup-eligible-count">0</div></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-secondary">Inactive</div><div class="h1 mb-0 text-secondary" id="popup-inactive-count">0</div></div></div></div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>System user</th>
                            <th>Account / login</th>
                            <th>Popup availability</th>
                            <th>Nearest store</th>
                            <th>Current order</th>
                            <th>Push</th>
                        </tr>
                        </thead>
                        <tbody id="seller-popup-users-rows">
                        <tr><td colspan="6" class="text-secondary p-4">Loading system user availability…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="small text-secondary mt-2" id="seller-popup-users-updated"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ hyperAsset('assets/js/seller-order-popup-dashboard.js') }}" defer></script>
@endpush
