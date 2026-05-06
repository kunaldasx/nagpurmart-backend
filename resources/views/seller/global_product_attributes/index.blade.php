@php use App\Enums\Attribute\AttributeTypesEnum; @endphp
@extends('layouts.seller.app', ['page' => $menuSeller['attributes']['active'] ?? ""])

@section('title', __('labels.attributes'))

@section('header_data')
    @php
        $page_title = __('labels.attributes');
        $page_pretitle = __('labels.seller') . " " . __('labels.attributes');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.attributes'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.attributes') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                <select class="form-select text-capitalize" id="typeFilter">
                                    <option value="">{{ __('labels.transaction_type') }}</option>
                                    @foreach(AttributeTypesEnum::values() as $value)
                                        <option
                                            value="{{$value}}">{{Str::replace("_", " ", $value)}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto ms-auto">
                                @if($createPermission)
                                    <div class="btn-list">
                                        <span class="d-flex flex-column flex-md-row gap-1">
                                            <button type="button" class="btn bg-primary-lt" data-bs-toggle="modal"
                                                    data-bs-target="#attribute-create-update-modal">
                                                <i class="ti ti-plus fs-3"></i>
                                                {{ __('labels.create_attribute') }}
                                            </button>
                                            <button type="button" class="btn bg-indigo-lt" data-bs-toggle="modal"
                                                    data-bs-target="#attribute-value-create-update-modal">
                                                <i class="ti ti-plus fs-3"></i>
                                                {{ __('labels.create_attribute_value') }}
                                            </button>
                                        </span>
                                    </div>
                                @endif
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
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                        <li class="nav-item">
                            <a href="#tabs-profile-ex1" class="nav-link active" data-bs-toggle="tab">
                                {{ __('labels.attributes') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#tabs-profile-ex2" class="nav-link" data-bs-toggle="tab">
                                {{ __('labels.attribute_values') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">

                        <div class="tab-pane active show" id="tabs-profile-ex1">

                            <div>
                                <x-datatable id="attributes-table" :columns="$columns"
                                             route="{{ route('seller.attributes.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>

                        <div class="tab-pane" id="tabs-profile-ex2">
                            <div>
                                <x-datatable id="attribute-values-table" :columns="$valuesColumns"
                                             route="{{ route('seller.attributes.values.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(($createPermission ?? false) || ($editPermission ?? false))
        <div
            class="modal modal-blur fade"
            id="attribute-create-update-modal"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            data-bs-backdrop="static"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form class="form-submit" action="{{ route('seller.attributes.store') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('labels.create_new_attribute') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="title">{{ __('labels.title') }}</label>
                                <input type="text" id="title" name="title" class="form-control"
                                       placeholder="{{__('labels.attribute_name')}}"
                                       required {{ old('title') ? 'value=' . old('title') : '' }}>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="label">{{ __('labels.label') }}</label>
                                <input type="text" id="label" name="label" class="form-control"
                                       placeholder="{{__('labels.label')}}"
                                       required {{ old('label') ? 'value=' . old('label') : '' }}>
                            </div>
                            <div class="mb-3">
                                <label for="type" class="form-label">{{ __('labels.swatche_type') }}</label>
                                <select class="form-select" id="swatche_type" name="swatche_type" required>
                                    {{-- text,number,select,radio,checkbox,color,image --}}
                                    <option value="" selected>{{ __('labels.select_type') }}</option>
                                    @foreach($attributeTypes as $type)
                                        <option value="{{$type}}">{{ $type }}</option>
                                    @endforeach

                                </select>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <a href="#" class="btn"
                               data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary">
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
                                    class="icon icon-2"
                                >
                                    <path d="M12 5l0 14"/>
                                    <path d="M5 12l14 0"/>
                                </svg>
                                {{ __('labels.create_new_attribute') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div
            class="modal modal-blur fade"
            id="attribute-value-create-update-modal"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            data-bs-backdrop="static"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form class="form-submit attribute-value-form"
                          action="{{ route('seller.attributes.values.store') }}"
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('labels.create_attribute_value') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="attribute_id" class="form-label">{{ __('labels.attribute') }}</label>
                                    <select class="form-select" id="attribute_id" name="attribute_id" required>
                                        <option value="">{{ __('form.selectAttribute') }}</option>
                                        {{--                                    @foreach ($attributes as $attribute)--}}
                                        {{--                                        <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>--}}
                                        {{--                                    @endforeach--}}
                                    </select>
                                </div>
                            </div>
                            <div id="dynamic-fields-container">
                                <!-- Initial Field Set -->
                                <div class="field-group mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row align-items-end">
                                                <!-- Value (machine name) -->
                                                <div class="col-md-2">
                                                    <div class="mb-3">
                                                        <label for="values[]"
                                                               class="form-label">{{ __('form.value') }}</label>
                                                        <input type="text" name="values[]" class="form-control"
                                                               placeholder="{{ __('labels.eg_red') }}" required
                                                            {{ old('value') ? 'value=' . old('value') : '' }}>
                                                    </div>
                                                </div>

                                                <!-- Dynamic swatche Value field -->
                                                <div class="col-md-2">
                                                    <div class="mb-3 swatche-value-container">
                                                        <label for="swatche_value[]"
                                                               class="form-label">{{ __('form.swatcheValue') }}</label>
                                                        <input type="text" name="swatche_value[]"
                                                               class="form-control swatche-value"
                                                               placeholder="{{ __('labels.enter_swatche_value') }}"
                                                               required>
                                                    </div>
                                                </div>
                                                <!-- Remove Button -->
                                                <div class="col-md-1">
                                                    <div class="mb-3 text-end">
                                                        <button type="button" class="btn btn-danger btn-sm remove-field"
                                                                style="display: none;">
                                                            <i class="ti ti-minus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-success w-100" id="add-more-fields">
                                        <i class="ti ti-plus"></i> {{ __('labels.add_more_values') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <a href="#" class="btn"
                               data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary">
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
                                    class="icon icon-2"
                                >
                                    <path d="M12 5l0 14"/>
                                    <path d="M5 12l14 0"/>
                                </svg>
                                {{ __('labels.create_new_attribute_value') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
@push('scripts')
    <script src="{{hyperAsset('assets/js/attribute.js')}}" defer></script>
@endpush
