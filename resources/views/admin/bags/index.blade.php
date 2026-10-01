@extends('layouts.admin.app', ['page' => 'bags'])

@section('title', __('labels.bag_inventory'))

@section('header_data')
    @php
        $page_title = __('labels.bag_inventory');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@section('admin-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <h3 class="card-title mb-0">Bag inventory across sellers</h3>
                    <div class="d-flex gap-2">
                        <select id="admin-bag-status" class="form-select" aria-label="Filter bag assignment status">
                            <option value="">All bags</option><option value="available">Available</option><option value="assigned">Assigned</option>
                        </select>
                        <input id="admin-bag-search" class="form-control" type="search" placeholder="Search barcode">
                        <button id="admin-bag-refresh" type="button" class="btn btn-outline-secondary">Refresh</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Barcode</th><th>Seller</th><th>Status</th><th>Order</th><th>Assigned</th></tr></thead>
                        <tbody id="admin-bag-list"><tr><td colspan="5" class="text-secondary">Loading…</td></tr></tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <span id="admin-bag-total" class="text-secondary"></span>
                    <div class="btn-list"><button id="admin-bag-prev" class="btn btn-outline-secondary btn-sm" type="button">Previous</button><button id="admin-bag-next" class="btn btn-outline-secondary btn-sm" type="button">Next</button></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let page = 1;
    let lastPage = 1;
    const escapeHtml = (value) => $('<div>').text(value || '').html();
    const loadBags = () => axios.get(`{{ route('admin.bags.data') }}?page=${page}&status=${encodeURIComponent($('#admin-bag-status').val())}&search=${encodeURIComponent($('#admin-bag-search').val())}`)
        .then(({data}) => {
            const payload = data.data;
            lastPage = payload.last_page;
            $('#admin-bag-total').text(`${payload.total} bags`);
            $('#admin-bag-list').html(payload.items.length ? payload.items.map((bag) => `<tr><td class="font-monospace">${escapeHtml(bag.barcode)}</td><td>${escapeHtml(bag.seller?.name)}</td><td><span class="badge ${bag.status === 'assigned' ? 'bg-blue-lt' : 'bg-green-lt'}">${escapeHtml(bag.status)}</span></td><td>${escapeHtml(bag.order_number || (bag.seller_order_id ? `Seller order #${bag.seller_order_id}` : '—'))}</td><td>${escapeHtml(bag.assigned_at || '—')}</td></tr>`).join('') : '<tr><td colspan="5" class="text-secondary">No bags found.</td></tr>');
            $('#admin-bag-prev').prop('disabled', page <= 1);
            $('#admin-bag-next').prop('disabled', page >= lastPage);
        })
        .catch(() => $('#admin-bag-list').html('<tr><td colspan="5" class="text-danger">Could not load bags.</td></tr>'));
    $('#admin-bag-refresh').on('click', () => { page = 1; loadBags(); });
    $('#admin-bag-status').on('change', () => { page = 1; loadBags(); });
    $('#admin-bag-search').on('search', () => { page = 1; loadBags(); });
    $('#admin-bag-prev').on('click', () => { page = Math.max(1, page - 1); loadBags(); });
    $('#admin-bag-next').on('click', () => { page = Math.min(lastPage, page + 1); loadBags(); });
    loadBags();
});
</script>
@endpush