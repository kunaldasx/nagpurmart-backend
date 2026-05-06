@php use App\Enums\Subscription\SubscriptionPlanKeyEnum;use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['subscriptions']['active'] ?? "", 'sub_page' => $menuAdmin['subscriptions']['route']['plans']['sub_active'] ?? null])

@section('title', (isset($plan) && ($plan->id ?? false)) ? __('labels.edit') : __('labels.add') . "  " . __('labels.subscription_plans'))

@section('header_data')
    @php
        $page_title = __('labels.subscription_plans');
        $page_pretitle = isset($plan) && ($plan->id ?? false) ? __('labels.edit') : __('labels.add');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.subscription_plans'), 'url' => route('admin.subscription-plans.index')],
        ['title' => (isset($plan) && ($plan->id ?? false)) ? __('labels.edit') : __('labels.add'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ (isset($plan) && ($plan->id ?? false)) ? __('labels.edit') : __('labels.add') }} {{ __('labels.subscription_plans') }}</h2>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ (isset($plan) && ($plan->id ?? false)) ? __('labels.edit') : __('labels.add') }} {{ __('labels.subscription_plans') }}</h3>
                </div>
                <form class="form-submit" method="POST"
                      action="{{ (isset($plan) && ($plan->id ?? false)) ? route('admin.subscription-plans.update', $plan->id) : route('admin.subscription-plans.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('labels.name') }}</label>
                                <input type="text" name="name" class="form-control"
                                       value="{{ old('name', $plan->name ?? '') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('labels.price') }}</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text"> {{$systemSettings['currencySymbol']}} </span>
                                    <input type="number" class="form-control" step="1" min="0" name="price"
                                           value="{{ old('price', $plan->price ?? 0) }}">
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">{{ __('labels.description') }}</label>
                                <textarea name="description" class="form-control"
                                          rows="3">{{ old('description', $plan->description ?? '') }}</textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('labels.duration_type') }}</label>
                                <select name="duration_type" id="duration_type" class="form-select">
                                    @php $dt = old('duration_type', $plan->duration_type ?? 'unlimited'); @endphp
                                    <option
                                        value="unlimited" {{ $dt === 'unlimited' ? 'selected' : '' }}>{{ __('labels.unlimited') }}</option>
                                    <option
                                        value="days" {{ $dt === 'days' ? 'selected' : '' }}>{{ __('labels.days') }}</option>
                                </select>
                                @error('duration_type')
                                <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3" id="duration_days_wrap">
                                <label class="form-label">{{ __('labels.duration_days') }}</label>
                                <input type="number" min="1" name="duration_days" class="form-control"
                                       value="{{ old('duration_days', $plan->duration_days ?? '') }}">
                                @error('duration_days')
                                <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="mb-3">
                                    <div class="form-label">{{ __('labels.switches') }}</div>
                                    <label class="form-check form-switch form-switch-3">
                                        <input class="form-check-input" type="checkbox" name="is_free"
                                               value="1" {{ old('is_free', $plan->is_free ?? false) ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ __('labels.is_free') }}</span>
                                    </label>
                                    <label class="form-check form-switch form-switch-2">
                                        <input class="form-check-input" type="checkbox" name="is_recommended"
                                               value="1" {{ old('is_recommended', $plan->is_recommended ?? false) ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ __('labels.is_recommended') }}</span>
                                    </label>
                                    <label class="form-check form-switch form-switch-2">
                                        <input class="form-check-input" type="checkbox" name="status"
                                               value="1" {{ old('status', $plan->status ?? true) ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ __('labels.active') }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="form-label required"><span>{{ __('labels.plan_configurations') }}</span><small> ({{__('labels.leave_empty_unlimited')}})</small></div>
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                        <tr>
                                            <th>{{ __('labels.key') }}</th>
                                            <th>{{ __('labels.value') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $limitMap = isset($plan) && ($plan->id ?? false) && isset($plan->limits)
                                                ? $plan->limits->pluck('value','key')->toArray()
                                                : [];
                                        @endphp
                                        @foreach(SubscriptionPlanKeyEnum::values() as $key)
                                            <tr>
                                                <td class="w-25">
                                                    <input type="text" name="{{$key}}"
                                                           class="form-control disabled text-capitalize"
                                                           value="{{ Str::replace("_" , " ", $key) }}"
                                                           readonly disabled>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control" name="{{$key . "_value"}}"
                                                           value="{{ old($key . '_value', $limitMap[$key] ?? '') }}" min="0" placeholder="{{__('labels.leave_empty_unlimited')}}">
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('admin.subscription-plans.index') }}"
                           class="btn btn-outline-secondary">{{ __('labels.cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('labels.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const select = document.getElementById('duration_type');
                const wrap = document.getElementById('duration_days_wrap');

                function toggle() {
                    if (!select) return;
                    if (select.value === 'days') {
                        wrap.classList.remove('d-none');
                    } else {
                        wrap.classList.add('d-none');
                    }
                }

                if (select) {
                    select.addEventListener('change', toggle);
                    toggle();
                }
            })();
        </script>
    @endpush
@endsection
