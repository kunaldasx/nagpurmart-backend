@extends('layouts.admin.app', ['page' => $menuAdmin['delivery_boy_management']['active'] ?? "", 'sub_page' => $menuAdmin['delivery_boy_management']['route']['delivery_boy_withdrawals']['sub_active']])

@section('title', __('labels.withdrawal_request_details'))

@section('header_data')
    @php
        $page_title = __('labels.withdrawal_request_details');
        $page_pretitle = __('labels.admin') . " " . __('labels.delivery_boy_withdrawals');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.delivery_boy_withdrawals'), 'url' => route('admin.delivery-boy-withdrawals.index')],
        ['title' => __('labels.withdrawal_request_details'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <!-- BEGIN PAGE HEADER -->
        <div class="page-header d-print-none">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ __('labels.withdrawal_request_details') }}</h2>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
                <div class="col-12 col-md-auto ms-auto d-print-none">
                    <div class="btn-list">
                        @if($withdrawalRequest->status === 'pending' && $processRequestPermission ?? false)
                            <button type="button" class="btn btn-primary process-withdrawal-request"
                                    data-id="{{ $withdrawalRequest->id }}"
                                    data-delivery-boy-id="{{ $withdrawalRequest->delivery_boy_id }}"
                                    data-amount="{{ $withdrawalRequest->amount }}">
                                {{ __('labels.process_request') }}
                            </button>
                        @endif
                        <a href="{{ route('admin.delivery-boy-withdrawals.index') }}" class="btn btn-outline-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-left">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 12l14 0"/>
                                <path d="M5 12l6 6"/>
                                <path d="M5 12l6 -6"/>
                            </svg>
                            {{ __('labels.back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('labels.withdrawal_request_information') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border: none;">
                                    <tbody>
                                    <tr>
                                        <td class="fw-bold border-0" style="width: 180px;">{{ __('labels.id') }}</td>
                                        <td class="border-0">{{ $withdrawalRequest->id }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.delivery_boy') }}</td>
                                        <td class="border-0">{{ $withdrawalRequest->deliveryBoy->user->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.amount') }}</td>
                                        <td class="border-0">{{ number_format($withdrawalRequest->amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.status') }}</td>
                                        <td class="border-0">
                                            <span class="badge p-2 {{ $withdrawalRequest->status === 'approved' ? 'bg-success-lt border border-success-subtle' : ($withdrawalRequest->status === 'rejected' ? 'bg-danger-lt border border-danger-subtle' : 'bg-warning-lt border border-warning-subtle') }}">
                                                {{ ucfirst($withdrawalRequest->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.request_note') }}</td>
                                        <td class="border-0">{{ $withdrawalRequest->request_note ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.created_at') }}</td>
                                        <td class="border-0">{{ $withdrawalRequest->created_at->format('d M Y, H:i') }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('labels.processing_information') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border: none;">
                                    <tbody>
                                    <tr>
                                        <td class="fw-bold border-0" style="width: 180px;">{{ __('labels.admin_remark') }}</td>
                                        <td class="border-0">{{ $withdrawalRequest->admin_remark ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.processed_at') }}</td>
                                        <td class="border-0">{{ $withdrawalRequest->processed_at ? $withdrawalRequest->processed_at->format('d M Y, H:i') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.processed_by') }}</td>
                                        <td class="border-0">{{ $withdrawalRequest->processedBy->name ?? 'N/A' }}</td>
                                    </tr>
                                    @if($withdrawalRequest->transaction)
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.transaction_id') }}</td>
                                        <td class="border-0">{{ $withdrawalRequest->transaction->id ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.transaction_type') }}</td>
                                        <td class="border-0">{{ ucfirst($withdrawalRequest->transaction->transaction_type ?? 'N/A') }}</td>
                                    </tr>
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WITHDRAWAL REQUEST MODAL -->
    @if($withdrawalRequest->status === 'pending' && $processRequestPermission ?? false)
    <div class="modal modal-blur fade" id="withdrawalRequestModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-primary"></div>
                <div class="modal-body text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-primary icon-lg">
                        <path d="M16 6l-8.5 8.5l-4.5 -4.5"/>
                        <path d="M21 12c0 -4.97 -4.03 -9 -9 -9c-4.97 0 -9 4.03 -9 9s4.03 9 9 9c4.97 0 9 -4.03 9 -9z"/>
                    </svg>
                    <h3>{{ __('labels.process_withdrawal_request') }}</h3>
                    <div class="text-secondary">{{ __('labels.confirm_withdrawal_request_message') }}</div>
                    <div class="mt-3">
                        <div class="text-muted">{{ __('labels.delivery_boy') }}: <span id="withdrawal-delivery-boy">{{ $withdrawalRequest->deliveryBoy->user->name ?? 'N/A' }}</span></div>
                        <div class="text-muted">{{ __('labels.amount') }}: <span id="withdrawal-amount">{{ number_format($withdrawalRequest->amount, 2) }}</span></div>
                    </div>
                    <div class="mt-3">
                        <div class="form-group">
                            <label for="withdrawal-status" class="form-label">{{ __('labels.status') }}</label>
                            <select id="withdrawal-status" class="form-select">
                                <option value="approved">{{ __('labels.approved') }}</option>
                                <option value="rejected">{{ __('labels.rejected') }}</option>
                            </select>
                        </div>
                        <div class="form-group mt-3">
                            <label for="withdrawal-remark" class="form-label">{{ __('labels.admin_remark') }}</label>
                            <textarea id="withdrawal-remark" class="form-control" rows="3" placeholder="{{ __('labels.optional_remark') }}"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                            </div>
                            <div class="col">
                                <button class="btn btn-primary w-100" id="confirmWithdrawal" data-bs-dismiss="modal">{{ __('labels.confirm') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    <script src="{{asset('assets/js/delivery-boy-withdrawals.js')}}" defer></script>
@endpush
