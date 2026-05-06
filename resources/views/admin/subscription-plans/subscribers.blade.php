@extends('layouts.admin.app', ['page' => $menuAdmin['subscriptions']['active'] ?? "", 'sub_page' => $menuAdmin['subscriptions']['route']['subscribers']['sub_active'] ?? null])

@section('title', __('labels.subscribers'))

@section('header_data')
    @php
        $page_title = __('labels.subscribers');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.subscriptions'), 'url' => route('admin.subscription-plans.index')],
        ['title' => __('labels.subscribers'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.subscribers') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                <select class="form-select" id="planFilter" style="max-width: 150px;">
                                    <option value="">{{ __('labels.plan_filter') }}</option>
                                    @foreach($plans as $plan)
                                        <option
                                            value="{{ $plan->id }}" {{$plan->is_selected ? 'selected' : ''}}>{{ ucfirst($plan->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select" id="statusFilter">
                                    <option value="">{{ __('labels.status_filter') }}</option>
                                    @foreach(($statuses ?? []) as $st)
                                        <option
                                            value="{{ $st }}" {{ (isset($status) && $status === $st) ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <input type="text"
                                       class="form-control"
                                       name="name"
                                       id="select-seller"
                                       multiple
                                       placeholder="{{ __('labels.enter_seller_name') }}"
                                />
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
                <div class="card-table">
                    <div class="row w-full p-3">
                        <x-datatable id="subscription-subscribers-table" :columns="$columns"
                                     route="{{ isset($planId) && $planId ? route('admin.subscription-plans.subscribers.datatable', ['plan_id' => $planId]) : route('admin.subscription-plans.subscribers.datatable') }}"
                                     :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{hyperAsset('assets/js/subscribers.blade.php.js')}}" defer></script>
@endpush
