@php use App\Enums\Order\OrderItemStatusEnum; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['orders']['active'] ?? ""])
@section('title', __('labels.order_details'))

@section('header_data')
    @php
        $page_title = __('labels.order_details');
        $page_pretitle = __('labels.admin') . " " . __('labels.order_details');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.orders'), 'url' => route('admin.orders.index')],
        ['title' => __('labels.order_details'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <!-- BEGIN PAGE HEADER -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <h2 class="page-title">{{ __('labels.order_details') }}</h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <a href="{{ route('admin.orders.index') }}"
                               class="btn btn-secondary d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-left"
                                     width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                     fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M5 12l14 0"></path>
                                    <path d="M5 12l6 6"></path>
                                    <path d="M5 12l6 -6"></path>
                                </svg>
                                {{ __('labels.back_to_orders') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END PAGE HEADER -->

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-cards">
                    <!-- Order Summary Card -->
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.order_summary') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="datagrid">
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.order_number') }}</div>
                                        <div class="datagrid-content">{{ $order['uuid'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.order_date') }}</div>
                                        <div
                                            class="datagrid-content">{{ $order['created_at'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.status') }}</div>
                                        <div class="datagrid-content text-capitalize">
                                            <span class="badge {{ $order['status'] }}">
                                                {{ Str::ucfirst(Str::replace("_", " ", $order['status']))}}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.final_total') }}</div>
                                        <div
                                            class="datagrid-content">{{ $systemSettings['currencySymbol'] . number_format($order['final_total'], 2) }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.payment_method') }}</div>
                                        <div
                                            class="datagrid-content text-uppercase">{{ $order['payment_method'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.payment_status') }}</div>
                                        <div class="datagrid-content text-capitalize">
                                            <span
                                                class="badge {{ $order['payment_status'] === 'paid' ? 'bg-green-lt' : 'bg-yellow-lt' }}">
                                            {{ $order['payment_status'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(!empty($order['order_note']))
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('labels.customer_information') }}</h3>
                                </div>
                                <div class="card-body">
                                    <div class="datagrid">
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">{{ __('labels.order_note') }}</div>
                                            <div
                                                class="datagrid-content text-capitalize">
                                                <textarea class="form-control" rows="3" readonly
                                                          disabled>{{ $order['order_note'] }}</textarea></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Customer Information Card -->
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.customer_information') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="datagrid">
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.customer_name') }}</div>
                                        <div
                                            class="datagrid-content text-capitalize">{{ $order['billing_name'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.email') }}</div>
                                        <div class="datagrid-content">{{ $order['email'] }}</div>
                                    </div>
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">{{ __('labels.phone') }}</div>
                                        <div class="datagrid-content">{{ $order['billing_phone'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address Card -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.shipping_address') }}</h3>
                            </div>
                            <div class="card-body">
                                <address>
                                    {{ $order['shipping_name'] }}<br>
                                    {{ $order['shipping_address_1'] }}<br>
                                    @if($order['shipping_address_2'])
                                        {{ $order['shipping_address_2'] }}<br>
                                    @endif
                                    @if($order['shipping_landmark'])
                                        {{ $order['shipping_landmark'] }}<br>
                                    @endif
                                    {{ $order['shipping_city'] }}
                                    , {{ $order['shipping_state'] }} {{ $order['shipping_zip'] }}<br>
                                    {{ $order['shipping_country'] }}<br>
                                    {{ $order['shipping_phone'] }}
                                </address>
                            </div>
                        </div>
                    </div>
                    <!-- Order Items Card -->
                    <div class="col-12 mt-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('labels.order_items') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                        <tr>
                                            {{--                                            <th width="30">--}}
                                            {{--                                                <input type="checkbox" class="form-check-input" id="select-all-items">--}}
                                            {{--                                            </th>--}}
                                            <th>{{ __('labels.store_name') }}</th>
                                            <th>{{ __('labels.product') }}</th>
                                            <th>{{ __('labels.variant') }}</th>
                                            <th>{{ __('labels.price') }}</th>
                                            <th>{{ __('labels.status') }}</th>
                                            <th>{{ __('labels.quantity') }}</th>
                                            <th>{{ __('labels.subtotal') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($order['items'] as $item)
                                            <tr>
                                                {{--                                                <td>--}}
                                                {{--                                                    <input type="checkbox" class="form-check-input item-checkbox"--}}
                                                {{--                                                           name="item_ids[]" value="{{ $item['orderItem']['id'] }}">--}}
                                                {{--                                                </td>--}}
                                                <td>{{ $item['store']['name'] ?? 'N/A' }}</td>
                                                <td>{{ $item['product']['title'] ?? 'N/A' }}
                                                    @if(!empty($item['attachments']))
                                                        <br class="mt-7">
                                                        <span class="fw-medium">Attachments</span>
                                                        <ul class="list-unstyled mb-0">
                                                            @foreach($item['attachments'] as $attachment)
                                                                <li><a href="{{ $attachment }}"
                                                                       target="_blank"
                                                                       data-fslightbox="gallery">view</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                                <td>{{ $item['variant']['title'] ?? 'N/A' }}</td>
                                                <td>{{$systemSettings['currencySymbol'] . number_format($item['price'] + $item['tax_amount'], 2) }}</td>
                                                <td><span class="badge {{ $item['orderItem']['status'] }}">
                                                {{ $item['orderItem']['status_formatted'] }}
                                                </span></td>
                                                <td>{{ $item['quantity'] }}</td>
                                                <td>{{ $systemSettings['currencySymbol'] . number_format($item['sub_total'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>{{ __('labels.total') }}:</strong>
                                            </td>
                                            <td><strong>{{ collect($order['items'])->sum('quantity')  }}</strong></td>
                                            <td>
                                                <strong>{{$systemSettings['currencySymbol'] . number_format($order['subtotal'], 2) }}</strong>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td colspan="6" class="text-end"><b>{{ __('labels.shipping_handling') }}
                                                    :</b></td>
                                            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['delivery_charge'], 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><b>{{ __('labels.handling_charges') }}:</b>
                                            </td>
                                            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['handling_charges'] ?? 0, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end">
                                                <b>{{ __('labels.per_store_drop_off_fee') }}:</b></td>
                                            <td>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['per_store_drop_off_fee'] ?? 0, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><b>{{ __('labels.grand_total') }}:</b></td>
                                            <td>
                                                <b>{{ $systemSettings['currencySymbol'] }}{{ number_format($order['subtotal'] + $order['delivery_charge'] + ($order['handling_charges'] ?? 0) + ($order['per_store_drop_off_fee'] ?? 0), 2) }}</b>
                                            </td>
                                        </tr>
                                        @if($order['wallet_balance'] > 0)
                                            <tr>
                                                <td colspan="6" class="text-end"><b>{{ __('labels.wallet_used') }}:</b>
                                                </td>
                                                <td>
                                                    - {{ $systemSettings['currencySymbol'] }}{{ $order['wallet_balance'] }}</td>
                                            </tr>
                                        @endif
                                        @if($order['promo_discount'] > 0)
                                            <tr>
                                                <td colspan="6" class="text-end">
                                                    <b>
                                                        {{ __('labels.promo_discount') }}
                                                        @if(!empty($order['promo_line']) && $order['promo_line']['cashback_flag'])
                                                            ({{ __('labels.cashback') }})

                                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                                  title="{{ __('messages.cashback_info_message') }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="18"
                                                                     height="18"
                                                                     viewBox="0 0 24 24" fill="none"
                                                                     stroke="currentColor"
                                                                     stroke-width="2" stroke-linecap="round"
                                                                     stroke-linejoin="round"
                                                                     class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                    <path
                                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/>
                                                                    <path d="M12 16v.01"/>
                                                                    <path
                                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/>
                                                                </svg>
                                                            </span>
                                                        @endif
                                                        <span
                                                            class="text-uppercase">({{ $order['promo_code'] }}):</span>
                                                    </b>
                                                </td>
                                                <td>
                                                    - {{ $systemSettings['currencySymbol'] }}{{ $order['promo_discount'] }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td colspan="6" class="text-end"><b>{{ __('labels.total_payable') }}:</b>
                                            </td>
                                            <td>
                                                <b>{{ $systemSettings['currencySymbol'] }}{{ $order['total_payable'] }}</b>
                                            </td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Status Card -->
                    {{--                    <div class="col-12 mt-3">--}}
                    {{--                        <div class="card">--}}
                    {{--                            <div class="card-header">--}}
                    {{--                                <h3 class="card-title">{{ __('labels.update_status') }}</h3>--}}
                    {{--                            </div>--}}
                    {{--                            <div class="card-body">--}}
                    {{--                                <div class="alert alert-info mb-3">--}}
                    {{--                                    <p class="mb-0">{{ __('labels.select_items_to_update_status') ?? 'Select one or more items from the table above to update their status.' }}</p>--}}
                    {{--                                </div>--}}
                    {{--                                <div id="status-update-results" class="mb-3"></div>--}}
                    {{--                                <form id="update-status-form" method="POST">--}}
                    {{--                                    @csrf--}}
                    {{--                                    <div class="mb-3">--}}
                    {{--                                        <label class="form-label">{{ __('labels.status') }}</label>--}}
                    {{--                                        <select name="status" class="form-select text-capitalize" id="item-status">--}}
                    {{--                                            <option--}}
                    {{--                                                    value="accept">Accept--}}
                    {{--                                            </option>--}}
                    {{--                                            <option--}}
                    {{--                                                    value="reject">Reject--}}
                    {{--                                            </option>--}}
                    {{--                                            <option--}}
                    {{--                                                    value="preparing">Preparing--}}
                    {{--                                            </option>--}}
                    {{--                                        </select>--}}
                    {{--                                    </div>--}}
                    {{--                                    <div class="mb-3">--}}
                    {{--                                        <button type="submit" class="btn btn-primary" id="update-items-status">--}}
                    {{--                                            {{ __('labels.update_status') }}--}}
                    {{--                                        </button>--}}
                    {{--                                    </div>--}}
                    {{--                                </form>--}}
                    {{--                            </div>--}}
                    {{--                        </div>--}}
                    {{--                    </div>--}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/order.js') }}"></script>
@endpush
