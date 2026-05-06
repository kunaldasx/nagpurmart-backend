@extends('layouts.seller.app', ['page' => $menuSeller['subscriptions']['active'] ?? "", 'sub_page' => $menuSeller['subscriptions']['route']['plans']['sub_active'] ?? null])

@section('title', __('labels.pricing'))

@section('header_data')
    @php
        $page_title = __('labels.pricing');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.pricing'), 'url' => null],
    ];
@endphp

@section('seller-content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ __('labels.pricing') }}</h2>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body p-0">
                    @include('components.subscription-pricing', ['panel' => $panel, 'plans' => $plans, 'subscriptionSettings' => $subscriptionSettings])
                </div>
            </div>
        </div>
    </div>
@endsection
