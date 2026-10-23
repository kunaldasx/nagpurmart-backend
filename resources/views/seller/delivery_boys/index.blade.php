@extends('layouts.seller.app', ['page' => 'delivery_boys'])

@section('title', 'Delivery Boys')

@section('header_data')
    @php
        $page_title = 'Delivery Boys';
        $page_pretitle = 'Seller management';
    @endphp
@endsection

@section('seller-content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <h2 class="page-title">Delivery Boys</h2>
                        <div class="text-secondary mt-1">
                            Password access is approved for {{ $loginDurationHours }} hours. QR validity can be set up to {{ $loginDurationHours }} hours.
                        </div>
                    </div>
                    @if ($canManageRiders)
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#availableRidersModal">
                                    Existing delivery boys <span class="badge bg-primary-lt ms-1">{{ $availableRiders->total() }}</span>
                                </button>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRiderModal">
                                    <i class="ti ti-plus me-1" aria-hidden="true"></i>Add delivery boy
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @if (session('success'))
                    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                @endif

                @if ($canManageRiders)
                    <div class="modal modal-blur fade" id="createRiderModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('seller.delivery-boys.store') }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h3 class="modal-title">Add a delivery boy</h3>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="create_rider_name">Full name</label>
                                                <input id="create_rider_name" name="full_name" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="create_rider_email">Email</label>
                                                <input id="create_rider_email" name="email" type="email" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="create_rider_mobile">Mobile</label>
                                                <input id="create_rider_mobile" name="mobile" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="create_rider_password">Temporary password</label>
                                                <input id="create_rider_password" name="password" type="password" minlength="8" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="create_rider_zone">Delivery zone</label>
                                                <select id="create_rider_zone" name="delivery_zone_id" class="form-select" required>
                                                    <option value="">Select a delivery zone</option>
                                                    @foreach($deliveryZones as $zone)
                                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <p class="text-secondary mb-0">New accounts remain inactive until verified by the platform admin.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                        <button class="btn btn-primary" type="submit">Create delivery boy</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal modal-blur fade" id="availableRidersModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div>
                                        <h3 class="modal-title">Existing delivery boys available to add</h3>
                                        <div class="text-secondary">Claim an unassigned platform rider to manage their seller login access.</div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead><tr><th>Name</th><th>Email</th><th>Delivery zone</th><th>Verification</th><th class="w-1">Action</th></tr></thead>
                                        <tbody>
                                        @forelse ($availableRiders as $rider)
                                            <tr>
                                                <td>{{ $rider->user?->name ?? $rider->full_name }}</td>
                                                <td>{{ $rider->user?->email }}</td>
                                                <td>{{ $rider->deliveryZone?->name ?? '—' }}</td>
                                                <td>{{ str_replace('_', ' ', $rider->verification_status->value) }}</td>
                                                <td>
                                                    <form method="POST" action="{{ route('seller.delivery-boys.claim', $rider->id) }}">
                                                        @csrf
                                                        <button class="btn btn-sm btn-primary" type="submit">Add to my team</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-secondary py-4">No unassigned platform delivery boys are available.</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if ($availableRiders->hasPages())
                                    <div class="modal-footer">{{ $availableRiders->links() }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Your delivery boys</h3>
                    </div>
                    <div class="card-body border-bottom text-secondary">
                        Manage password approval, QR validity, and rider account details from each row.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Verification</th>
                                <th>Password login approval</th>
                                <th>Approval expires</th>
                                <th>QR expires</th>
                                <th class="w-1">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($riders as $rider)
                                <tr>
                                    <td>{{ $rider->user?->name ?? $rider->full_name }}</td>
                                    <td>{{ $rider->user?->email }}</td>
                                    <td>{{ str_replace('_', ' ', $rider->verification_status->value) }}</td>
                                    <td>
                                        @php
                                            $approvalStatus = $rider->login_approval_status;
                                            $approvalIsActive = $approvalStatus === 'approved' && $rider->login_approved_until?->isFuture();
                                            $approvalBadge = $approvalIsActive ? 'bg-green-lt' : ($approvalStatus === 'pending' ? 'bg-yellow-lt' : 'bg-red-lt');
                                            $approvalLabel = $approvalIsActive ? 'Approved' : ($approvalStatus === 'approved' ? 'Expired' : ucfirst($approvalStatus));
                                        @endphp
                                        @if ($canManageRiders)
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge {{ $approvalBadge }}">{{ $approvalLabel }}</span>
                                                <button type="button" class="btn btn-icon btn-sm btn-outline-primary js-rider-approval"
                                                        title="Edit password login approval" aria-label="Edit approval for {{ $rider->full_name }}"
                                                        data-name="{{ $rider->user?->name ?? $rider->full_name }}"
                                                        data-status="{{ $approvalStatus }}"
                                                        data-approved-until="{{ $rider->login_approved_until?->format('Y-m-d H:i') ?? '' }}"
                                                        data-approve-url="{{ route('seller.delivery-boys.approve-login', $rider->id) }}"
                                                        data-disapprove-url="{{ route('seller.delivery-boys.reject-login', $rider->id) }}">
                                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        @else
                                            <span class="badge {{ $approvalBadge }}">{{ $approvalLabel }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $rider->login_approved_until?->toDateTimeString() ?? '—' }}</td>
                                    <td>{{ $rider->qr_login_expires_at?->toDateTimeString() ?? '—' }}</td>
                                    <td>
                                        @if ($canManageRiders)
                                            <button
                                                class="btn btn-icon btn-sm btn-outline-primary js-rider-qr"
                                                type="button"
                                                data-show-url="{{ route('seller.delivery-boys.login-qr', $rider->id) }}"
                                                data-rotate-url="{{ route('seller.delivery-boys.rotate-login-qr', $rider->id) }}"
                                                data-mode="show"
                                                data-name="{{ $rider->user?->name ?? $rider->full_name }}"
                                                title="Show rider login QR"
                                                aria-label="Show QR for {{ $rider->full_name }}"
                                                @disabled($rider->verification_status !== \App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum::VERIFIED)
                                            ><i class="ti ti-qrcode" aria-hidden="true"></i></button>
                                            <button
                                                class="btn btn-icon btn-sm btn-outline-secondary js-rider-qr"
                                                type="button"
                                                data-show-url="{{ route('seller.delivery-boys.login-qr', $rider->id) }}"
                                                data-rotate-url="{{ route('seller.delivery-boys.rotate-login-qr', $rider->id) }}"
                                                data-mode="regenerate"
                                                data-name="{{ $rider->user?->name ?? $rider->full_name }}"
                                                title="Regenerate rider login QR"
                                                aria-label="Regenerate QR for {{ $rider->full_name }}"
                                                @disabled($rider->verification_status !== \App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum::VERIFIED)
                                            ><i class="ti ti-refresh" aria-hidden="true"></i></button>
                                            <button type="button" class="btn btn-icon btn-sm btn-outline-primary js-rider-edit"
                                                    title="Edit delivery boy" aria-label="Edit {{ $rider->full_name }}"
                                                    data-update-url="{{ route('seller.delivery-boys.update', $rider->id) }}"
                                                    data-name="{{ $rider->user?->name ?? $rider->full_name }}"
                                                    data-email="{{ $rider->user?->email }}"
                                                    data-mobile="{{ $rider->user?->mobile }}"
                                                    data-zone="{{ $rider->delivery_zone_id }}">
                                                <i class="ti ti-pencil" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="btn btn-icon btn-sm btn-outline-danger js-rider-delete"
                                                    title="Delete delivery boy" aria-label="Delete {{ $rider->full_name }}"
                                                    data-delete-url="{{ route('seller.delivery-boys.destroy', $rider->id) }}"
                                                    data-name="{{ $rider->user?->name ?? $rider->full_name }}">
                                                <i class="ti ti-trash" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-secondary py-4">No delivery boys are assigned to your seller account.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($riders->hasPages())
                        <div class="card-footer">{{ $riders->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="riderApprovalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="riderApprovalForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title">Password login approval</h3>
                            <div class="text-secondary mt-1" id="riderApprovalName"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">Current status: <span class="badge" id="riderApprovalStatus"></span></div>
                        <p class="text-secondary mb-0">
                            Approving allows password login for {{ $loginDurationHours }} hours. QR login is managed separately.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="riderApproveButton">Approve for {{ $loginDurationHours }} hours</button>
                        <button type="submit" class="btn btn-outline-danger" id="riderDisapproveButton">Disapprove</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="editRiderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="editRiderForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Edit delivery boy</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="edit_rider_name">Full name</label>
                                <input id="edit_rider_name" name="full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="edit_rider_email">Email</label>
                                <input id="edit_rider_email" name="email" type="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="edit_rider_mobile">Mobile</label>
                                <input id="edit_rider_mobile" name="mobile" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="edit_rider_password">New password</label>
                                <input id="edit_rider_password" name="password" type="password" minlength="8" class="form-control" autocomplete="new-password">
                                <div class="form-hint">Leave blank to keep the current password.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="edit_rider_zone">Delivery zone</label>
                                <select id="edit_rider_zone" name="delivery_zone_id" class="form-select" required>
                                    @foreach($deliveryZones as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="deleteRiderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <form id="deleteRiderForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-body text-center py-4">
                        <i class="ti ti-alert-triangle text-danger icon mb-2" aria-hidden="true"></i>
                        <h3>Delete delivery boy?</h3>
                        <div class="text-secondary">This deletes the rider account and revokes its active sessions. <span id="deleteRiderName" class="fw-bold"></span></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete delivery boy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="riderQrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="riderQrTitle">Rider login QR</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row align-items-end mb-3">
                        <div class="col">
                            <label class="form-label" for="riderQrDuration">QR validity (hours)</label>
                            <input id="riderQrDuration" type="number" class="form-control" min="1" max="{{ $loginDurationHours }}" value="{{ $loginDurationHours }}">
                            <div class="form-hint">Maximum set by the platform admin: {{ $loginDurationHours }} hours.</div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-danger" id="riderQrRotate">Regenerate QR</button>
                        </div>
                    </div>
                    <div class="text-center">
                        <canvas id="riderQrCanvas" class="mw-100"></canvas>
                        <div id="riderQrLoading" class="text-secondary">Loading QR credential…</div>
                        <div id="riderQrExpiry" class="small text-secondary mt-3"></div>
                        <div id="riderQrError" class="text-danger mt-2" role="alert"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/delivery-boys.js')
@endpush
