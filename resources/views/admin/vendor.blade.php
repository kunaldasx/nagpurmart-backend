@php
    use App\Enums\SystemVendorTypeEnum;
    $systemSettings = $systemSettings ?? ($settings ?? []);
@endphp
@extends('layouts.admin.guest')

@section('title', __('labels.system_type'))
@section('content')
    <div>
        <div class="page page-center">
            <div class="container-fluid py-4">
                <div class="text-center mb-4">
                    <div class="text-center mb-4">
                        <!-- BEGIN NAVBAR LOGO -->
                        <a href="." class="navbar-brand navbar-brand-autodark">
                            @if(($systemSettings['demoMode'] ?? false))
                                <img
                                    src="{{asset('logos/hyper-local-logo.png')}}"
                                    alt="{{$systemSettings['appName'] ?? ""}}" width="150px">
                            @else
                                <img
                                    src="{{!empty($systemSettings['logo'])?$systemSettings['logo'] : asset('logos/hyper-local-logo.png')}}"
                                    alt="{{$systemSettings['appName'] ?? ""}}" width="150px">
                            @endif
                        </a>
                        <!-- END NAVBAR LOGO -->
                    </div>
                    <div class="card card-md">
                        <div class="card-body">
                            <h2 class="h2 text-center mb-4">Choose Your Store's Business Model</h2>
                            <p class="text-center">This is a one-time configuration that defines how your store
                                operates.
                                Select <strong>Multi Vendor</strong> to allow multiple sellers to list and manage their
                                own
                                products — ideal for building a marketplace. Choose <strong>Single Vendor</strong> if
                                you're
                                running your own store and managing all products yourself.</p>
                            <form id="system-select-form" action="{{ route('admin.system-type.store') }}"
                                  method="post">
                                @csrf
                                <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                    <label class="form-selectgroup-item flex-fill">
                                        <input type="radio" name="systemVendorType"
                                               value="{{SystemVendorTypeEnum::MULTIPLE()}}"
                                               class="form-selectgroup-input" {{ (old('systemVendorType', $systemSettings['systemVendorType'] ?? SystemVendorTypeEnum::MULTIPLE()) == SystemVendorTypeEnum::MULTIPLE()) ? 'checked' : '' }}>
                                        <div class="form-selectgroup-label d-flex align-items-center p-3">
                                            <div class="me-3">
                                                <span class="form-selectgroup-check rounded-5"></span>
                                            </div>
                                            <div>
                                                <p class="mb-0 fw-bold">Multi Vendor</p>
                                                <p class="mb-0 text-muted small">Multiple sellers manage their own
                                                    products in a shared marketplace.</p>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="form-selectgroup-item flex-fill">
                                        <input type="radio" name="systemVendorType"
                                               value="{{SystemVendorTypeEnum::SINGLE()}}"
                                               class="form-selectgroup-input" {{ (old('systemVendorType', $systemSettings['systemVendorType'] ?? '') == SystemVendorTypeEnum::SINGLE()) ? 'checked' : '' }}>
                                        <div class="form-selectgroup-label d-flex align-items-center p-3">
                                            <div class="me-3">
                                                <span class="form-selectgroup-check rounded-5"></span>
                                            </div>
                                            <div>
                                                <p class="mb-0 fw-bold">Single Vendor</p>
                                                <p class="mb-0 text-muted small">You manage all products and orders
                                                    entirely on your own.</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <!-- Single vendor additional seller user form -->
                                <div id="single-seller-fields" class="mt-3" style="display: none;">
                                    <div class="alert alert-info" role="alert">
                                        Please provide credentials for your store owner (seller) account. This will
                                        create a new seller user.
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label" for="seller_name">Seller Name</label>
                                            <input type="text" class="form-control" name="seller_name" id="seller_name"
                                                   placeholder="John Doe">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label" for="seller_mobile">Seller Mobile</label>
                                            <input type="tel" class="form-control" name="seller_mobile"
                                                   id="seller_mobile" placeholder="70****1689">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label" for="seller_email">Seller Email</label>
                                            <input type="email" class="form-control" name="seller_email"
                                                   id="seller_email" placeholder="owner@example.com">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label" for="seller_password">Password</label>
                                            <input type="password" class="form-control" name="seller_password"
                                                   id="seller_password" placeholder="********">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label" for="seller_password_confirmation">Confirm
                                                Password</label>
                                            <input type="password" class="form-control"
                                                   name="seller_password_confirmation" id="seller_password_confirmation"
                                                   placeholder="********">
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">Confirm & Continue</button>
                                </div>
                            </form>

                            <div class="alert alert-warning mt-3" role="alert">
                                <div class="alert-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" class="icon alert-icon icon-2">
                                        <path d="M12 9v4"></path>
                                        <path
                                            d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"></path>
                                        <path d="M12 16h.01"></path>
                                    </svg>
                                </div>
                                <strong>This selection is permanent.</strong>Once confirmed, your business model cannot
                                be changed. Make sure you've chosen the right option for your store before proceeding.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{hyperAsset('assets/js/system-type.js')}}" defer></script>
@endpush
