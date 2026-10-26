@extends('layouts.seller.app')

@section('title', 'Rider Dispatch')

@section('header_data')
    @php
        $page_title = 'Rider Dispatch';
        $page_pretitle = 'Order operations';
    @endphp
@endsection

@section('seller-content')
    @php
        $currentSeller = auth()->user()?->seller();
        $isMainSeller = $currentSeller && (int) $currentSeller->user_id === (int) auth()->id();
    @endphp
    <div class="page-wrapper" id="dispatch-center" data-url="{{ $dataUrl }}" data-self-url="{{ route('seller.orders.index') }}">
        <div class="page-body">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
                <div>
                    <div class="text-secondary small text-uppercase fw-bold">Dispatch control</div>
                    <h2 class="page-title mb-1">Rider dispatch</h2>
                    <div class="text-secondary">Orders are reserved to one active rider within 200 m of the pickup store.</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-primary" id="dispatch-refresh" type="button" title="Refresh dispatch status">
                        <i class="ti ti-refresh" aria-hidden="true"></i> Refresh
                    </button>
                </div>
            </div>

            @if($isMainSeller)
                @include('seller.orders._popup-users-dashboard')
            @endif

            <div class="alert alert-warning d-none" id="dispatch-alert" role="alert"></div>
            <div class="row row-cards mb-3">
                <div class="col-sm-4"><div class="card"><div class="card-body"><div class="text-secondary">Ready for dispatch</div><div class="h1 mb-0" id="dispatch-ready-count">0</div></div></div></div>
                <div class="col-sm-4"><div class="card"><div class="card-body"><div class="text-secondary">No nearby riders</div><div class="h1 mb-0 text-warning" id="dispatch-uncovered-count">0</div></div></div></div>
                <div class="col-sm-4"><div class="card"><div class="card-body"><div class="text-secondary">Rider assigned</div><div class="h1 mb-0 text-success" id="dispatch-assigned-count">0</div></div></div></div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Order</th><th>Pickup store</th><th>Rider / nearby</th><th>Dispatch state</th><th class="w-1">Action</th></tr></thead>
                        <tbody id="dispatch-orders"><tr><td colspan="5" class="text-secondary p-4">Loading dispatch status…</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="small text-secondary mt-2" id="dispatch-updated"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const root = document.getElementById('dispatch-center');
            if (!root) return;
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character]));
            const stateLabels = {
                awaiting_rider_acceptance: 'Waiting for rider acceptance',
                rider_assigned: 'Rider accepted',
                no_nearby_riders: 'No active rider in pickup radius',
                waiting_for_dispatch: 'Waiting for rider',
                seller_self_delivery: 'Seller self-delivery',
            };
            const load = async () => {
                const response = await fetch(root.dataset.url, {headers: {'Accept': 'application/json'}});
                const body = await response.json();
                if (!response.ok || !body.success) throw new Error(body.message || 'Could not load dispatch status.');
                const data = body.data;
                document.getElementById('dispatch-ready-count').textContent = data.summary.ready_orders;
                document.getElementById('dispatch-uncovered-count').textContent = data.summary.no_nearby_riders;
                document.getElementById('dispatch-assigned-count').textContent = data.summary.assigned_orders;
                const uncovered = data.orders.filter((order) => order.status === 'no_nearby_riders');
                const alert = document.getElementById('dispatch-alert');
                alert.classList.toggle('d-none', uncovered.length === 0);
                alert.textContent = uncovered.length
                    ? `${uncovered.length} ready order${uncovered.length === 1 ? '' : 's'} have no active rider within 200 m. You can take an order for seller self-delivery.`
                    : '';
                const tbody = document.getElementById('dispatch-orders');
                tbody.innerHTML = data.orders.length ? data.orders.map((order) => {
                    const riderText = order.delivery_boy_name
                        ? `${escapeHtml(order.delivery_boy_name)} (#${escapeHtml(order.delivery_boy_id)})`
                        : `${escapeHtml(order.nearby_available_riders)} available`;
                    const canSelfDeliver = order.status !== 'seller_self_delivery' && order.status !== 'rider_assigned';
                    return `<tr>
                        <td>#${escapeHtml(order.order_number || order.order_id)}</td>
                        <td>${escapeHtml(order.pickup || 'Pickup location missing')}</td>
                        <td>${riderText}</td>
                        <td><span class="badge ${order.status === 'no_nearby_riders' ? 'bg-warning-lt text-warning' : 'bg-blue-lt text-blue'}">${escapeHtml(stateLabels[order.status] || order.status)}</span></td>
                        <td>${canSelfDeliver ? `<button class="btn btn-sm btn-outline-primary" type="button" data-self-deliver="${escapeHtml(order.order_id)}">Self-deliver</button>` : ''}</td>
                    </tr>`;
                }).join('') : '<tr><td colspan="5" class="text-secondary p-4">No prepared bagged orders need dispatch.</td></tr>';
                document.getElementById('dispatch-updated').textContent = `Updated ${new Date().toLocaleTimeString()}. Refreshes automatically every 15 seconds.`;
            };
            const refresh = async () => {
                try {
                    await load();
                } catch (error) {
                    const alert = document.getElementById('dispatch-alert');
                    alert.classList.remove('d-none');
                    alert.textContent = error.message;
                }
            };
            if (window.location.hash === '#seller-popup-users') {
                const dashboard = document.getElementById('seller-popup-users');
                dashboard?.classList.remove('d-none');
                dashboard?.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
            document.getElementById('dispatch-refresh').addEventListener('click', refresh);
            document.getElementById('dispatch-orders').addEventListener('click', async (event) => {
                const button = event.target.closest('[data-self-deliver]');
                if (!button || !window.confirm('Mark this order for seller self-delivery? The reserved rider will be notified to stop ringing.')) return;
                button.disabled = true;
                try {
                    const response = await fetch(`${root.dataset.selfUrl}/${button.dataset.selfDeliver}/self-deliver`, {
                        method: 'POST',
                        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                    });
                    const body = await response.json();
                    if (!response.ok || !body.success) throw new Error(body.message || 'Could not update order.');
                    await refresh();
                } catch (error) {
                    window.alert(error.message);
                    button.disabled = false;
                }
            });
            refresh();
            window.setInterval(refresh, 15000);
        })();
    </script>
    @if($isMainSeller)
        <script src="{{ hyperAsset('assets/js/seller-order-popup-dashboard.js') }}" defer></script>
    @endif
@endpush
