@extends('layouts.admin.app', ['page' => $menuAdmin['delivery_boy_management']['active'] ?? "", 'sub_page' => $menuAdmin['delivery_boy_management']['route']['delivery_boy_withdrawals']['sub_active']])

@section('title', __('labels.delivery_boy_withdrawals'))

@section('header_data')
    @php
        $page_title = __('labels.delivery_boy_withdrawals');
        $page_pretitle = __('labels.admin') . " " . __('labels.delivery_boy_withdrawals');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.delivery_boy_withdrawals'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.pending_withdrawal_requests') }}</h3>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <select class="form-select" name="delivery_boy" id="deliveryBoySearch">
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('admin.delivery-boy-withdrawals.history') }}" class="btn btn-outline-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-history">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M12 8l0 4l2 2"/>
                                                <path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"/>
                                            </svg>
                                            {{ __('labels.withdrawal_history') }}
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
                            <div class="row w-full p-3">
                                <x-datatable id="delivery-boy-withdrawals-table" :columns="$columns"
                                             route="{{ route('admin.delivery-boy-withdrawals.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WITHDRAWAL REQUEST MODAL -->
    <div class="modal modal-blur fade" id="withdrawalRequestModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-primary"></div>
                <div class="modal-body text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="icon icon-tabler icons-tabler-outline icon-tabler-circle-dashed-check icon-lg text-primary">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M8.56 3.69a9 9 0 0 0 -2.92 1.95"/>
                        <path d="M3.69 8.56a9 9 0 0 0 -.69 3.44"/>
                        <path d="M3.69 15.44a9 9 0 0 0 1.95 2.92"/>
                        <path d="M8.56 20.31a9 9 0 0 0 3.44 .69"/>
                        <path d="M15.44 20.31a9 9 0 0 0 2.92 -1.95"/>
                        <path d="M20.31 15.44a9 9 0 0 0 .69 -3.44"/>
                        <path d="M20.31 8.56a9 9 0 0 0 -1.95 -2.92"/>
                        <path d="M15.44 3.69a9 9 0 0 0 -3.44 -.69"/>
                        <path d="M9 12l2 2l4 -4"/>
                    </svg>
                    <h3>{{ __('labels.process_withdrawal_request') }}</h3>
                    <div class="text-secondary">{{ __('labels.confirm_withdrawal_request_message') }}</div>
                    <div class="mt-3">
                        <div class="text-muted">{{ __('labels.delivery_boy') }}: <span id="withdrawal-delivery-boy"></span></div>
                        <div class="text-muted">{{ __('labels.amount') }}: <span id="withdrawal-amount"></span></div>
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
@endsection

@push('scripts')
    <script src="{{asset('assets/js/delivery-boy-withdrawals.js')}}" defer></script>
@endpush
