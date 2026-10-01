@extends('layouts.seller.app', ['page' => 'bags'])

@section('title', __('labels.bag_inventory'))

@section('header_data')
    @php
        $page_title = __('labels.bag_inventory');
        $page_pretitle = __('labels.seller');
    @endphp
@endsection

@section('seller-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Add bag barcodes</h3></div>
                        <div class="card-body">
                            <label class="form-label" for="bag-bulk-input">Paste or type barcodes, one per line</label>
                            <textarea id="bag-bulk-input" class="form-control font-monospace" rows="5" placeholder="BAG-000001&#10;BAG-000002&#10;BAG-000003"></textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2 gap-2 flex-wrap">
                                <small class="text-secondary">Commas and semicolons are accepted too. Existing barcodes are reported and left unchanged.</small>
                                <button id="bag-bulk-submit" class="btn btn-primary" type="button">Add barcodes</button>
                            </div>
                            <div id="bag-import-result" class="mt-3" role="status" aria-live="polite"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <h3 class="card-title mb-0">Your bags</h3>
                            <div class="d-flex gap-2">
                                <select id="bag-status-filter" class="form-select" aria-label="Filter bags by assignment">
                                    <option value="">All bags</option>
                                    <option value="available">Available</option>
                                    <option value="assigned">Assigned</option>
                                </select>
                                <button id="bag-refresh" class="btn btn-outline-secondary" type="button" aria-label="Refresh bag list">Refresh</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>Barcode</th><th>Status</th><th>Order</th><th>Added</th><th class="w-1">Actions</th></tr></thead>
                                <tbody id="bag-list"><tr><td colspan="5" class="text-secondary">Loading…</td></tr></tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <span id="bag-total" class="text-secondary"></span>
                            <div class="btn-list"><button id="bag-prev" class="btn btn-outline-secondary btn-sm" type="button">Previous</button><button id="bag-next" class="btn btn-outline-secondary btn-sm" type="button">Next</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="edit-bag-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <form id="edit-bag-form">
                    <div class="modal-header">
                        <h3 class="modal-title">Edit bag barcode</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="edit-bag-barcode">Barcode</label>
                        <input id="edit-bag-barcode" class="form-control font-monospace" type="text" maxlength="255" required autocomplete="off">
                        <div id="edit-bag-error" class="text-danger small mt-2" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                        <button id="edit-bag-submit" type="submit" class="btn btn-primary">Save barcode</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="delete-bag-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-status bg-danger"></div>
                <div class="modal-body">
                    <h3 class="modal-title">Delete bag?</h3>
                    <p class="text-secondary mt-2 mb-0">This removes <span id="delete-bag-barcode" class="font-monospace fw-bold"></span> from your available bag inventory. This cannot be undone.</p>
                    <div id="delete-bag-error" class="text-danger small mt-2" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                    <button id="delete-bag-submit" type="button" class="btn btn-danger">Delete bag</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ hyperAsset('assets/js/seller-bags.js') }}" defer></script>
@endpush