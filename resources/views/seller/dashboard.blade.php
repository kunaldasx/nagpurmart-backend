@extends('layouts.seller.app', ['page' => $menuSeller['dashboard']['active'] ?? ""])

@section('title', __('labels.dashboard'))

@section('header_data')
    @php
        $page_title = __('labels.dashboard');
        $page_pretitle = __('labels.seller') . " " . __('labels.dashboard');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => '']
    ];
@endphp

@section('seller-content')
    @if($viewPermission ?? false)
        <div class="row row-deck row-cards">
            <div class="col-sm-12 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="row gy-3">
                            <div class="col-12 col-sm d-flex flex-column justify-content-between">
                                <div>
                                    <h3 class="h2 text-capitalize">Welcome back, {{$user->name ?? "Power"}}</h3>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">{{ __('labels.sales') }}</div>
                                        <div class="ms-auto lh-1">
                                            <div class="dropdown">
                                                <a class="dropdown-toggle text-secondary sales-period" href="#"
                                                   data-bs-toggle="dropdown"
                                                   aria-haspopup="true" aria-expanded="false" data-period="30"
                                                >{{ __('labels.last_30_days') }}</a
                                                >
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="#"
                                                       data-period="7">{{ __('labels.last_7_days') }}</a>
                                                    <a class="dropdown-item active" href="#"
                                                       data-period="30">{{ __('labels.last_30_days') }}</a>
                                                    <a class="dropdown-item" href="#"
                                                       data-period="90">{{ __('labels.last_3_months') }}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="h1 mb-3">{{ $conversionRateData['rate'] ?? 0 }}%</div>
                                    <div class="d-flex mb-2">
                                        <div>{{ __('labels.conversion_rate') }}</div>
                                        <div class="ms-auto">
                        <span
                            class="text-{{ $conversionRateData['is_increase'] ? 'green' : 'red' }} d-inline-flex align-items-center lh-1">
                          {{ abs($conversionRateData['percentage_change']) }}%
                            <!-- Download SVG icon from http://tabler.io/icons/icon/trending-up or trending-down -->
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
                              class="icon ms-1 icon-2"
                          >
                            @if($conversionRateData['is_increase'])
                                  <path d="M3 17l6 -6l4 4l8 -8"/>
                                  <path d="M14 7l7 0l0 7"/>
                              @else
                                  <path d="M3 7l6 6l4 -4l8 8"/>
                                  <path d="M21 7l0 7l-7 0"/>
                              @endif
                          </svg>
                        </span>
                                        </div>
                                    </div>
                                    <div class="text-secondary mb-2">
                                        {{ $conversionRateData['delivered_orders'] }} {{ __('labels.delivered_out_of_total_orders') }}
                                        {{ $conversionRateData['total_orders'] }}
                                    </div>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-primary"
                                            style="width: {{ $conversionRateData['rate'] }}%"
                                            role="progressbar"
                                            aria-valuenow="{{ $conversionRateData['rate'] }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            aria-label="{{ $conversionRateData['rate'] }}% {{ __('labels.complete') }}"
                                        >
                                        <span
                                            class="visually-hidden">{{ $conversionRateData['rate'] }}% {{ __('labels.complete') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-auto d-flex justify-content-center">
                                <img src="{{asset("assets/theme/img/dashboard.svg")}}" alt="Sales Illustration"
                                     class="img-fluid"
                                     style="max-height: 200px;" width="100%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('labels.revenue') }}</div>
                            <div class="ms-auto lh-1">
                                <div class="dropdown">
                                    <a class="dropdown-toggle text-secondary revenue-period" href="#"
                                       data-bs-toggle="dropdown"
                                       aria-haspopup="true" aria-expanded="false" data-period="30"
                                    >{{ __('labels.last_30_days') }}</a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="#"
                                           data-period="7">{{ __('labels.last_7_days') }}</a>
                                        <a class="dropdown-item active" href="#"
                                           data-period="30">{{ __('labels.last_30_days') }}</a>
                                        <a class="dropdown-item" href="#"
                                           data-period="90">{{ __('labels.last_3_months') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-baseline">
                            <div class="h1 mb-0 me-2" id="revenue-total">{{ $revenueData['formatted_total'] }}</div>
                            <div class="me-auto">
                        <span class="text-green d-inline-flex align-items-center lh-1" id="revenue-days">
                          {{ count($revenueData['daily']) }} {{ __('labels.days') }}
                            <!-- Download SVG icon from http://tabler.io/icons/icon/calendar -->
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
                              class="icon ms-1 icon-2"
                          >
                            <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z"/>
                            <path d="M16 3v4"/>
                            <path d="M8 3v4"/>
                            <path d="M4 11h16"/>
                            <path d="M11 15h1"/>
                            <path d="M12 15v3"/>
                          </svg>
                        </span>
                            </div>
                        </div>
                    </div>
                    <div id="chart-revenue-bg" class="rounded-bottom chart-sm"></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('labels.wallet_balance') }}</div>
                        </div>
                        <div class="d-flex align-items-baseline">
                            <div class="h1 mb-3 me-2">{{ $currencyService->format($walletBalance) }}</div>
                            <div class="me-auto">
                        <span class="text-yellow d-inline-flex align-items-center lh-1">
                          {{ $currencyService->format($blockedBalance) }} {{ __('labels.blocked') }}
                            <!-- Download SVG icon from http://tabler.io/icons/icon/minus -->
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
                              class="icon ms-1 icon-2"
                          >
                            <path d="M5 12l14 0"/>
                          </svg>
                        </span>
                            </div>
                        </div>
                        <div id="chart-new-clients" class="chart-sm"></div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="row row-cards">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <a href="{{ route('seller.commissions.index') }}"
                               class="card-body text-decoration-none">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                            <span class="bg-primary text-white avatar"
                            ><!-- Download SVG icon from http://tabler.io/icons/icon/currency-dollar -->
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
                                  class="icon icon-1"
                              >
                                <path
                                    d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2"/>
                                <path d="M12 3v3m0 12v3"/></svg
                              ></span>
                                    </div>
                                    <div class="col">
                                        <div
                                            class="font-weight-medium">{{ $salesData['total_sales'] }} {{ __('labels.items_sold') }}</div>
                                        <div class="text-secondary">{{ $salesData['unsettled_payments'] }}
                                            {{ $salesData['unsettled_payments'] != 1 ? __('labels.unsettled_payments') : __('labels.unsettled_payment') }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <a href="{{ route('seller.orders.index') }}"
                               class="card-body text-decoration-none">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                            <span class="bg-green text-white avatar"
                            ><!-- Download SVG icon from http://tabler.io/icons/icon/shopping-cart -->
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
                                  class="icon icon-1"
                              >
                                <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                                <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                                <path d="M17 17h-11v-14h-2"/>
                                <path d="M6 5l14 1l-1 7h-13"/></svg
                              ></span>
                                    </div>
                                    <div class="col">
                                        <div
                                            class="font-weight-medium">{{ $totalOrders }} {{ __('labels.orders') }}</div>
                                        <div
                                            class="text-secondary">{{ $deliveredOrders }} {{ __('labels.delivered') }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                            <span class="bg-yellow text-white avatar"
                            ><!-- Download SVG icon from http://tabler.io/icons/icon/star -->
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
                                  class="icon icon-1"
                              >
                                <path
                                    d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z"/></svg
                              ></span>
                                    </div>
                                    <div class="col">
                                        <div
                                            class="font-weight-medium">{{ $recentFeedback['average_rating'] }} {{ __('labels.rating') }}</div>
                                        <div
                                            class="text-secondary">{{ $recentFeedback['total_reviews'] }} {{ __('labels.reviews') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <a href="{{ route('seller.products.index') }}"
                               class="card-body text-decoration-none">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                            <span class="bg-azure text-white avatar"
                            ><!-- Download SVG icon from http://tabler.io/icons/icon/package -->
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
                                  class="icon icon-1"
                              >
                                <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/>
                                <path d="M12 12l8 -4.5"/>
                                <path d="M12 12l0 9"/>
                                <path d="M12 12l-8 -4.5"/></svg
                              ></span>
                                    </div>
                                    <div class="col">
                                        <div
                                            class="font-weight-medium">{{ $productStats['total_products'] }} {{ __('labels.products') }}</div>
                                        <div
                                            class="text-secondary">{{ $productStats['recent_products'] }} {{ __('labels.new_this_week') }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <h3 class="card-title mb-0">{{ __('labels.store_revenue') }}</h3>
                            <div class="ms-auto">
                                <div class="dropdown">
                                    <a class="dropdown-toggle text-secondary store-revenue-period" href="#"
                                       data-bs-toggle="dropdown"
                                       aria-haspopup="true" aria-expanded="false" data-period="30"
                                    >{{ __('labels.last_30_days') }}</a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="#"
                                           data-period="7">{{ __('labels.last_7_days') }}</a>
                                        <a class="dropdown-item active" href="#"
                                           data-period="30">{{ __('labels.last_30_days') }}</a>
                                        <a class="dropdown-item" href="#"
                                           data-period="90">{{ __('labels.last_3_months') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="chart-stores-revenue" class="chart-lg"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">{{ __('labels.store_wise_order_distribution') }}</h3>
                        <div id="chart-campaigns"></div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div class="card-title">{{ __('labels.recent_orders') }}</div>
                            <div class="ms-auto">
                                <a href="{{ route('seller.orders.index') }}" class="btn btn-primary">
                                    {{ __('labels.view_all_orders') }}
                                </a>
                                <button class="btn btn-outline-primary" id="refresh">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round"
                                         class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"></path>
                                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"></path>
                                    </svg>
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row w-full">
                            <x-datatable id="orders-table" :columns="$orderColumns"
                                         route="{{ route('seller.orders.datatable') }}"
                                         :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title">{{ __('labels.daily_orders_history') }}</div>
                    </div>
                    <div class="position-relative">
                        <div class="position-absolute top-0 left-0 px-3 mt-1 w-75">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <div class="chart-sparkline chart-sparkline-square"
                                         id="sparkline-activity"></div>
                                </div>
                                <div class="col">
                                    <div>{{ __('labels.todays_earning') }}
                                        : {{ $todaysEarning['formatted_today'] }}</div>
                                    <div class="text-{{ $todaysEarning['is_increase'] ? 'green' : 'red' }}">
                                        <!-- Download SVG icon from http://tabler.io/icons/icon/trending-up or trending-down -->
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
                                            class="icon icon-inline {{ $todaysEarning['is_increase'] ? 'text-green' : 'text-red' }} icon-3"
                                        >
                                            @if($todaysEarning['is_increase'])
                                                <path d="M3 17l6 -6l4 4l8 -8"/>
                                                <path d="M14 7l7 0l0 7"/>
                                            @else
                                                <path d="M3 7l6 6l4 -4l8 8"/>
                                                <path d="M21 7l0 7l-7 0"/>
                                            @endif
                                        </svg>
                                        {{ abs($todaysEarning['percentage_change']) }}
                                        % {{ $todaysEarning['is_increase'] ? __('labels.more') : __('labels.less') }} {{ __('labels.than_yesterday') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="chart-development-activity"></div>
                    </div>
                    <div class="card-table table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                            <tr>
                                <th>{{ __('labels.customer') }}</th>
                                <th>{{ __('labels.feedback') }}</th>
                                <th>{{ __('labels.rating') }}</th>
                                <th>{{ __('labels.date') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if(count($recentFeedback['items']) > 0)
                                @foreach($recentFeedback['items'] as $feedback)
                                    <tr>
                                        <td class="w-1">
                                    <span class="avatar avatar-sm">
                                        {{ substr($feedback['user_name'], 0, 1) }}
                                    </span>
                                        </td>
                                        <td class="td-truncate">
                                            <div class="font-weight-medium">{{ $feedback['title'] }}</div>
                                            <div
                                                class="text-truncate text-secondary">{{ $feedback['description'] }}</div>
                                        </td>
                                        <td>
                                            <div class="d-flex pointer-events-none">
                                                <select id="rating-{{ $loop->index }}" class="rating-stars"
                                                        data-rating="{{ $feedback['rating'] }}">
                                                    <option value="">{{ __('labels.select_a_rating') }}</option>
                                                    <option value="5" {{ $feedback['rating'] == 5 ? 'selected' : '' }}>
                                                        {{ __('labels.excellent') }}
                                                    </option>
                                                    <option value="4" {{ $feedback['rating'] == 4 ? 'selected' : '' }}>
                                                        {{ __('labels.very_good') }}
                                                    </option>
                                                    <option value="3" {{ $feedback['rating'] == 3 ? 'selected' : '' }}>
                                                        {{ __('labels.average') }}
                                                    </option>
                                                    <option value="2" {{ $feedback['rating'] == 2 ? 'selected' : '' }}>
                                                        {{ __('labels.poor') }}
                                                    </option>
                                                    <option value="1" {{ $feedback['rating'] == 1 ? 'selected' : '' }}>
                                                        {{ __('labels.terrible') }}
                                                    </option>
                                                </select>
                                            </div>
                                        </td>
                                        <td class="text-nowrap text-secondary">{{ $feedback['date'] }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4" class="text-center">{{ __('labels.no_feedback_available') }}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                        <div class="card-footer d-flex align-items-center">
                            <p class="m-0 text-secondary">
                                {{ __('labels.total_reviews') }}: <span
                                    class="font-weight-medium">{{ $recentFeedback['total_reviews'] }}</span>
                                | {{ __('labels.average_rating') }}: <span
                                    class="font-weight-medium">{{ $recentFeedback['average_rating'] }}</span>
                                <span class="ml-2 pointer-events-none">
                                <select id="rating-average" class="rating-stars"
                                        data-rating="{{ $recentFeedback['average_rating'] }}">
                                    <option value="">{{ __('labels.select_a_rating') }}</option>
                                    <option
                                        value="5" {{ round($recentFeedback['average_rating']) == 5 ? 'selected' : '' }}>{{ __('labels.excellent') }}</option>
                                    <option
                                        value="4" {{ round($recentFeedback['average_rating']) == 4 ? 'selected' : '' }}>{{ __('labels.very_good') }}</option>
                                    <option
                                        value="3" {{ round($recentFeedback['average_rating']) == 3 ? 'selected' : '' }}>{{ __('labels.average') }}</option>
                                    <option
                                        value="2" {{ round($recentFeedback['average_rating']) == 2 ? 'selected' : '' }}>{{ __('labels.poor') }}</option>
                                    <option
                                        value="1" {{ round($recentFeedback['average_rating']) == 1 ? 'selected' : '' }}>{{ __('labels.terrible') }}</option>
                                </select>
                            </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('seller.orders.partials.order-accept-modal')
        @include('seller.orders.partials.order-preparing-modal')
        @include('seller.orders.partials.order-reject-modal')
    @endif
@endsection
@push('styles')
    <link rel="stylesheet" href="{{asset('assets/vendor/star-rating.js/dist/star-rating.min.css')}}">
@endpush

@push('script')
    <script src="{{asset('assets/vendor/apexcharts/dist/apexcharts.min.js')}}" defer></script>
    <script src="{{asset('assets/vendor/star-rating.js/dist/star-rating.min.js')}}" defer></script>
    <script src="{{hyperAsset('assets/js/order.js')}}" defer></script>
    <script>
        // Pass dashboard data to JavaScript
        var dashboardData = {
            monthlyRevenueData: @json($monthlyRevenueData),
            storeOrderTotals: @json($storeOrderTotals),
            storeRevenueData: @json($storeRevenueData),
            dailyPurchaseHistory: @json($dailyPurchaseHistory),
            todaysEarning: @json($todaysEarning)
        };
    </script>
    <script src="{{hyperAsset('assets/js/seller-dashboard.js')}}" defer></script>
@endpush
