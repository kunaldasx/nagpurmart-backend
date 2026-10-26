<div class="card mb-3 d-none" id="seller-popup-users" data-url="{{ route('seller.orders.popup-users.data') }}">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h3 class="card-title mb-1">System user availability</h3>
            <div class="text-secondary">Eligible system users receive incoming orders first. You receive an order alert only when no eligible system user is nearby and available.</div>
        </div>
        <button class="btn btn-outline-primary" id="seller-popup-users-refresh" type="button">
            <i class="ti ti-refresh" aria-hidden="true"></i> Refresh
        </button>
    </div>
    <div class="card-body">
        <div class="alert alert-danger d-none" id="seller-popup-users-error" role="alert"></div>
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-secondary">System users</div><div class="h1 mb-0" id="popup-total-count">0</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-secondary">Active</div><div class="h1 mb-0 text-success" id="popup-active-count">0</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-secondary">Eligible for a new order</div><div class="h1 mb-0 text-primary" id="popup-eligible-count">0</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-secondary">Inactive</div><div class="h1 mb-0 text-secondary" id="popup-inactive-count">0</div></div></div></div>
        </div>

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
        <div class="small text-secondary mt-2" id="seller-popup-users-updated"></div>
    </div>
</div>
