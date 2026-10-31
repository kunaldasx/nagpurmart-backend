@extends($isAdmin ? 'layouts.admin.app' : 'layouts.seller.app', [
    'page' => $isAdmin ? ($menuAdmin['products']['active'] ?? 'products') : ($menuSeller['products']['active'] ?? 'products'),
    'sub_page' => 'expiry_batches',
])

@section('title', 'Expiry batch management')

@section('header_data')
    @php
        $page_title = 'Expiry batch management';
        $page_pretitle = $isAdmin ? 'Admin inventory' : 'Seller inventory';
    @endphp
@endsection

@section($isAdmin ? 'admin-content' : 'seller-content')
    <div class="page-wrapper">
        <div class="page-body">
            @if(session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Please review the form:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row row-cards mb-3">
                <div class="col-sm-6 col-lg">
                    <div class="card card-sm"><div class="card-body">
                        <div class="text-secondary">Expired stock</div>
                        <div class="h2 mb-0">{{ number_format($summary['expired_units']) }} units</div>
                        <div class="text-secondary">{{ $summary['expired_batches'] }} batches need review</div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-lg">
                    <div class="card card-sm"><div class="card-body">
                        <div class="text-secondary">Expiring in {{ $days }} days</div>
                        <div class="h2 mb-0">{{ number_format($summary['expiring_units']) }} units</div>
                        <div class="text-secondary">{{ $summary['expiring_batches'] }} batches to rotate</div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-lg">
                    <div class="card card-sm"><div class="card-body">
                        <div class="text-secondary">Safe batches</div>
                        <div class="h2 mb-0">{{ number_format($summary['safe_batches']) }}</div>
                        <div class="text-secondary">Beyond the warning window</div>
                    </div></div>
                </div>
                <div class="col-sm-6 col-lg">
                    <div class="card card-sm"><div class="card-body">
                        <div class="text-secondary">Stock without expiry tracking</div>
                        <div class="h2 mb-0">{{ number_format($summary['untracked_units']) }} units</div>
                        <div class="text-secondary">{{ number_format($summary['untracked_variants']) }} product variants</div>
                    </div></div>
                </div>
            </div>

            <div class="alert alert-info" role="note">
                Expiry alerts are informational. Expired quantities are not automatically deducted or blocked from sale.
                Review the affected batches and record any stock correction below to keep on-hand inventory accurate.
            </div>

            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Find a product to receive</h3>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route($baseRoute . '.index') }}" class="row g-2 align-items-end">
                                @if($isAdmin)
                                    <div class="col-md-3">
                                        <label class="form-label" for="seller-filter">Seller</label>
                                        <select class="form-select" id="seller-filter" name="seller_id">
                                            <option value="">All sellers</option>
                                            @foreach($sellers as $seller)
                                                <option value="{{ $seller->id }}" @selected((string) ($filters['seller_id'] ?? '') === (string) $seller->id)>
                                                    {{ $seller->user?->name ?? 'Seller #' . $seller->id }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-md-3">
                                    <label class="form-label" for="store-filter">Store</label>
                                    <select class="form-select" id="store-filter" name="store_id">
                                        <option value="">All stores</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>
                                                {{ $store->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="expiry-search">Product, variant, store, or batch</label>
                                    <input class="form-control" id="expiry-search" name="search" value="{{ $search }}" minlength="2" placeholder="Type at least 2 characters">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary w-100" type="submit">Search</button>
                                </div>
                                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'all' }}">
                                <input type="hidden" name="days" value="{{ $days }}">
                            </form>

                            @if(mb_strlen($search) >= 2)
                                @if($variants->isNotEmpty())
                                    <form method="POST" action="{{ route($baseRoute . '.receive') }}" class="row g-2 align-items-end border-top mt-3 pt-3">
                                        @csrf
                                        <div class="col-lg-5">
                                            <label class="form-label" for="store-product-variant">Product / store</label>
                                            <select class="form-select" id="store-product-variant" name="store_product_variant_id" required>
                                                <option value="">Select product and store</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}">
                                                        {{ $variant->productVariant?->product?->title }}
                                                        @if($variant->productVariant?->title) — {{ $variant->productVariant->title }}@endif
                                                        · {{ $variant->store?->name }} · On hand: {{ $variant->stock }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-4 col-lg-2">
                                            <label class="form-label" for="batch-quantity">Units received</label>
                                            <input class="form-control" id="batch-quantity" name="quantity" type="number" min="1" step="1" required>
                                        </div>
                                        <div class="col-sm-4 col-lg-2">
                                            <label class="form-label" for="batch-expiry-date">Expiry date</label>
                                            <input class="form-control" id="batch-expiry-date" name="expiry_date" type="date" min="{{ today()->toDateString() }}" required>
                                        </div>
                                        <div class="col-sm-4 col-lg-3">
                                            <label class="form-label" for="batch-code">Batch / lot code <span class="text-secondary">(optional)</span></label>
                                            <input class="form-control" id="batch-code" name="batch_code" maxlength="100">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox" name="adjust_stock" value="1" checked>
                                                <span class="form-check-label">Add these units to on-hand stock (new delivery)</span>
                                            </label>
                                            <div class="form-hint">Uncheck only when assigning an expiry date to units already included in current stock.</div>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-success" type="submit">Record batch</button>
                                        </div>
                                    </form>
                                @else
                                    <div class="alert alert-warning mt-3 mb-0">No matching stocked product variants found. Try a different product or store name.</div>
                                @endif
                            @else
                                <div class="form-hint mt-2">Search for a product or store to choose the stock item for a new batch.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Expiry batches</h3>
                            <div class="card-actions">
                                <form method="GET" action="{{ route($baseRoute . '.index') }}" class="d-flex gap-2">
                                    @foreach(['seller_id', 'store_id', 'search'] as $filterName)
                                        @if(!empty($filters[$filterName]))
                                            <input type="hidden" name="{{ $filterName }}" value="{{ $filters[$filterName] }}">
                                        @endif
                                    @endforeach
                                    <select class="form-select" name="status" aria-label="Filter by expiry status">
                                        @foreach(['all' => 'All batches', 'expired' => 'Expired', 'expiring' => 'Expiring soon', 'safe' => 'Safe', 'depleted' => 'Depleted'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select class="form-select" name="days" aria-label="Near-expiry warning window">
                                        @foreach([7, 15, 30, 60] as $window)
                                            <option value="{{ $window }}" @selected($days === $window)>{{ $window }} days</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-outline-primary" type="submit">Apply</button>
                                </form>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                <tr>
                                    @if($isAdmin)<th>Seller / store</th>@endif
                                    <th>Product / variant</th>
                                    <th>Batch</th>
                                    <th>Expiry</th>
                                    <th>Status</th>
                                    <th>Remaining / received</th>
                                    <th>Current on hand</th>
                                    <th>Update batch</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($batches as $batch)
                                    @php
                                        $daysLeft = (int) today()->diffInDays($batch->expiry_date, false);
                                        $status = $batch->quantity_remaining === 0
                                            ? 'depleted'
                                            : ($batch->expiry_date->lt(today())
                                                ? 'expired'
                                                : ($daysLeft <= $days ? 'expiring' : 'safe'));
                                        $badge = ['expired' => 'bg-red-lt', 'expiring' => 'bg-yellow-lt', 'safe' => 'bg-green-lt', 'depleted' => 'bg-secondary-lt'][$status];
                                    @endphp
                                    <tr>
                                        @if($isAdmin)
                                            <td>
                                                {{ $batch->storeProductVariant?->store?->seller?->user?->name ?? 'Seller' }}<br>
                                                <span class="text-secondary">{{ $batch->storeProductVariant?->store?->name }}</span>
                                            </td>
                                        @endif
                                        <td>
                                            <strong>{{ $batch->storeProductVariant?->productVariant?->product?->title ?? 'Product unavailable' }}</strong><br>
                                            <span class="text-secondary">{{ $batch->storeProductVariant?->productVariant?->title }}</span>
                                        </td>
                                        <td>{{ $batch->batch_code ?: '—' }}</td>
                                        <td>
                                            {{ $batch->expiry_date->format('d M Y') }}<br>
                                            @if($status === 'expired')
                                                <span class="text-danger">{{ abs($daysLeft) }} days past expiry</span>
                                            @elseif($status === 'expiring')
                                                <span class="text-warning">{{ $daysLeft === 0 ? 'Expires today' : "In {$daysLeft} days" }}</span>
                                            @elseif($status === 'safe')
                                                <span class="text-secondary">{{ $daysLeft }} days left</span>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $badge }}">{{ ucfirst($status) }}</span></td>
                                        <td>{{ number_format($batch->quantity_remaining) }} / {{ number_format($batch->quantity_received) }}</td>
                                        <td>{{ number_format((int) $batch->storeProductVariant?->stock) }}</td>
                                        <td>
                                            <details>
                                                <summary class="btn btn-sm btn-outline-primary">Edit</summary>
                                                <form method="POST" action="{{ route($baseRoute . '.update', $batch->id) }}" class="mt-2" style="min-width: 240px;">
                                                    @csrf
                                                    <label class="form-label">Batch / lot code</label>
                                                    <input class="form-control form-control-sm mb-2" name="batch_code" maxlength="100" value="{{ $batch->batch_code }}">
                                                    <label class="form-label">Expiry date</label>
                                                    <input class="form-control form-control-sm mb-2" type="date" name="expiry_date" value="{{ $batch->expiry_date->toDateString() }}" required>
                                                    <label class="form-label">Remaining units</label>
                                                    <input class="form-control form-control-sm mb-2" type="number" name="quantity_remaining" min="0" value="{{ $batch->quantity_remaining }}" required>
                                                    <label class="form-label">Reason for stock change</label>
                                                    <input class="form-control form-control-sm mb-2" name="reason" maxlength="255" placeholder="e.g. expired / damaged / counted">
                                                    <label class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" name="adjust_stock" value="1" checked>
                                                        <span class="form-check-label">Adjust on-hand stock</span>
                                                    </label>
                                                    <button class="btn btn-sm btn-primary" type="submit">Save changes</button>
                                                </form>
                                            </details>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isAdmin ? 8 : 7 }}" class="text-center text-secondary py-4">
                                            No expiry batches match these filters. Search for a product above to record its first batch.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($batches->hasPages())
                            <div class="card-footer">{{ $batches->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
