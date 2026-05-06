@extends('layouts.admin.guest')

@section('title', __('labels.license_verification'))
@section('content')
    <div>
        <div class="page page-center">
            <div class="container container-tight py-4 mb-4">
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
                @if(!empty(request('message')))
                    <div class="alert alert-danger mt-2" role="alert">
                        <div class="alert-icon">
                            <!-- Download SVG icon from http://tabler.io/icons/icon/alert-triangle -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" class="icon alert-icon icon-2">
                                <path d="M12 9v4"></path>
                                <path
                                    d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"></path>
                                <path d="M12 16h.01"></path>
                            </svg>
                        </div>
                        <div>

                            {{request('message') ?? ""}}
                        </div>
                    </div>
                @endif
                <div class="card card-md">
                    <div class="card-body">
                        <h2 class="h2 text-center mb-4">{{__('labels.license_verification')}}</h2>

                        <form class="form-submit" action="{{ route('license.revalidate.verify') }}" method="post">
                            @csrf
                            <input type="hidden" name="intended" value="{{ $intended ?? url('/') }}">
                            <div class="space-y">
                                <div>
                                    <label class="form-label"> Purchase Code </label>
                                    <input type="text" placeholder="Enter purchase code" class="form-control"
                                           id="purchase_code" name="purchase_code">
                                </div>
                                <div>
                                    <label class="form-label"> Domain URL </label>
                                    <input type="text" class="form-control" id="domain_url" name="domain_url"
                                           value="{{ $domain ?? request()->getSchemeAndHttpHost() }}" readonly>
                                    <small>This URL will be bound to your license.</small>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary w-100">Verify & Continue</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
