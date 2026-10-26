<div class="card mb-3" id="rider-availability" data-url="{{ route('seller.orders.rider-availability') }}">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <h3 class="card-title mb-1">Rider availability</h3>
            <div class="text-secondary small">Across your pickup zones; available means active, nearby, recently located, and not already assigned.</div>
        </div>
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
