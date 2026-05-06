@extends('layouts.admin.app',  ['page' => $menuAdmin['stores']['active'] ?? ""])

@section('title', $store->name)

@section('header_data')
    @php
        $page_title = $store->name;
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.stores'), 'url' => route('admin.sellers.store.index')],
        ['title' => $store->name, 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <!-- BEGIN PAGE HEADER -->
        <div class="page-header d-print-none">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title text-capitalize">{{$store->name}} {{ __('labels.stores') }}</h2>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('labels.about') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border: none;">
                                    <tbody>
                                    <tr>
                                        <td class="fw-bold border-0" style="width: 180px;">{{ __('labels.name') }}</td>
                                        <td class="border-0">{{ $store->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.visibility_status') }}</td>
                                        <td class="border-0">
                                            <span
                                                class="badge p-2 bg-info-lt border border-info-subtle">{{ ucfirst(Str::replace("_", " ",$store->visibility_status->value)) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.verification_status') }}</td>
                                        <td class="border-0">
                                            <span
                                                class="badge border p-2 {{ $store->verification_status->value === 'approved' ? 'bg-success-lt border-success-subtle' : 'bg-danger-lt border-danger-subtle' }}">
                                                {{ ucfirst(Str::replace("_", " ",$store->verification_status->value)) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.fulfillment_type') }}</td>
                                        <td class="border-0">{{ ucfirst($store->fulfillment_type->value) }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('labels.contact_information') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border: none;">
                                    <tbody>
                                    <tr>
                                        <td class="fw-bold border-0"
                                            style="width: 180px;">{{ __('labels.contact_email') }}</td>
                                        <td class="border-0">{{ $store->contact_email }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.contact_number') }}</td>
                                        <td class="border-0">{{ $store->contact_number }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('labels.location_details') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border: none;">
                                    <tbody>
                                    <tr>
                                        <td class="fw-bold border-0"
                                            style="width: 180px;">{{ __('labels.address') }}</td>
                                        <td class="border-0">{{ $store->address }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.city') }}</td>
                                        <td class="border-0">{{ $store->city }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.state') }}</td>
                                        <td class="border-0">{{ $store->state }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.landmark') }}</td>
                                        <td class="border-0">{{ $store->landmark }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.zipcode') }}</td>
                                        <td class="border-0">{{ $store->zipcode }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.country') }}</td>
                                        <td class="border-0">{{ $store->country }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.country_code') }}</td>
                                        <td class="border-0">{{ $store->country_code }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.latitude') }}</td>
                                        <td class="border-0">{{ $store->latitude }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.longitude') }}</td>
                                        <td class="border-0">{{ $store->longitude }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.delivery_zone_id') }}</td>
                                        <td class="border-0">{{ $store->delivery_zone_id }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('labels.operational_timing') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border: none;">
                                    <tbody>
                                    <tr>
                                        <td class="fw-bold border-0"
                                            style="width: 180px;">{{ __('labels.timing') }}</td>
                                        <td class="border-0">{{ $store->timing }}</td>
                                    </tr>
{{--                                    <tr>--}}
{{--                                        <td class="fw-bold border-0">{{ __('labels.order_preparation_time') }}</td>--}}
{{--                                        <td class="border-0">{{ $store->order_preparation_time }}</td>--}}
{{--                                    </tr>--}}
{{--                                    <tr>--}}
{{--                                        <td class="fw-bold border-0">{{ __('labels.max_delivery_distance') }}</td>--}}
{{--                                        <td class="border-0">{{ $store->max_delivery_distance }}</td>--}}
{{--                                    </tr>--}}
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.store_status') }}</td>
                                        <td class="border-0">
                                            @if($store->status === 'online')
                                                <span class="badge bg-success-lt text-uppercase p-2">
                                                    {{ __('labels.online') }}
                                                </span>
                                            @else
                                                <span class="badge bg-danger-lt text-uppercase p-2">
                                                    {{ __('labels.offline') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row row-cards">
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('labels.financial_bank_details') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border: none;">
                                    <tbody>
                                    <tr>
                                        <td class="fw-bold border-0"
                                            style="width: 180px;">{{ __('labels.currency_code') }}</td>
                                        <td class="border-0">{{ $store->currency_code }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.tax_name') }}</td>
                                        <td class="border-0">{{ $store->tax_name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.tax_number') }}</td>
                                        <td class="border-0">{{ $store->tax_number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.bank_name') }}</td>
                                        <td class="border-0">{{ $store->bank_name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.bank_branch_code') }}</td>
                                        <td class="border-0">{{ $store->bank_branch_code }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.account_holder_name') }}</td>
                                        <td class="border-0">{{ $store->account_holder_name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.account_number') }}</td>
                                        <td class="border-0">{{ $store->account_number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.routing_number') }}</td>
                                        <td class="border-0">{{ $store->routing_number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.bank_account_type') }}</td>
                                        <td class="border-0">{{ $store->bank_account_type }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
{{--                    <div class="card mb-4">--}}
{{--                        <div class="card-header">--}}
{{--                            <h4 class="card-title">{{ __('labels.shipping_delivery') }}</h4>--}}
{{--                        </div>--}}
{{--                        <div class="card-body">--}}
{{--                            <div class="table-responsive">--}}
{{--                                <table class="table mb-0" style="border: none;">--}}
{{--                                    <tbody>--}}
{{--                                    <tr>--}}
{{--                                        <td class="fw-bold border-0"--}}
{{--                                            style="width: 180px;">{{ __('labels.domestic_shipping_charges') }}</td>--}}
{{--                                        <td class="border-0">{{ $store->domestic_shipping_charges }}</td>--}}
{{--                                    </tr>--}}
{{--                                    <tr>--}}
{{--                                        <td class="fw-bold border-0">{{ __('labels.international_shipping_charges') }}</td>--}}
{{--                                        <td class="border-0">{{ $store->international_shipping_charges }}</td>--}}
{{--                                    </tr>--}}
{{--                                    </tbody>--}}
{{--                                </table>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
                </div>
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4 class="card-title">{{ __('labels.logo') }}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <a href="{{ $store->store_logo }}" class="img-box-200px-h" target="_blank"
                                           data-fslightbox="gallery">
                                            <img src="{{$store->store_logo}}" alt="">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4 class="card-title">{{ __('labels.banner') }}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <a href="{{ $store->store_banner }}" class="img-box-200px-h" target="_blank"
                                           data-fslightbox="gallery">
                                            <img src="{{$store->store_banner}}" alt="">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4 class="card-title">{{ __('labels.address_proof') }}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <a href="{{ $store->address_proof }}" class="img-box-200px-h" target="_blank"
                                           data-fslightbox="gallery">
                                            <img src="{{$store->address_proof}}" alt="">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4 class="card-title">{{ __('labels.voided_check') }}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <a href="{{ $store->voided_check }}" class="img-box-200px-h" target="_blank"
                                           data-fslightbox="gallery">
                                            <img src="{{$store->voided_check}}" alt="">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if($verifyPermission ?? false)
                <div class="row row-cards">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="card-title">{{ __('labels.verify_account') }}</h4>
                            </div>
                            <div class="card-body">
                                <form method="post" action="{{route('admin.sellers.store.verify', $store->id)}}"
                                      class="form-submit">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.verification_status') }}</label>
                                                <select class="form-select" name="verification_status">
                                                    <option value="">{{ __('labels.select_status') }}</option>
                                                    @foreach($verificationStatus as $status)
                                                        <option
                                                            value="{{$status}}" {{$status === $store->verification_status->value ? "selected" : ""}}>{{ ucfirst(Str::replace("_", " ",$status))}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="mb-3">
                                                <label
                                                    class="form-label required">{{ __('labels.visibility_status') }}</label>
                                                <select class="form-select" name="visibility_status">
                                                    <option value="">{{ __('labels.select_status') }}</option>
                                                    @foreach($visibilityStatus as $vStatus)
                                                        <option
                                                            value="{{$vStatus}}" {{$vStatus === $store->visibility_status->value ? "selected" : ""}}>{{ ucfirst(Str::replace("_", " ",$vStatus))}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">{{ __('labels.submit') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
