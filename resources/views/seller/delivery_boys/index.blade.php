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
                            Password approval and QR login credentials expire after {{ $loginDurationHours }} hours.
                        </div>
                    </div>
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
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Add a delivery boy</h3>
                        </div>
                        <form method="POST" action="{{ route('seller.delivery-boys.store') }}">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="full_name">Full name</label>
                                        <input id="full_name" name="full_name" class="form-control" required value="{{ old('full_name') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="email">Email</label>
                                        <input id="email" name="email" type="email" class="form-control" required value="{{ old('email') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="mobile">Mobile</label>
                                        <input id="mobile" name="mobile" class="form-control" value="{{ old('mobile') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="password">Temporary password</label>
                                        <input id="password" name="password" type="password" minlength="8" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="delivery_zone_id">Delivery zone</label>
                                        <select id="delivery_zone_id" name="delivery_zone_id" class="form-select" required>
                                            <option value="">Select a delivery zone</option>
                                            @foreach($deliveryZones as $zone)
                                                <option value="{{ $zone->id }}" @selected((string) old('delivery_zone_id') === (string) $zone->id)>{{ $zone->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <p class="text-secondary mb-0">
                                    New accounts remain inactive and unverified until reviewed by the platform admin.
                                </p>
                            </div>
                            <div class="card-footer text-end">
                                <button class="btn btn-primary" type="submit">Create delivery boy</button>
                            </div>
                        </form>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Your delivery boys</h3>
                    </div>
                    <div class="card-body border-bottom text-secondary">
                        Seller approval controls password login. QR login is a separate credential; use <strong>Regenerate QR</strong> to revoke an exposed QR and its sessions immediately.
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
                                        @if ($rider->login_approval_status === 'approved' && $rider->login_approved_until?->isFuture())
                                            <span class="badge bg-green-lt">Approved</span>
                                        @elseif ($rider->login_approval_status === 'pending')
                                            <span class="badge bg-yellow-lt">Pending</span>
                                        @else
                                            <span class="badge bg-red-lt">Disapproved</span>
                                        @endif
                                    </td>
                                    <td>{{ $rider->login_approved_until?->toDateTimeString() ?? '—' }}</td>
                                    <td>
                                        @if ($canManageRiders)
                                            @if ($rider->login_approval_status !== 'approved' || !$rider->login_approved_until?->isFuture())
                                                <form method="POST" action="{{ route('seller.delivery-boys.approve-login', $rider->id) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success" type="submit">Approve for {{ $loginDurationHours }} hours</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('seller.delivery-boys.reject-login', $rider->id) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Disapprove</button>
                                            </form>
                                            <button
                                                class="btn btn-sm btn-outline-primary js-rider-qr"
                                                type="button"
                                                data-url="{{ route('seller.delivery-boys.login-qr', $rider->id) }}"
                                                data-method="GET"
                                                data-name="{{ $rider->user?->name ?? $rider->full_name }}"
                                                @disabled($rider->verification_status !== \App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum::VERIFIED)
                                            >Show QR</button>
                                            <button
                                                class="btn btn-sm btn-outline-secondary js-rider-qr"
                                                type="button"
                                                data-url="{{ route('seller.delivery-boys.rotate-login-qr', $rider->id) }}"
                                                data-method="POST"
                                                data-name="{{ $rider->user?->name ?? $rider->full_name }}"
                                                @disabled($rider->verification_status !== \App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum::VERIFIED)
                                            >Regenerate QR</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">No delivery boys are assigned to your seller account.</td>
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

    <div class="modal modal-blur fade" id="riderQrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="riderQrTitle">Rider login QR</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <canvas id="riderQrCanvas" class="mw-100"></canvas>
                    <div id="riderQrExpiry" class="small text-secondary mt-3"></div>
                    <div id="riderQrError" class="text-danger mt-2" role="alert"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/delivery-boys.js')
@endpush
