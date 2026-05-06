@extends('layouts.admin.app', ['page' => $menuAdmin['delivery_boy_management']['active'] ?? "", 'sub_page' => $menuAdmin['delivery_boy_management']['route']['delivery_boys']['sub_active']])

@section('title', __('labels.delivery_boys'))

@section('header_data')
    @php
        $page_title = __('labels.delivery_boys');
        $page_pretitle = __('labels.admin') . " " . __('labels.delivery_boys');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.delivery_boys'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.delivery_boys') }} <span class="delivery-boy-count"></span></h3>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <select class="form-select" name="delivery_boy" id="deliveryBoySearch">
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="verificationStatusFilter">
                                            <option value="">{{ __('labels.verification_status') }}</option>
                                            @foreach($verificationStatuses as $status)
                                                <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="statusFilter">
                                            <option value="">{{ __('labels.status') }}</option>
                                            <option value="active">{{ __('labels.active') }}</option>
                                            <option value="inactive">{{ __('labels.inactive') }}</option>
                                        </select>
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
                                <x-datatable id="delivery-boys-table" :columns="$columns"
                                             route="{{ route('admin.delivery-boys.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW MODAL -->
    <div class="modal modal-blur fade" id="viewDeliveryBoyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('labels.delivery_boy_details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="deliveryBoyDetails">
                    <!-- Delivery boy details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- VERIFICATION STATUS MODAL -->
    <div class="modal modal-blur fade" id="verificationStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('labels.update_verification_status') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="verificationStatusForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.verification_status') }}</label>
                            <select class="form-select" name="verification_status" required>
                                <option value="">{{ __('labels.select_status') }}</option>
                                @foreach($verificationStatuses as $status)
                                    <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.verification_remark') }}</label>
                            <textarea class="form-control" name="verification_remark" rows="3" placeholder="{{ __('labels.optional_remark') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('labels.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- STATUS MODAL -->
    <div class="modal modal-blur fade" id="statusModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('labels.update_status') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="statusForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.status') }}</label>
                            <select class="form-select" name="status" required>
                                <option value="">{{ __('labels.select_status') }}</option>
                                <option value="active">{{ __('labels.active') }}</option>
                                <option value="inactive">{{ __('labels.inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('labels.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
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
                    <h3>{{ __('labels.delete_delivery_boy') }}</h3>
                    <div class="text-secondary">{{ __('labels.delete_delivery_boy_confirmation') }}</div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                            </div>
                            <div class="col">
                                <button class="btn btn-danger w-100" id="confirmDelete" data-bs-dismiss="modal">{{ __('labels.delete') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{hyperAsset('assets/js/delivery-boy.js')}}" defer></script>
@endpush
