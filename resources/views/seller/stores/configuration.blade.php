@php use App\Enums\Store\StoreStatusEnum; @endphp
@extends('layouts.seller.app', ['page' => $menuSeller['stores']['active'] ?? ""])

@section('title', __('labels.store_configuration'))

@section('header_data')
    @php
        $page_title = __('labels.store_configuration');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.store_configuration'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="page-wrapper">
        @include('components.page_header', ['title' => $store->name . ' Store Configuration', 'step' => 2])

        <!-- BEGIN PAGE BODY -->
        <!-- resources/views/admin/sellers/form.blade.php -->
        <div class="page-body">
            <div class="container-xl">
                <div class="row g-5">
                    <div class="col-sm-2 d-none d-lg-block">
                        <div class="sticky-top">
                            <h3>{{ __('labels.menu') }}</h3>
                            <nav class="nav nav-vertical nav-pills" id="pills">
                                <a class="nav-link" href="#pills-scheduling">{{ __('labels.scheduling') }}</a>
                                {{--                                <a class="nav-link" href="#pills-delivery">{{ __('labels.delivery_settings') }}</a>--}}
                                <a class="nav-link" href="#pills-store">{{ __('labels.store_information') }}</a>
                                <a class="nav-link" href="#pills-policies">{{ __('labels.policies') }}</a>
                                <a class="nav-link" href="#pills-metadata">{{ __('labels.metadata') }}</a>
                            </nav>
                        </div>
                    </div>
                    <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                        <div class="row row-cards">
                            <div class="col-12">
                                <form action="{{route('seller.stores.store_configuration', ['id' =>$store->id])}}"
                                      class="form-submit" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12">
                                            <!-- Scheduling -->
                                            <div class="card mb-4" id="pills-scheduling">
                                                <div class="card-header">
                                                    <h4 class="card-title">{{ __('labels.scheduling') }}</h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label required">{{ __('labels.timing') }}</label>
                                                        <input type="text" class="form-control" name="timing"
                                                               placeholder="{{ __('labels.enter_timing') }}"
                                                               value="{{ $store->timing ?? '' }}"/>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label required">{{ __('labels.order_preparation_time') }}</label>
                                                        <input type="number" min="0" class="form-control"
                                                               name="order_preparation_time"
                                                               placeholder="{{ __('labels.enter_order_preparation_time') }}"
                                                               value="{{ old('order_preparation_time', $store->order_preparation_time ?? '') }}"/>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label required">{{ __('labels.store_status') }}</label>
                                                        <select class="form-select text-capitalize" name="status" required>
                                                            <option value="">{{ __('labels.select_status') }}</option>
                                                            @foreach(StoreStatusEnum::values() as $status)
                                                                <option
                                                                    value="{{$status}}" {{ $store->status == $status ? 'selected' : '' }}>
                                                                    {{$status}}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div
                                                            class="form-text">{{ __('labels.store_status_help') }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {{--                                        <div class="col-md-6">--}}

                                        {{--                                            <!-- Delivery Settings -->--}}
                                        {{--                                            <div class="card mb-4" id="pills-delivery">--}}
                                        {{--                                                <div class="card-header">--}}
                                        {{--                                                    <h4 class="card-title">{{ __('labels.delivery_settings') }}</h4>--}}
                                        {{--                                                </div>--}}
                                        {{--                                                <div class="card-body">--}}
                                        {{--                                                    <div class="mb-3">--}}
                                        {{--                                                        <label--}}
                                        {{--                                                            class="form-label required">{{ __('labels.max_delivery_distance') }}</label>--}}
                                        {{--                                                        <input type="number"  min="0" class="form-control"--}}
                                        {{--                                                               name="max_delivery_distance"--}}
                                        {{--                                                               placeholder="{{ __('labels.enter_max_delivery_distance') }}"--}}
                                        {{--                                                               value="{{ old('max_delivery_distance', $store->max_delivery_distance ?? '') }}"/>--}}
                                        {{--                                                    </div>--}}
                                        {{--                                                    <div class="mb-3">--}}
                                        {{--                                                        <label--}}
                                        {{--                                                            class="form-label required">{{ __('labels.domestic_shipping_charges') }}</label>--}}
                                        {{--                                                        <input type="number"  min="0" step="0.01" class="form-control"--}}
                                        {{--                                                               name="domestic_shipping_charges"--}}
                                        {{--                                                               placeholder="{{ __('labels.enter_domestic_shipping_charges') }}"--}}
                                        {{--                                                               value="{{ old('domestic_shipping_charges', $store->domestic_shipping_charges ?? '') }}"/>--}}
                                        {{--                                                    </div>--}}
                                        {{--                                                    <div class="mb-3">--}}
                                        {{--                                                        <label--}}
                                        {{--                                                            class="form-label required">{{ __('labels.international_shipping_charges') }}</label>--}}
                                        {{--                                                        <input type="number"  min="0" step="0.01" class="form-control"--}}
                                        {{--                                                               name="international_shipping_charges"--}}
                                        {{--                                                               placeholder="{{ __('labels.enter_international_shipping_charges') }}"--}}
                                        {{--                                                               value="{{ old('international_shipping_charges', $store->international_shipping_charges ?? '') }}"/>--}}
                                        {{--                                                    </div>--}}
                                        {{--                                                </div>--}}
                                        {{--                                            </div>--}}
                                        {{--                                        </div>--}}
                                    </div>
                                    <!-- Store Information -->
                                    <div class="card mb-4" id="pills-store">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.store_information') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('labels.description') }}</label>
                                                <textarea class="hugerte-mytextarea"
                                                          name="description">{{ old('description', $store->description ?? '') }}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('labels.about_us') }}</label>
                                                <textarea class="hugerte-mytextarea" s
                                                          name="about_us">{{ old('about_us', $store->about_us ?? '') }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Policies -->
                                    <div class="card mb-4" id="pills-policies">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.policies') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label">{{ __('labels.promotional_text') }}</label>
                                                        <textarea class="hugerte-mytextarea" name="promotional_text"
                                                                  placeholder="{{ __('labels.enter_promotional_text') }}">{{ old('promotional_text', $store->promotional_text ?? '') }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label required">{{ __('labels.return_replacement_policy') }}</label>
                                                        <textarea class="hugerte-mytextarea"
                                                                  name="return_replacement_policy"
                                                                  placeholder="{{ __('labels.enter_return_replacement_policy') }}">{{ old('return_replacement_policy', $store->return_replacement_policy ?? '') }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label required">{{ __('labels.refund_policy') }}</label>
                                                        <textarea class="hugerte-mytextarea" name="refund_policy"
                                                                  placeholder="{{ __('labels.enter_refund_policy') }}">{{ old('refund_policy', $store->refund_policy ?? '') }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label required">{{ __('labels.terms_and_conditions') }}</label>
                                                        <textarea class="hugerte-mytextarea" name="terms_and_conditions"
                                                                  placeholder="{{ __('labels.enter_terms_and_conditions') }}">{{ old('terms_and_conditions', $store->terms_and_conditions ?? '') }}</textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.delivery_policy') }}</label>
                                                <textarea class="hugerte-mytextarea" name="delivery_policy"
                                                          placeholder="{{ __('labels.enter_delivery_policy') }}">{{ old('delivery_policy', $store->delivery_policy ?? '') }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Metadata -->
                                    <div class="card mb-4" id="pills-metadata">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.status_and_metadata') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('labels.metadata') }}</label>
                                                <textarea class="form-control" name="metadata"
                                                          placeholder="{{ __('labels.enter_metadata') }}">{{ old('metadata', $store->metadata ?? '') }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer text-end">
                                        <div class="d-flex">
                                            <button type="submit"
                                                    class="btn btn-primary ms-auto">Submit
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
