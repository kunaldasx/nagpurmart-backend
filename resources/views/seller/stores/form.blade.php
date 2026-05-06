@extends('layouts.seller.app', [
    'page' => $menuSeller['stores']['active'] ?? "",
])

@section('title', __('labels.add_store'))

@section('header_data')
    @php
        $page_title = __('labels.add_store');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.stores'), 'url' => route('seller.stores.index')],
        ['title' => __('labels.add_store'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="page-wrapper">
        @include('components.page_header', ['title' => empty($store) ? "Register New Store" : "Update ".$store->name." Store", 'step' => 2])

        <!-- BEGIN PAGE BODY -->
        <!-- resources/views/admin/sellers/form.blade.php -->
        <div class="page-body">
            <div class="container-xl">
                <div class="row g-5">
                    <div class="col-sm-2 d-none d-lg-block">
                        <div class="sticky-top">
                            <h3>{{ __('labels.menu') }}</h3>
                            <nav class="nav nav-vertical nav-pills" id="pills">
                                <a class="nav-link" href="#pills-basic">{{ __('labels.basic_details') }}</a>
                                <a class="nav-link" href="#pills-location">{{ __('labels.location_details') }}</a>
                                <a class="nav-link" href="#pills-logos">{{ __('labels.logo_and_banner') }}</a>
                                <a class="nav-link" href="#pills-documents">{{ __('labels.business_documents') }}</a>
                                <a class="nav-link" href="#pills-bank">{{ __('labels.bank_details') }}</a>
                            </nav>
                        </div>
                    </div>
                    <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                        <div class="row row-cards">
                            <div class="col-12">
                                <form
                                    action="{{empty($store) ? route('seller.stores.store') : route('seller.stores.update', $store->id ?? '')}}"
                                    class="form-submit" method="post"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <!-- Basic Details -->
                                    <div class="card mb-4" id="pills-basic">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.basic_details') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.store_name') }}</label>
                                                <input type="text" class="form-control" name="name"
                                                       placeholder="{{ __('labels.enter_store_name') }}"
                                                       value="{{ old('name', $store->name ?? '') }} "/>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label required">{{ __('labels.contact_email') }}</label>
                                                        <input type="email" class="form-control" name="contact_email"
                                                               placeholder="{{ __('labels.enter_email_address') }}"
                                                               value="{{ old('contact_email', $store->contact_email ?? '') }}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label required">{{ __('labels.contact_number') }}</label>
                                                        <input type="number" min="0" class="form-control" name="contact_number"
                                                               id="mobile"
                                                               placeholder="{{ __('labels.enter_mobile_number') }}"
                                                               value="{{ old('contact_number', $store->contact_number ?? '') }}" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Location Details -->
                                    <div class="card mb-4" id="pills-location">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.location_details') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div id="autocomplete-container" class="form-row" style="display: none;"></div>
                                            <div class="alert alert-info"> {{__('messages.select_zone_message')}}</div>
                                            <div id="map"></div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.country') }}</label>
                                                <select class="form-select" name="country"
                                                        id="select-countries">
                                                </select>
                                                <input type="hidden" id="selected-country"
                                                       value="{{!empty($store) && $store->country ? $store->country : ""}}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.address') }}</label>
                                                <input type="text" class="form-control" name="address" id="address"
                                                       placeholder="{{ __('labels.enter_address') }}"
                                                       value="{{ old('address', $store->address ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.landmark') }}</label>
                                                <input type="text" class="form-control" name="landmark" id="landmark"
                                                       placeholder="{{ __('labels.enter_landmark') }}"
                                                       value="{{ old('landmark', $store->landmark ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.city') }}</label>
                                                <input type="text" class="form-control" name="city" id="city"
                                                       placeholder="{{ __('labels.enter_city') }}"
                                                       value="{{ old('city', $store->city ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.state') }}</label>
                                                <input type="text" class="form-control" name="state" id="state"
                                                       placeholder="{{ __('labels.enter_state') }}"
                                                       value="{{ old('state', $store->state ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.zipcode') }}</label>
                                                <input type="text" class="form-control" name="zipcode" id="zipcode"
                                                       placeholder="{{ __('labels.enter_zipcode') }}"
                                                       value="{{ old('zipcode', $store->zipcode ?? '') }}"/>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">{{ __('labels.latitude') }}</label>
                                                        <input type="text" class="form-control" name="latitude"
                                                               id="latitude"
                                                               placeholder="{{ __('labels.enter_latitude') }}"
                                                               value="{{ old('latitude', $store->latitude ?? '') }}"/>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">{{ __('labels.longitude') }}</label>
                                                        <input type="text" class="form-control" name="longitude"
                                                               id="longitude"
                                                               placeholder="{{ __('labels.enter_longitude') }}"
                                                               value="{{ old('longitude', $store->longitude ?? '') }}"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card mb-4" id="pills-logos">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.logo_and_banner') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.store_logo') }}</label>
                                                <x-filepond_image name="store_logo"
                                                                  imageUrl="{{$store->store_logo ?? null}}"/>
                                                @error('store_logo')
                                                <div class="text-danger mt-2">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label">{{ __('labels.store_banner') }}</label>
                                                <x-filepond_image name="store_banner"
                                                                  imageUrl="{{$store->store_banner ?? null}}"/>
                                                @error('store_banner')
                                                <div class="text-danger mt-2">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Business Documents -->
                                    <div class="card mb-4" id="pills-documents">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.business_documents') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.address_proof') }}</label>
                                                <x-filepond_image name="address_proof"
                                                                  imageUrl="{{$store->address_proof ?? null}}"
                                                                  disabled="{{!empty($store->address_proof) ? 'true' : 'false'}}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.voided_check') }}</label>
                                                <x-filepond_image name="voided_check"
                                                                  imageUrl="{{$store->voided_check ?? null}}"
                                                                  disabled="{{!empty($store->voided_check) ? 'true' : 'false'}}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.tax_name') }}</label>
                                                <input type="text" class="form-control" name="tax_name"
                                                       placeholder="{{ __('labels.enter_tax_name') }}"
                                                       value="{{ old('tax_name', $store->tax_name ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.tax_number') }}</label>
                                                <input type="text" class="form-control" name="tax_number"
                                                       placeholder="{{ __('labels.enter_tax_number') }}"
                                                       value="{{ old('tax_number', $store->tax_number ?? '') }}"/>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bank Details -->
                                    <div class="card mb-4" id="pills-bank">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ __('labels.bank_details') }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('labels.bank_name') }}</label>
                                                <input type="text" class="form-control" name="bank_name"
                                                       placeholder="{{ __('labels.enter_bank_name') }}"
                                                       value="{{ old('bank_name', $store->bank_name ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.bank_branch_code') }}</label>
                                                <input type="text" class="form-control" name="bank_branch_code"
                                                       placeholder="{{ __('labels.enter_bank_branch_code') }}"
                                                       value="{{ old('bank_branch_code', $store->bank_branch_code ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.account_holder_name') }}</label>
                                                <input type="text" class="form-control" name="account_holder_name"
                                                       placeholder="{{ __('labels.enter_account_holder_name') }}"
                                                       value="{{ old('account_holder_name', $store->account_holder_name ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.account_number') }}</label>
                                                <input type="number" min="0" class="form-control" name="account_number"
                                                       placeholder="{{ __('labels.enter_account_number') }}"
                                                       value="{{ old('account_number', $store->account_number ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.routing_number') }}</label>
                                                <input type="number" min="0" class="form-control" name="routing_number"
                                                       placeholder="{{ __('labels.enter_routing_number') }}"
                                                       value="{{ old('routing_number', $store->routing_number ?? '') }}"/>
                                            </div>
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.bank_account_type') }}</label>
                                                <select class="form-select" name="bank_account_type">
                                                    @foreach($bankAccountTypes as $accountType)
                                                        <option
                                                            value="{{ $accountType }}" {{!empty($store) && $store->bank_account_type === $accountType ? "selected" : ""}}>{{ $accountType }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer text-end">
                                    <div class="d-flex">
                                            <button type="submit"
                                                    class="btn btn-primary ms-auto">{{empty($store) ? "Submit" : "Update"}}</button>
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
    <style>
        #map {
            height: 400px;
            width: 100%;
            margin-bottom: 20px;
        }

        .form-row {
            margin-bottom: 8px;
        }

        /* Additional styles for the city selector inside map */
        #city-selector-card gmp-place-autocomplete {
            width: 100%;
        }

        #infowindow-content .title {
            font-weight: bold;
        }

        #map #infowindow-content {
            display: inline;
        }
    </style>
@endsection
@push('scripts')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{$googleApiKey}}&libraries=maps,places,marker&callback=initMap"
        async defer>
    </script>
    <script src="{{ hyperAsset('assets/js/stores.js')}}"></script>
@endpush
