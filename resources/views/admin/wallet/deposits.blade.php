@extends('layouts.admin.app', ['page' => $menuAdmin['customers']['active'] ?? "", 'sub_page' => $menuAdmin['customers']['route']['deposits']['sub_active'] ?? null])

@section('title', __('labels.pending_wallet_deposits'))

@section('header_data')
    @php
        $page_title = __('labels.pending_wallet_deposits');
        $page_pretitle = __('labels.admin') . " " . __('labels.wallet');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.pending_wallet_deposits'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.pending_wallet_deposits') }}</h3>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <button class="btn btn-outline-primary" id="refresh">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
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
                                <x-datatable id="admin-wallet-deposits-table" :columns="$columns"
                                             route="{{ route('admin.wallet.deposits.datatable') }}"
                                             :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- APPROVE DEPOSIT MODAL (matches order accept modal style) -->
    <div class="modal modal-blur fade" id="approveDepositModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-success"></div>
                <div class="modal-body text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-green icon-lg">
                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                        <path d="M9 12l2 2l4 -4"/>
                    </svg>
                    <h3>{{ __('labels.complete') }} {{ __('labels.wallet') }} {{ __('labels.deposit') ?? 'Deposit' }}</h3>
                    <div class="text-secondary">{{ __('labels.confirm_manual_deposit_message') }}</div>
                    <div class="mt-3">
                        <div class="text-muted">{{ __('labels.customer') }}: <span id="approve-deposit-user"></span></div>
                        <div class="text-muted">{{ __('labels.amount') }}: <span id="approve-deposit-amount"></span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                            </div>
                            <div class="col">
                                <button class="btn btn-success w-100" id="confirmApproveDeposit" data-bs-dismiss="modal">{{ __('labels.complete') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- REJECT DEPOSIT MODAL (matches order reject modal style) -->
    <div class="modal modal-blur fade" id="rejectDepositModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon mb-2 text-danger icon-lg">
                        <path d="M12 9v4"/>
                        <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/>
                        <path d="M12 16h.01"/>
                    </svg>
                    <h3>{{ __('labels.fail') }} {{ __('labels.wallet') }} {{ __('labels.deposit') ?? 'Deposit' }}</h3>
                    <div class="text-secondary">{{ __('labels.confirm_manual_deposit_message') }}</div>
                    <div class="mt-3">
                        <div class="text-muted">{{ __('labels.customer') }}: <span id="reject-deposit-user"></span></div>
                        <div class="text-muted">{{ __('labels.amount') }}: <span id="reject-deposit-amount"></span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                            </div>
                            <div class="col">
                                <button class="btn btn-danger w-100" id="confirmRejectDeposit" data-bs-dismiss="modal">{{ __('labels.fail') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function(){

            let processingId = null;
            let processingUser = '';
            let processingAmount = '';

            $('#approveDepositModal, #rejectDepositModal')
                .on('show.bs.modal', function (event) {
                    const button = $(event.relatedTarget);
                    const modalId = this.id;

                    processingId = button.data('id');
                    processingUser = button.data('username');
                    processingAmount = button.data('amount');

                    if (modalId === 'approveDepositModal') {
                        $('#approve-deposit-user').text(processingUser);
                        $('#approve-deposit-amount').text(processingAmount);
                    }

                    if (modalId === 'rejectDepositModal') {
                        $('#reject-deposit-user').text(processingUser);
                        $('#reject-deposit-amount').text(processingAmount);
                    }
                })
                .on('hidden.bs.modal', function () {
                    const modalId = this.id;

                    if (modalId === 'approveDepositModal') {
                        $('#approve-deposit-user').text('');
                        $('#approve-deposit-amount').text('');
                    }

                    if (modalId === 'rejectDepositModal') {
                        $('#reject-deposit-user').text('');
                        $('#reject-deposit-amount').text('');
                    }

                    processingId = null;
                    processingUser = '';
                    processingAmount = '';
                });

            async function postAction(action){
                if(!processingId) return;
                try {
                    const resp = await fetch(`{{ url('admin/wallet/deposits') }}/${processingId}/process`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ action })
                    });
                    const data = await resp.json();
                    if (!resp.ok || !data.success) {
                        alert(data.message || 'Action failed');
                    }
                } catch (e) {
                    console.error(e);
                    alert('Action failed');
                } finally {
                    window.LarabergDataTables?.reload?.('admin-wallet-deposits-table') || window.location.reload();
                }
            }

            document.getElementById('confirmApproveDeposit')?.addEventListener('click', function(){ postAction('approve'); });
            document.getElementById('confirmRejectDeposit')?.addEventListener('click', function(){ postAction('reject'); });

        })();
    </script>
@endpush
