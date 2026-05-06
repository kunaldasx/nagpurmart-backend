@php use App\Enums\DateRangeFilterEnum;use App\Enums\Order\OrderItemStatusEnum;use App\Enums\Payment\PaymentTypeEnum;use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['orders']['active'] ?? ""])

@section('title', __('labels.orders'))

@section('header_data')
    @php
        $page_title = __('labels.orders');
        $page_pretitle = __('labels.admin') . " " . __('labels.orders');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.orders'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.order_items') }} <span class="order-count"></span></h3>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <select class="form-select text-capitalize" id="paymentFilter">
                                            <option value="">{{ __('labels.payment_type') }}</option>
                                            @foreach(PaymentTypeEnum::values() as $value)
                                                <option
                                                    value="{{$value}}">{{Str::replace("_", " ", $value)}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select text-capitalize" id="statusFilter">
                                            <option value="">{{ __('labels.status') }}</option>
                                            @foreach(OrderItemStatusEnum::values() as $value)
                                                <option
                                                    value="{{$value}}">{{Str::replace("_", " ", $value)}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="rangeFilter">
                                            <option value="">{{ __('labels.date_range') }}</option>
                                            @foreach(DateRangeFilterEnum::values() as $value)
                                                <option
                                                    value="{{$value}}">{{Str::replace("_", " ", $value)}}</option>
                                            @endforeach
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
                            <div class="row w-full">
                                <x-datatable id="orders-table" :columns="$columns"
                                             route="{{ route('admin.orders.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- REJECT MODAL -->
    <div
        class="modal modal-blur fade"
        id="rejectModel"
        tabindex="-1"
        role="dialog"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <!-- Download SVG icon from http://tabler.io/icons/icon/alert-triangle -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="icon mb-2 text-danger icon-lg"
                    >
                        <path d="M12 9v4"/>
                        <path
                            d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"
                        />
                        <path d="M12 16h.01"/>
                    </svg>
                    <h3>Reject Order</h3>
                    <div class="text-secondary">
                        Are you sure you want to reject this order? This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="#" class="btn w-100" data-bs-dismiss="modal">Cancel</a>
                            </div>
                            <div class="col">
                                <a href="#" class="btn btn-danger w-100" id="confirmReject" data-bs-dismiss="modal">Reject
                                    Order</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END MODAL -->
    <!-- ACCEPT MODAL -->
    <div
        class="modal modal-blur fade"
        id="acceptModel"
        tabindex="-1"
        role="dialog"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-success"></div>
                <div class="modal-body text-center py-4">
                    <!-- Download SVG icon from http://tabler.io/icons/icon/circle-check -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="icon mb-2 text-green icon-lg"
                    >
                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                        <path d="M9 12l2 2l4 -4"/>
                    </svg>
                    <h3>Accept Order</h3>
                    <div class="text-secondary">
                        Are you sure you want to accept this order? You will be responsible for fulfilling it.
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="#" class="btn w-100" data-bs-dismiss="modal">Cancel</a>
                            </div>
                            <div class="col">
                                <a href="#" class="btn btn-success w-100" id="confirmAccept" data-bs-dismiss="modal">Accept
                                    Order</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END MODAL -->
    <!-- PREPARING MODAL -->
    <div
        class="modal modal-blur fade"
        id="preparingModel"
        tabindex="-1"
        role="dialog"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-primary"></div>
                <div class="modal-body text-center py-4">
                    <!-- Download SVG icon from http://tabler.io/icons/icon/package -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="icon mb-2 text-primary icon-lg"
                    >
                        <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/>
                        <path d="M12 12l8 -4.5"/>
                        <path d="M12 12l0 9"/>
                        <path d="M12 12l-8 -4.5"/>
                    </svg>
                    <h3>Mark as Preparing</h3>
                    <div class="text-secondary">
                        Are you sure you want to mark this order as preparing? This indicates you've started working on
                        the order.
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="#" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancel</a>
                            </div>
                            <div class="col">
                                <a href="#" class="btn btn-primary w-100" id="confirmPreparing" data-bs-dismiss="modal">Mark
                                    as Preparing</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END MODAL -->
@endsection

@push('scripts')
    <script src="{{hyperAsset('assets/js/order.js')}}" defer></script>
@endpush
