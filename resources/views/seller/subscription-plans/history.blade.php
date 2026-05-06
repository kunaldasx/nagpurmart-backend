@extends('layouts.seller.app', ['page' => $menuSeller['subscriptions']['active'] ?? "", 'sub_page' => $menuSeller['subscriptions']['route']['history']['sub_active'] ?? null])

@section('title', __('labels.subscription_history'))

@section('header_data')
    @php
        $page_title = __('labels.subscription_history');
        $page_pretitle = __('labels.subscriptions');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.subscriptions'), 'url' => route('seller.subscription-plans.index')],
        ['title' => __('labels.subscription_history'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('labels.subscription_history') }}</h3>
                            <div class="card-actions">
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <a href="{{ route('seller.subscription-plans.index') }}" class="btn btn-outline-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-left">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M5 12l14 0"/>
                                                <path d="M5 12l6 6"/>
                                                <path d="M5 12l6 -6"/>
                                            </svg>
                                            {{ __('labels.back_to_plans') }}
                                        </a>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('seller.subscription-plans.current') }}" class="btn btn-outline-primary">
                                            {{ __('labels.current_subscription') }}
                                        </a>
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
                                <x-datatable id="subscription-history-table" :columns="$columns"
                                             route="{{ route('seller.subscription-plans.history.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
