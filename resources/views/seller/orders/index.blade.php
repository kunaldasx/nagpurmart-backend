@php use App\Enums\DateRangeFilterEnum;use App\Enums\Order\OrderItemStatusEnum;use Illuminate\Support\Str; @endphp
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
            <div class="card mb-3" id="rider-availability" data-url="{{ route('seller.orders.rider-availability') }}">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="card-title mb-1">Rider availability</h3>
                        <div class="text-secondary small">Across your pickup zones; available means active, nearby, recently located, and not already assigned.</div>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" type="button" id="rider-availability-refresh" title="Refresh rider availability">
                        <i class="ti ti-refresh" aria-hidden="true"></i>
                        Refresh
                    </button>
                </div>
                <div class="card-body pt-2">
                    <div class="alert alert-warning d-none mb-3" id="rider-availability-error" role="alert"></div>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-secondary">Active riders</div>
                                <div class="h2 mb-0" id="active-rider-count">0</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-secondary">Available in pickup radius</div>
                                <div class="h2 mb-0 text-success" id="available-rider-count">0</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-secondary">Inactive riders</div>
                                <div class="h2 mb-0 text-secondary" id="inactive-rider-count">0</div>
                            </div>
                        </div>
                    </div>
                    <details class="mt-3">
                        <summary class="fw-semibold">View rider details</summary>
                        <div class="table-responsive mt-3">
                            <table class="table table-vcenter table-sm mb-1">
                                <thead>
                                <tr>
                                    <th>Rider</th>
                                    <th>Status</th>
                                    <th>Zone</th>
                                    <th>Nearest pickup</th>
                                    <th>Last location report</th>
                                    <th>Dispatch eligibility</th>
                                </tr>
                                </thead>
                                <tbody id="rider-availability-rows">
                                <tr><td colspan="6" class="text-secondary">Loading rider details…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </details>
                    <div class="small text-secondary mt-2" id="rider-availability-updated"></div>
                </div>
            </div>

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

    @include('seller.orders.partials.order-accept-modal')
    @include('seller.orders.partials.order-preparing-modal')
    @include('seller.orders.partials.order-reject-modal')

@endsection

@push('scripts')
    <script src="{{hyperAsset('assets/js/order.js')}}" defer></script>
    <script>
        (() => {
            const root = document.getElementById('rider-availability');
            if (!root) return;

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[character]));
            const reasonLabels = {
                inactive: 'Inactive',
                not_verified: 'Not verified',
                no_fcm_token: 'App notification token missing',
                location_stale: 'Location is older than 2 minutes',
                outside_pickup_radius: 'Outside 200 m pickup radius',
                pending_offer: 'Has a pending order offer',
                active_delivery: 'Already on a delivery',
            };

            const loadRiderAvailability = async () => {
                const response = await fetch(root.dataset.url, {headers: {'Accept': 'application/json'}});
                const body = await response.json();
                if (!response.ok || !body.success) throw new Error(body.message || 'Could not load rider availability.');

                const summary = body.data.summary;
                document.getElementById('active-rider-count').textContent = summary.active_riders;
                document.getElementById('available-rider-count').textContent = summary.available_riders_in_radius;
                document.getElementById('inactive-rider-count').textContent = summary.inactive_riders;

                const rows = document.getElementById('rider-availability-rows');
                rows.innerHTML = body.data.riders.length ? body.data.riders.map((rider) => {
                    const statusClass = rider.status === 'active' ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary';
                    const availability = rider.is_available_in_radius
                        ? '<span class="badge bg-success-lt text-success">Available</span>'
                        : `<span class="text-secondary">${escapeHtml(reasonLabels[rider.unavailable_reason] || 'Unavailable')}</span>`;
                    const distance = rider.nearest_pickup_distance_m === null
                        ? 'No recent location'
                        : `${escapeHtml(rider.nearest_pickup_distance_m)} m`;
                    const lastSeen = rider.location_age_seconds === null
                        ? 'Never reported'
                        : `${escapeHtml(rider.location_age_seconds)} sec ago`;
                    return `<tr>
                        <td>${escapeHtml(rider.name || ('Rider #' + rider.id))} <span class="text-secondary">#${escapeHtml(rider.id)}</span></td>
                        <td><span class="badge ${statusClass}">${escapeHtml(rider.status)}</span></td>
                        <td>${escapeHtml(rider.delivery_zone || '—')}</td>
                        <td>${distance}</td>
                        <td>${lastSeen}</td>
                        <td>${availability}</td>
                    </tr>`;
                }).join('') : '<tr><td colspan="6" class="text-secondary">No riders are registered in your pickup zones.</td></tr>';
                document.getElementById('rider-availability-updated').textContent = `Updated ${new Date().toLocaleTimeString()}. Refreshes automatically every 30 seconds.`;
                document.getElementById('rider-availability-error').classList.add('d-none');
            };

            const refresh = async () => {
                try {
                    await loadRiderAvailability();
                } catch (error) {
                    const alert = document.getElementById('rider-availability-error');
                    alert.textContent = error.message;
                    alert.classList.remove('d-none');
                }
            };

            document.getElementById('rider-availability-refresh').addEventListener('click', refresh);
            refresh();
            window.setInterval(refresh, 30000);
        })();
    </script>
@endpush
