@php use App\Enums\ActiveInactiveStatusEnum; @endphp
@extends('layouts.admin.app', ['page' => $menuSeller['delivery_zones']['active'] ?? ""])

@section('title', __('labels.delivery_zones'))
@section('header_data')
    @php
        $page_title =  __('labels.delivery_zones');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' =>  __('labels.delivery_zones'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ __('labels.delivery_zones') }}
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        {{ __('labels.add_delivery_zone') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <div class="row row-cards">
            <div class="col-12">
                @if(!$googleApiKey)
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <div class="alert-icon">
                            <!-- Download SVG icon from http://tabler.io/icons/icon/alert-circle -->
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
                                class="icon alert-icon icon-2"
                            >
                                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/>
                                <path d="M12 8v4"/>
                                <path d="M12 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="alert-heading"><a
                                    href="{{route('admin.settings.show', ['setting' => \App\Enums\SettingTypeEnum::AUTHENTICATION()])}}"
                                    target="_blank"> {{__('messages.google_api_key_not_found')}} </a>
                            </h4>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('labels.select_delivery_zones') }}</h3>
                    </div>
                    <form
                        action="{{ empty($deliveryZone) ? route('admin.delivery-zones.store') : route('admin.delivery-zones.update', ['id' => $deliveryZone->id]) }}"
                        method="POST" class="form-submit">
                        @csrf
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center mb-3 flex-wrap gap-1">
                                <button type="button" class="btn btn-danger"
                                        id="clear-last">{{ __('labels.remove_polygon') }}</button>
                                @if(!empty($deliveryZone))
                                    <button type="button" class="btn btn-warning" id="reset-zone">
                                        Reset to Original Zone
                                    </button>
                                @endif
                            </div>

                            <input type="hidden" name="center_latitude" id="center-latitude"
                                   value="{{$deliveryZone->center_latitude ?? ""}}">
                            <input type="hidden" name="center_longitude" id="center-longitude"
                                   value="{{$deliveryZone->center_longitude ?? ""}}">
                            <input type="hidden" name="boundary_json" id="boundary-json"
                                   value="{{!empty($deliveryZone) ? json_encode($deliveryZone->boundary_json) : ""}}">
                            <input type="hidden" name="radius_km" id="radius-km"
                                   value="{{$deliveryZone->radius_km ?? ""}}">
                            @if(!empty($deliveryZone))
                                <input type="hidden" id="current-zone-id" value="{{$deliveryZone->id}}" />
                            @endif
                            @if(!empty($deliveryZone))
                                <textarea class="d-none" id="existing-delivery-zone">{{$deliveryZone}}</textarea>
                            @endif
                            <div class="place-autocomplete-card" id="place-autocomplete-card">
                                <p>Search for a place here:</p>
                            </div>
                            <div id="map" style="height: 500px;" class="mb-3 border"></div>
                            <div class="mb-3">
                                <label for="zone-name" class="form-label required">{{ __('labels.zone_name') }}</label>
                                <input type="text" class="form-control" name="name" id="zone-name"
                                       placeholder="{{__('labels.placeholder_zone_name')}}"
                                       value="{{$deliveryZone->name ?? ''}}">
                            </div>
                            <h4 class="mt-4 mb-3">{{ __('labels.delivery_changes_and_details') }}</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="free-delivery-amount" class="form-label">
                                            {{ __('labels.free_delivery_amount') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="{{__('messages.free_delivery_amount_info_message')}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 .48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <input type="number" class="form-control" name="free_delivery_amount"
                                               id="free-delivery-amount"
                                               placeholder="e.g. 500"
                                               value="{{$deliveryZone->free_delivery_amount ?? ''}}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="buffer-time" class="form-label required">
                                            {{ __('labels.buffer_time') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="{{__('messages.buffer_time_info_message')}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <div class="input-group mb-2">
                                            <input type="number" class="form-control" name="buffer_time"
                                                   id="buffer-time"
                                                   placeholder="e.g. 10"
                                                   value="{{$deliveryZone->buffer_time ?? ''}}" min="0">
                                            <span class="input-group-text"> {{__('labels.minutes')}} </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="rush-delivery-enabled"
                                           name="rush_delivery_enabled"
                                           value="1" {{ !empty($deliveryZone) && $deliveryZone->rush_delivery_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label"
                                           for="rush-delivery-enabled">
                                        {{ __('labels.rush_delivery_enabled') }}
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                              title="{{__('messages.rush_delivery_enabled_info_message')}}"><svg
                                                xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                    stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                    d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                    d="M12 16v.01"/><path
                                                    d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rush-delivery-time-per-km" class="form-label">
                                            {{ __('labels.rush_delivery_time_per_km') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="{{__('messages.rush_delivery_time_per_km_info_message')}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <div class="input-group mb-2">
                                            <input type="number" class="form-control" name="rush_delivery_time_per_km"
                                                   id="rush-delivery-time-per-km"
                                                   placeholder="e.g. 3"
                                                   value="{{$deliveryZone->rush_delivery_time_per_km ?? ''}}" min="0">
                                            <span class="input-group-text"> {{__('labels.minutes')}} </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rush-delivery-charges" class="form-label">
                                            {{ __('labels.rush_delivery_charges') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="{{__('messages.rush_delivery_charges_info_message')}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <input type="number" class="form-control" name="rush_delivery_charges"
                                               id="rush-delivery-charges"
                                               placeholder="e.g. 100"
                                               value="{{$deliveryZone->rush_delivery_charges ?? ''}}" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="delivery-time-per-km" class="form-label required">
                                            {{ __('labels.delivery_time_per_km') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="{{__('messages.delivery_time_per_km_info_message')}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <div class="input-group mb-2">
                                            <input type="number" class="form-control" name="delivery_time_per_km"
                                                   id="delivery-time-per-km"
                                                   placeholder="e.g. 5"
                                                   value="{{$deliveryZone->delivery_time_per_km ?? ''}}" min="0">
                                            <span class="input-group-text"> {{__('labels.minutes')}} </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="regular-delivery-charges" class="form-label required">
                                            {{ __('labels.regular_delivery_charges') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="{{__('messages.regular_delivery_charges_info_message')}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <input type="number" class="form-control" name="regular_delivery_charges"
                                               id="regular-delivery-charges"
                                               placeholder="e.g. 50"
                                               value="{{$deliveryZone->regular_delivery_charges ?? ''}}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="distance-based-delivery-charges" class="form-label">
                                            {{ __('labels.distance_based_delivery_charges') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="{{__('messages.distance_based_delivery_charges_info_message')}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <input type="number" class="form-control" name="distance_based_delivery_charges"
                                               id="distance-based-delivery-charges"
                                               placeholder="e.g. 10"
                                               value="{{$deliveryZone->distance_based_delivery_charges ?? ''}}" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="per-store-drop-off-fee" class="form-label">
                                            {{ __('labels.per_store_drop_off_fee') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="{{__('messages.per_store_drop_off_fee_info_message')}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <input type="number" class="form-control" name="per_store_drop_off_fee"
                                               id="per-store-drop-off-fee"
                                               placeholder="e.g. 20"
                                               value="{{$deliveryZone->per_store_drop_off_fee ?? ''}}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="handling-charges" class="form-label">
                                            {{ __('labels.handling_charges') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="{{__('messages.handling_charges_info_message')}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <input type="number" class="form-control" name="handling_charges"
                                               id="handling-charges"
                                               placeholder="e.g. 15"
                                               value="{{$deliveryZone->handling_charges ?? ''}}" min="0">
                                    </div>
                                </div>
                            </div>

                            <h4 class="mt-4 mb-3">{{ __('labels.delivery_boy_earnings') }}</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="delivery-boy-base-fee" class="form-label">
                                            {{ __('labels.base_fee') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="Base fee for delivery boy per order"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <input type="number" class="form-control" name="delivery_boy_base_fee"
                                               id="delivery-boy-base-fee"
                                               placeholder="e.g. 50.00"
                                               step="0.01"
                                               value="{{$deliveryZone->delivery_boy_base_fee ?? ''}}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="delivery-boy-per-store-pickup-fee" class="form-label">
                                            {{ __('labels.per_store_pickup_fee') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="Additional fee for each store pickup"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <input type="number" class="form-control" name="delivery_boy_per_store_pickup_fee"
                                               id="delivery-boy-per-store-pickup-fee"
                                               placeholder="e.g. 15.00"
                                               step="0.01"
                                               value="{{$deliveryZone->delivery_boy_per_store_pickup_fee ?? ''}}" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="delivery-boy-distance-based-fee" class="form-label">
                                            {{ __('labels.distance_based_fee') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="Fee based on delivery distance"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <input type="number" class="form-control" name="delivery_boy_distance_based_fee"
                                               id="delivery-boy-distance-based-fee"
                                               placeholder="e.g. 10.00"
                                               step="0.01"
                                               value="{{$deliveryZone->delivery_boy_distance_based_fee ?? ''}}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="delivery-boy-per-order-incentive" class="form-label">
                                            {{ __('labels.per_order_incentive') }}
                                            <span data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="Additional incentive per order"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-help-octagon"><path
                                                        stroke="none" d="M0 0h24v24H0z" fill="none"/><path
                                                        d="M12.802 2.165l5.575 2.389c.48 .206 .863 .589 1.07 1.07l2.388 5.574c.22 .512 .22 1.092 0 1.604l-2.389 5.575c-.206 .48 -.589 .863 -1.07 1.07l-5.574 2.388c-.512 .22 -1.092 .22 -1.604 0l-5.575 -2.389a2.036 2.036 0 0 1 -1.07 -1.07l-2.388 -5.574a2.036 2.036 0 0 1 0 -1.604l2.389 -5.575c.206 -.48 .589 -.863 1.07 -1.07l5.574 -2.388a2.036 2.036 0 0 1 1.604 0z"/><path
                                                        d="M12 16v.01"/><path
                                                        d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483"/></svg></span>
                                        </label>
                                        <input type="number" class="form-control" name="delivery_boy_per_order_incentive"
                                               id="delivery-boy-per-order-incentive"
                                               placeholder="e.g. 20.00"
                                               step="0.01"
                                               value="{{$deliveryZone->delivery_boy_per_order_incentive ?? ''}}" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">{{ __('labels.status') }}</label>
                                <select name="status" id="status" class="form-control text-capitalize" required>
                                    @foreach(ActiveInactiveStatusEnum::values() as $status)
                                        <option value="{{ $status }}"
                                            {{ !empty($deliveryZone) && $deliveryZone->status == $status ? 'selected' : '' }}
                                        >
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary text-end">{{__('labels.save')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
<style>
    #place-autocomplete-card {
        background-color: #fff;
        border-radius: 5px;
        box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
        margin: 10px;
        padding: 5px;
        font-family: Roboto, sans-serif;
        font-size: large;
        font-weight: bold;
    }

    gmp-place-autocomplete {
        width: 300px;
    }

    #infowindow-content .title {
        font-weight: bold;
    }

    #map #infowindow-content {
        display: inline;
    }
</style>
@push('script')
    <script async defer>(g => {
            var h, a, k, p = "The Google Maps JavaScript API", c = "google", l = "importLibrary", q = "__ib__",
                m = document, b = window;
            b = b[c] || (b[c] = {});
            var d = b.maps || (b.maps = {}), r = new Set, e = new URLSearchParams,
                u = () => h || (h = new Promise(async (f, n) => {
                    await (a = m.createElement("script"));
                    e.set("libraries", [...r] + "");
                    for (k in g) e.set(k.replace(/[A-Z]/g, t => "_" + t[0].toLowerCase()), g[k]);
                    e.set("callback", c + ".maps." + q);
                    a.src = `https://maps.${c}apis.com/maps/api/js?` + e;
                    d[q] = f;
                    a.onerror = () => h = n(Error(p + " could not load."));
                    a.nonce = m.querySelector("script[nonce]")?.nonce || "";
                    m.head.append(a)
                }));
            d[l] ? console.warn(p + " only loads once. Ignoring:", g) : d[l] = (f, ...n) => r.add(f) && u().then(() => d[l](f, ...n))
        })
        ({key: "{{$googleApiKey}}", v: "weekly"});
    </script>
    <script src="{{ hyperAsset('assets/js/delivery-zone.js') }}"></script>
@endpush
