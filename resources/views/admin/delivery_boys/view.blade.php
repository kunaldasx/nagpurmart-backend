@extends('layouts.admin.app', ['page' => $menuAdmin['delivery_boys']['active'] ?? ""])

@section('title', $deliveryBoy->full_name)

@section('header_data')
    @php
        $page_title = $deliveryBoy->full_name;
        $page_pretitle = __('labels.delivery_boy_details');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.delivery_boys'), 'url' => route('admin.delivery-boys.index')],
        ['title' => $deliveryBoy->full_name, 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <!-- BEGIN PAGE HEADER -->
        <div class="page-header d-print-none">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title text-capitalize">{{ $deliveryBoy->full_name }}
                        - {{ __('labels.delivery_boy') }}</h2>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
                <div class="col-12 col-md-auto ms-auto d-print-none">
                    <div class="btn-list">
                        @if($editPermission ?? false)
                            <button type="button" class="btn btn-primary"
                                    data-id="{{ $deliveryBoy->id }}" data-bs-toggle="modal"
                                    data-bs-target="#verificationStatusModal">
                                {{ __('labels.update_verification_status') }}
                            </button>
                        @endif
                        @if($deletePermission ?? false)
                            <button type="button" class="btn btn-danger"
                                    data-id="{{ $deliveryBoy->id }}" data-bs-toggle="modal"
                                    data-bs-target="#deleteModal">
                                {{ __('labels.delete') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('labels.personal_information') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border: none;">
                                    <tbody>
                                    <tr>
                                        <td class="fw-bold border-0"
                                            style="width: 180px;">{{ __('labels.full_name') }}</td>
                                        <td class="border-0">{{ $deliveryBoy->full_name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.email') }}</td>
                                        <td class="border-0">{{ $deliveryBoy->user->email }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.mobile') }}</td>
                                        <td class="border-0">{{ $deliveryBoy->user->mobile }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.country') }}</td>
                                        <td class="border-0">{{ $deliveryBoy->user->country ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.address') }}</td>
                                        <td class="border-0">{{ $deliveryBoy->address ?? 'N/A' }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('labels.verification_details') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border: none;">
                                    <tbody>
                                    <tr>
                                        <td class="fw-bold border-0"
                                            style="width: 180px;">{{ __('labels.verification_status') }}</td>
                                        <td class="border-0">
                                            <span
                                                class="badge border p-2 {{ $deliveryBoy->verification_status->value === 'verified' ? 'bg-success-lt border-success-subtle' : ($deliveryBoy->verification_status->value === 'rejected' ? 'bg-danger-lt border-danger-subtle' : 'bg-warning-lt border-warning-subtle') }}">
                                                {{ ucfirst(Str::replace("_", " ", $deliveryBoy->verification_status->value)) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.status') }}</td>
                                        <td class="border-0">
                                            <span
                                                class="badge p-2 {{ $deliveryBoy->status === 'active' ? 'bg-success-lt border border-success-subtle' : 'bg-danger-lt border border-danger-subtle' }}">
                                                {{ ucfirst($deliveryBoy->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($deliveryBoy->verification_remark)
                                        <tr>
                                            <td class="fw-bold border-0">{{ __('labels.verification_remark') }}</td>
                                            <td class="border-0">{{ $deliveryBoy->verification_remark }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.registration_date') }}</td>
                                        <td class="border-0">{{ $deliveryBoy->created_at->format('d M Y, H:i') }}</td>
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
                            <h4 class="card-title">{{ __('labels.delivery_information') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0" style="border: none;">
                                    <tbody>
                                    <tr>
                                        <td class="fw-bold border-0"
                                            style="width: 180px;">{{ __('labels.delivery_zone') }}</td>
                                        <td class="border-0">{{ $deliveryBoy->deliveryZone?->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.vehicle_type') }}</td>
                                        <td class="border-0">{{ $deliveryBoy->vehicle_type ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.driver_license_number') }}</td>
                                        <td class="border-0">{{ $deliveryBoy->driver_license_number ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.total_orders') }}</td>
                                        <td class="border-0">
                                            <span
                                                class="badge bg-orange-lt">{{ $deliveryBoy->assignments->count() ?? 0 }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.completed_orders') }}</td>
                                        <td class="border-0">
                                            <span
                                                class="badge bg-primary-lt">{{ $successDelivery ?? 0 }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold border-0">{{ __('labels.rating') }}</td>
                                        <td class="border-0">
                                            <div class="d-flex align-items-center">
                                                @if($reviewData)
                                                    <span class="ml-2 pointer-events-none">
                                <select id="rating-average" class="rating-stars"
                                        data-rating="{{ $reviewData['average_rating'] }}">
                                    <option value="">{{ __('labels.select_a_rating') }}</option>
                                    <option
                                        value="5" {{ round($reviewData['average_rating']) == 5 ? 'selected' : '' }}>{{ __('labels.excellent') }}</option>
                                    <option
                                        value="4" {{ round($reviewData['average_rating']) == 4 ? 'selected' : '' }}>{{ __('labels.very_good') }}</option>
                                    <option
                                        value="3" {{ round($reviewData['average_rating']) == 3 ? 'selected' : '' }}>{{ __('labels.average') }}</option>
                                    <option
                                        value="2" {{ round($reviewData['average_rating']) == 2 ? 'selected' : '' }}>{{ __('labels.poor') }}</option>
                                    <option
                                        value="1" {{ round($reviewData['average_rating']) == 1 ? 'selected' : '' }}>{{ __('labels.terrible') }}</option>
                                </select>
                            </span>
                                                    <span
                                                        class="ms-2">{{ number_format($reviewData->average_rating, 1) }}</span>
                                                @else
                                                    <span class="text-muted">{{ __('labels.no_rating') }}</span>
                                                @endif
                                            </div>
                                        </td>
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
                            <h4 class="card-title">{{ __('labels.documents') }}</h4>
                        </div>
                        <div class="card-body">
                            @if($driverLicenseUrl)
                                <div class="mb-4">
                                    <h5 class="mb-2">{{ __('labels.driver_license') }}</h5>
                                    <div class="d-flex justify-content-center align-items-center">
                                        <a href="{{ $driverLicenseUrl }}" class="img-box-200px-h" target="_blank"
                                           data-fslightbox="gallery">
                                            <img src="{{$driverLicenseUrl}}" alt="" height="300px">
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="mb-4">
                                    <h5 class="mb-2">{{ __('labels.driver_license') }}</h5>
                                    <div class="alert alert-info">
                                        {{ __('labels.no_document_uploaded') }}
                                    </div>
                                </div>
                            @endif

                            @if($vehicleRegistrationUrl)
                                <div class="mb-4">
                                    <h5 class="mb-2">{{ __('labels.vehicle_registration') }}</h5>
                                    <div class="d-flex justify-content-center align-items-center">
                                        <a href="{{ $vehicleRegistrationUrl }}" class="img-box-200px-h" target="_blank"
                                           data-fslightbox="gallery">
                                            <img src="{{$vehicleRegistrationUrl}}" alt="" height="300px">
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="mb-4">
                                    <h5 class="mb-2">{{ __('labels.vehicle_registration') }}</h5>
                                    <div class="alert alert-info">
                                        {{ __('labels.no_document_uploaded') }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VERIFICATION STATUS MODAL -->
    <div class="modal modal-blur fade" id="verificationStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('labels.update_verification_status') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="form-submit" method="POST"
                      action="{{ route('admin.delivery-boys.update-verification-status', $deliveryBoy->id) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.verification_status') }}</label>
                            <select class="form-select" name="verification_status" required>
                                <option value="">{{ __('labels.select_status') }}</option>
                                @foreach($verificationStatuses as $status)
                                    <option
                                        value="{{ $status }}" {{ $deliveryBoy->verification_status->value === $status ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.verification_remark') }}</label>
                            <textarea class="form-control" name="verification_remark" rows="3"
                                      placeholder="{{ __('labels.optional_remark') }}">{{ $deliveryBoy->verification_remark }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('labels.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="icon mb-2 text-danger icon-lg">
                        <path d="M12 9v4"/>
                        <path
                            d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/>
                        <path d="M12 16h.01"/>
                    </svg>
                    <h3>{{ __('labels.delete_delivery_boy') }}</h3>
                    <div class="text-secondary">{{ __('labels.delete_delivery_boy_confirmation') }}</div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button class="btn btn-outline-secondary w-100"
                                        data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                            </div>
                            <div class="col">
                                <button class="btn btn-danger w-100" id="confirmDelete"
                                        data-bs-dismiss="modal">{{ __('labels.delete') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{asset('assets/vendor/star-rating.js/dist/star-rating.min.css')}}">
@endpush
@push('scripts')
    <script src="{{asset('assets/vendor/star-rating.js/dist/star-rating.min.js')}}" defer></script>
    <script src="{{asset('assets/js/delivery-boy.js')}}"></script>
@endpush
