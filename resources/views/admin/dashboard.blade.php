@extends('layouts.admin.app', ['page' => $menuAdmin['dashboard']['active'] ?? ""])

@section('title', __('labels.dashboard'))

@section('header_data')
    @php
        $page_title = __('labels.dashboard');
        $page_pretitle = __('labels.admin') . " " . __('labels.dashboard');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => '']
    ];
@endphp

@section('admin-content')
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
                                                   aria-haspopup="true" aria-expanded="false" data-period="7"
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
                                    <div class="h1 mb-3" id="sales-rate">{{ $conversionRateData['rate'] ?? 0 }}%</div>
                                    <div class="d-flex mb-2">
                                        <div>{{ __('labels.conversion_rate') }}</div>
                                        <div class="ms-auto">
                        <span
                            class="text-{{ !empty($conversionRateData['is_increase']) && $conversionRateData['is_increase'] ? 'green' : 'red' }} d-inline-flex align-items-center lh-1"
                            id="sales-trend">
                          {{ abs($conversionRateData['percentage_change'] ?? 0) }}%
                        </span>
                                            <!-- Download SVG icon from http://tabler.io/icons/icon/trending-up or trending-down -->
                                            <span
                                                class="text-{{ !empty($conversionRateData['is_increase']) && $conversionRateData['is_increase'] ? 'green' : 'red' }}"> <svg
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
                            <path id="sales-trend-path-1"
                                  d="{{ $conversionRateData['is_increase'] ?? false ? 'M3 17l6 -6l4 4l8 -8' : 'M3 7l6 6l4 -4l8 8' }}"/>
                            <path id="sales-trend-path-2"
                                  d="{{ $conversionRateData['is_increase'] ?? false ? 'M14 7l7 0l0 7' : 'M21 7l0 7l-7 0' }}"/>
                          </svg></span>
                                        </div>
                                    </div>
                                    <div class="text-secondary mb-2" id="sales-details">
                                        {{ $conversionRateData['delivered_orders'] ?? 0 }} {{ __('labels.delivered_out_of_total_orders') }}
                                        {{ $conversionRateData['total_orders'] ?? 0 }}
                                    </div>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-primary"
                                            id="sales-progress"
                                            style="width: {{ $conversionRateData['rate'] ?? 0 }}%"
                                            role="progressbar"
                                            aria-valuenow="{{ $conversionRateData['rate'] ?? 0 }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            aria-label="{{ $conversionRateData['rate'] ?? 0 }}% {{ __('labels.complete') }}"
                                        >
                            <span
                                class="visually-hidden">{{ $conversionRateData['rate']?? 0 }}% {{ __('labels.complete') }}</span>
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
            <div class=" col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('labels.revenue') }}</div>
                            <div class="ms-auto lh-1">
                                <div class="dropdown">
                                    <a class="dropdown-toggle text-secondary revenue-period"
                                       id="revenue-period" href="#"
                                       data-bs-toggle="dropdown"
                                       aria-haspopup="true" aria-expanded="false" data-period="7"
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
                        <div class="d-flex align-items-baseline">
                            <div class="h1 mb-0 me-2"
                                 id="revenue-total">{{ $revenueDataBg['formatted_total'] ?? 0 }}</div>
                            <div class="me-auto">

                        <span class="text-green d-inline-flex align-items-center lh-1" id="revenue-days">
                          {{ count($revenueDataBg['daily'] ?? []) }} {{ __('labels.days') }}
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
                            <div class="subheader">{{ __('labels.new_user_registrations') }}</div>
                            <div class="ms-auto lh-1">
                                <div class="dropdown">
                                    <a class="dropdown-toggle text-secondary new-users-period" href="#"
                                       data-bs-toggle="dropdown"
                                       aria-haspopup="true" aria-expanded="false" data-period="7"
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
                        <div class="d-flex align-items-baseline">
                            <div class="h1 mb-3 me-2"
                                 id="new-users-count">{{ $newUserRegistrationsData['count'] }}</div>
                            <div class="me-auto">
                        <span
                            class="text-{{ $newUserRegistrationsData['is_increase'] ? 'green' : 'red' }} d-inline-flex align-items-center lh-1"
                            id="new-users-trend">
                          {{ abs($newUserRegistrationsData['percentage_change']) }}%
                            <!-- Download SVG icon from http://tabler.io/icons/icon/trending-up -->
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
                            @if($newUserRegistrationsData['is_increase'])
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
                    </div>
                    <div id="chart-new-users" class="chart-sm"></div>
                </div>
            </div>
            <div class="col-12">
                <div class="row row-cards">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <a href="{{ route('admin.sellers.index') }}"
                               class="card-body text-decoration-none">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                            <span class="bg-primary text-white avatar"
                            ><!-- Download SVG icon from http://tabler.io/icons/icon/building-store -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round"
                                     class="icon icon-tabler icons-tabler-outline icon-tabler-building-store"><path
                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path
                                        d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/><path
                                        d="M5 21l0 -10.15"/><path d="M19 21l0 -10.15"/><path
                                        d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/></svg>
                              </span>
                                    </div>
                                    <div class="col">
                                        <div
                                            class="font-weight-medium">{{$adminInsights['total_sellers']}} {{ __('labels.sellers') }}</div>
                                        <div
                                            class="text-secondary">{{$adminInsights['total_stores']}} {{ __('labels.active_stores') }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <a href="{{ route('admin.orders.index') }}"
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
                                            class="font-weight-medium">{{$adminInsights['total_orders']}} {{ __('labels.orders') }}</div>
                                        <div
                                            class="text-secondary">{{$adminInsights['total_delivered_orders']}} {{ __('labels.delivered') }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <a href="{{ route('admin.delivery-boys.index') }}"
                               class="card-body text-decoration-none">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                            <span class="bg-yellow text-white avatar"
                            ><!-- Download SVG icon from http://tabler.io/icons/icon/bike -->
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                  stroke-linejoin="round"
                                  class="icon icon-tabler icons-tabler-outline icon-tabler-bike"><path stroke="none"
                                                                                                       d="M0 0h24v24H0z"
                                                                                                       fill="none"/><path
                                     d="M5 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path
                                     d="M19 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path
                                     d="M12 19l0 -4l-3 -3l5 -4l2 3l3 0"/><path
                                     d="M17 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg></span>
                                    </div>
                                    <div class="col">
                                        <div
                                            class="font-weight-medium">{{$adminInsights['total_active_delivery_boys']}} {{ __('labels.active_delivery_boys') }}</div>
                                        <div
                                            class="text-secondary">{{$adminInsights['total_delivery_boys']}} {{ __('labels.total_delivery_boys') }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <a href="{{ route('admin.products.index') }}"
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
                                            class="font-weight-medium">{{ $adminInsights['total_products'] }} {{ __('labels.products') }}</div>
                                        <div
                                            class="text-secondary">{{ $adminInsights['total_product_sales'] }} {{ __('labels.total_sales') }}
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
                        <h3 class="card-title">{{ __('labels.order_commission_last_30_days') }}</h3>
                        <div id="chart-revenue" class="chart-lg"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h3 class="card-title">{{ __('labels.enhanced_commissions') }}</h3>
                            <div class="ms-auto">
                                <div class="dropdown ps-2">
                                    <a class="dropdown-toggle text-secondary commission-period" href="#"
                                       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                       data-period="30">{{ __('labels.last_30_days') }}</a>
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
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <div class="text-center">
                                    <div class="h3 mb-0"
                                         id="commission-total">{{ $enhancedCommissionsData['total_commission'] }}</div>
                                    <div class="text-secondary">{{ __('labels.total_commission') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-center">
                                    <div class="h3 mb-0"
                                         id="commission-orders">{{ $enhancedCommissionsData['total_orders'] }}</div>
                                    <div class="text-secondary">{{ __('labels.total_orders') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-center">
                                    <div class="h3 mb-0"
                                         id="commission-avg">{{ $enhancedCommissionsData['avg_commission'] }}</div>
                                    <div class="text-secondary">{{ __('labels.avg_commission') }}</div>
                                </div>
                            </div>
                        </div>
                        <div id="commission-chart" class="chart-lg"></div>
                    </div>
                </div>
            </div>

            <!-- Top Sellers Section -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h3 class="card-title">{{ __('labels.top_sellers') }}</h3>
                            <div class="ms-auto">
                                <div class="dropdown ps-2">
                                    <a class="dropdown-toggle text-secondary top-sellers-period" href="#"
                                       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                       data-period="30">{{ __('labels.last_30_days') }}</a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="#"
                                           data-period="7">{{ __('labels.last_7_days') }}</a>
                                        <a class="dropdown-item active" href="#"
                                           data-period="30">{{ __('labels.last_30_days') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush" id="top-sellers-list">
                            @forelse($topSellers as $index => $seller)
                                <div class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-teal-lt me-3">{{ $index + 1 }}</span>
                                    <div class="avatar avatar-sm me-3">
                                        @if(!empty($seller['avatar']))
                                            <img src="{{ $seller['avatar'] }}" alt="{{ $seller['name'] }}"
                                                 class="rounded">
                                        @else
                                            <span class="avatar avatar-sm bg-primary text-white">
                        {{ strtoupper(substr($seller['name'], 0, 2)) }}
                    </span>
                                        @endif
                                    </div>
                                    <div class="flex-fill">
                                        <div class="font-weight-medium">{{ $seller['name'] }}</div>
                                        <div class="text-secondary">
                                            {{ $seller['total_orders'] }} {{ __('labels.orders') }}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="font-weight-medium">{{ $seller['total_revenue'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center w-100 py-5">
                                    <img src="{{ asset('assets/theme/img/not-found.svg') }}"
                                         alt="No data found"
                                         class="w-100"
                                         style="max-width: 400px; height: auto; margin: 0 auto; display: block;">
                                </div>
                            @endforelse
                        </div>

                    </div>
                </div>
            </div>

            <!-- Top Products Section -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h3 class="card-title">{{ __('labels.top_selling_products') }}</h3>
                            <div class="ms-auto">
                                <div class="dropdown ps-2">
                                    <a class="dropdown-toggle text-secondary top-products-period" href="#"
                                       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                       data-period="30">{{ __('labels.last_30_days') }}</a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="#"
                                           data-period="7">{{ __('labels.last_7_days') }}</a>
                                        <a class="dropdown-item active" href="#"
                                           data-period="30">{{ __('labels.last_30_days') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush" id="top-products-list">
                            @forelse($topSellingProducts as $index => $product)
                                <div class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary-lt me-3">{{ $index + 1 }}</span>
                                    <div class="avatar avatar-sm me-3">
                                        @if(!empty($product['image']))
                                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}"
                                                 class="rounded">
                                        @else
                                            <span class="avatar avatar-sm bg-primary text-white">
                        {{ strtoupper(substr($product['name'], 0, 1)) }}
                    </span>
                                        @endif
                                    </div>
                                    <div class="flex-fill">
                                        <div class="font-weight-medium">
                                            <a href="{{ url('admin/products/' . $product['id']) }}"
                                               class="text-decoration-none text-body">
                                                {{ Str::limit($product['name'], 25) }}
                                            </a>
                                        </div>
                                        <div class="text-secondary">
                                            {{ $product['category'] }}
                                        </div>
                                        <div class="text-secondary">
                                            {{ $product['total_quantity'] }} {{ __('labels.sold') }}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="font-weight-medium">{{ $product['total_revenue'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center w-100 py-5">
                                    <img src="{{ asset('assets/theme/img/not-found.svg') }}"
                                         alt="No products found"
                                         class="w-100"
                                         style="max-width: 400px; height: auto; margin: 0 auto; display: block;">
                                </div>
                            @endforelse
                        </div>

                    </div>
                </div>
            </div>

            <!-- Top Delivery Boys Section -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h3 class="card-title">{{ __('labels.top_delivery_boys') }}</h3>
                            <div class="ms-auto">
                                <div class="dropdown ps-2">
                                    <a class="dropdown-toggle text-secondary top-delivery-boys-period"
                                       href="#"
                                       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                       data-period="30">{{ __('labels.last_30_days') }}</a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="#"
                                           data-period="7">{{ __('labels.last_7_days') }}</a>
                                        <a class="dropdown-item active" href="#"
                                           data-period="30">{{ __('labels.last_30_days') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush" id="top-delivery-boys-list">

                            @forelse($topDeliveryBoys as $index => $deliveryBoy)
                                <div class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-warning-lt me-3">{{ $index + 1 }}</span>

                                    <div class="avatar avatar-sm me-3 bg-warning text-white">
                                        @if(!empty($deliveryBoy['avatar']))
                                            <img src="{{ $deliveryBoy['avatar'] }}"
                                                 alt="{{ ($deliveryBoy['name'] ?? "") }}"
                                                 class="rounded">
                                        @else
                                            {{ strtoupper(substr(($deliveryBoy['name'] ?? ""), 0, 2)) }}
                                        @endif
                                    </div>

                                    <div class="flex-fill">
                                        <div class="font-weight-medium text-capitalize">{{ ($deliveryBoy['name'] ?? "") }}</div>
                                        <div class="text-secondary">
                                            {{ $deliveryBoy['total_deliveries'] ?? 0 }} {{ __('labels.deliveries') }}
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <div class="font-weight-medium">{{ $deliveryBoy['total_revenue'] ?? 0 }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center w-100 py-5">
                                    <img src="{{ asset('assets/theme/img/not-found.svg') }}"
                                         alt="No products found"
                                         class="w-100"
                                         style="max-width: 400px; height: auto; margin: 0 auto; display: block;">
                                </div>
                            @endforelse
                        </div>

                    </div>
                </div>
            </div>

            <!-- Categories with Filters Section -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center flex-wrap">
                            <h3 class="card-title">{{ __('labels.categories') }}</h3>
                            <div class="ms-auto">
                                <div class="d-flex gap-2 flex-wrap">
                                    <div class="dropdown ps-2">
                                        <a class="dropdown-toggle text-secondary" href="#"
                                           data-bs-toggle="dropdown"
                                           id="categories-filter">{{ __('labels.all_categories') }}</a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item active" href="#"
                                               data-filter="all">{{ __('labels.all_categories') }}</a>
                                            <a class="dropdown-item" href="#"
                                               data-filter="top_selling">{{ __('labels.top_selling') }}</a>
                                            <a class="dropdown-item" href="#"
                                               data-filter="no_products">{{ __('labels.no_products') }}</a>
                                        </div>
                                    </div>
                                    <div class="dropdown ps-2">
                                        <a class="dropdown-toggle text-secondary" href="#"
                                           data-bs-toggle="dropdown"
                                           id="categories-sort">{{ __('labels.sort_by_products_count') }}</a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="#"
                                               data-sort="name">{{ __('labels.sort_by_name') }}</a>
                                            <a class="dropdown-item active" href="#"
                                               data-sort="products_count">{{ __('labels.sort_by_products_count') }}</a>
                                            <a class="dropdown-item" href="#"
                                               data-sort="total_sold">{{ __('labels.sort_by_total_product_sold') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="categories-grid">
                            @foreach($categoriesWithFilters as $category)
                                <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                                    <div class="card card-sm">
                                        <div class="card-body text-center">
                                            @if($category['image'])
                                                <img src="{{ $category['image'] }}"
                                                     alt="{{ $category['title'] }}"
                                                     class="avatar avatar-lg mb-2 object-contain">
                                            @else
                                                <div
                                                    class="avatar avatar-lg mb-2 object-contain avatar-placeholder">{{ substr($category['title'], 0, 2) }}</div>
                                            @endif
                                            <h4 class="card-title">{{ $category['title'] }}</h4>
                                            <div
                                                class="text-secondary">{{ $category['products_count'] }} {{ __('labels.products') }}</div>
                                            @if(isset($category['total_sold']))
                                                <div
                                                    class="text-success">{{ $category['total_sold'] }} {{ __('labels.sold') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
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
                </div>
            </div>
        </div>
    @endif

@endsection
@push('styles')
    <link rel="stylesheet"
          href="{{asset('assets/vendor/star-rating.js/dist/star-rating.min.css')}}">
@endpush
@push('script')
    <script src="{{asset('assets/vendor/apexcharts/dist/apexcharts.min.js')}}" defer></script>
    <script src="{{asset('assets/vendor/star-rating.js/dist/star-rating.min.js')}}" defer></script>
    <script>
        // Pass dashboard data to JavaScript
        var dashboardData = {
            monthlyRevenueData: @json($adminCommissionChart),
            storeOrderTotals: @json([]),
            dailyPurchaseHistory: @json($dailyPurchaseHistory),
            todaysEarning: @json([]),
            categoryProductWeightage: @json($categoryProductWeightage),
            newUserRegistrationsData: @json($newUserRegistrationsData),
            revenueDataBg: @json($revenueDataBg)
        };
        const commissionData = @json($enhancedCommissionsData['daily_data']);
    </script>
    <script src="{{hyperAsset('assets/js/admin-dashboard.js')}}" defer></script>
@endpush
