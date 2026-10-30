@php use App\Enums\DateRangeFilterEnum;use App\Enums\Order\OrderItemStatusEnum;use App\Enums\Order\OrderStatusEnum;use Illuminate\Support\Str; @endphp
@extends('layouts.seller.app', ['page' => $menuSeller['orders']['active'] ?? ""])

@section('title', __('labels.orders'))

@section('header_data')
    @php
        $page_title = __('labels.orders');
        $page_pretitle = __('labels.seller') . " " . __('labels.orders');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.orders'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.order_items') }} <span class="order-count"></span></h3>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <div class="btn-group" role="group" aria-label="Order type">
                                            <button type="button" class="btn btn-primary order-mode-tab" data-order-mode="regular" data-pending-url="{{ route('seller.orders.pending-regular') }}">Regular <span class="badge bg-white text-primary ms-1" id="regular-order-count">0</span></button>
                                            <button type="button" class="btn btn-outline-primary order-mode-tab" data-order-mode="wholesale">Wholesale <span class="badge bg-primary text-white ms-1" id="wholesale-order-count">0</span></button>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select text-capitalize" id="statusFilter">
                                            <option value="">{{ __('labels.status') }}</option>
                                            @foreach(OrderItemStatusEnum::values() as $value)
                                                <option
                                                    value="{{$value}}">{{Str::replace("_", " ", $value)}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="rangeFilter">
                                            <option value="">{{ __('labels.date_range') }}</option>
                                            @foreach(DateRangeFilterEnum::values() as $value)
                                                <option
                                                    value="{{$value}}">{{Str::replace("_", " ", $value)}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <a class="btn btn-outline-success" href="{{ route('seller.orders.dispatch-center') }}">
                                            <i class="ti ti-route" aria-hidden="true"></i>
                                            Rider dispatch
                                        </a>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#bulkInvoiceModal">
                                            <i class="ti ti-file-type-pdf" aria-hidden="true"></i>
                                            Download invoices PDF
                                        </button>
                                    </div>
                                    @php
                                        $currentSeller = auth()->user()?->seller();
                                        $isMainSeller = $currentSeller && (int) $currentSeller->user_id === (int) auth()->id();
                                    @endphp
                                    @if($isMainSeller)
                                        <div class="col-auto">
                                            <a class="btn btn-outline-primary" href="{{ route('seller.orders.popup-users') }}">
                                                <i class="ti ti-users" aria-hidden="true"></i>
                                                System user availability
                                            </a>
                                        </div>
                                    @endif
                                    <div class="col-auto">
                                        <button class="btn btn-outline-primary" id="refresh">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                                <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                            </svg>
                                            {{ __('labels.refresh') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row w-full">
                                <x-datatable id="orders-table" :columns="$columns"
                                             route="{{ route('seller.orders.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="bulkInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" method="GET" action="{{ route('seller.orders.invoices.bulk') }}" target="_blank">
                <div class="modal-header">
                    <h5 class="modal-title">Download order invoices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary">Download your store's order invoices placed within the selected dates in one PDF.</p>
                    <div class="mb-3">
                        <label class="form-label" for="bulk-invoice-start-date">Start date</label>
                        <input class="form-control" id="bulk-invoice-start-date" type="date" name="start_date"
                               value="{{ now()->subDays(6)->toDateString() }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="bulk-invoice-end-date">End date</label>
                        <input class="form-control" id="bulk-invoice-end-date" type="date" name="end_date"
                               value="{{ now()->toDateString() }}" required>
                    </div>
                    <div>
                        <label class="form-label" for="bulk-invoice-status">Order status</label>
                        <select class="form-select text-capitalize" id="bulk-invoice-status" name="status">
                            <option value="">All statuses</option>
                            @foreach(OrderStatusEnum::orderFilters() as $value)
                                <option value="{{ $value }}">{{ Str::replace('_', ' ', $value) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-link" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">Download PDF</button>
                </div>
            </form>
        </div>
    </div>

    @include('seller.orders.partials.order-accept-modal')
    @include('seller.orders.partials.order-preparing-modal')
    @include('seller.orders.partials.order-reject-modal')

@endsection

@push('scripts')
    <script src="{{hyperAsset('assets/js/order.js')}}" defer></script>
@endpush
