@extends('vendor.installer.layouts.master')

@section('title', 'License Verification')
@section('style')
    <link href="{{ asset('installer/froiden-helper/helper.css') }}" rel="stylesheet"/>
    <style>
        .has-error{ color: red; }
        .has-error input{ color: black; border:1px solid red; }
    </style>
@endsection
@section('container')
    <form method="post" action="{{ route('LaravelInstaller::license.verify') }}" id="license-form">
        <div class="form-group">
            <label class="col-sm-2 control-label">Purchase Code</label>
            <div class="col-sm-12">
                <input type="text" name="purchase_code" class="form-control" placeholder="Enter your purchase code" required>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">Domain URL</label>
            <div class="col-sm-12">
                <input type="text" name="domain_url" class="form-control" value="{{ request()->getSchemeAndHttpHost() }}" readonly>
                <small>This URL will be bound to your license.</small>
            </div>
        </div>
        <div class="modal-footer">
            <div class="buttons">
                <button class="btn btn-primary" onclick="verifyLicense();return false">
                    {{ trans('installer_messages.next') }}
                </button>
            </div>
        </div>
    </form>
    <script>
        function verifyLicense() {
            $.easyAjax({
                url: "{!! route('LaravelInstaller::license.verify') !!}",
                type: "GET",
                data: $("#license-form").serialize(),
                container: "#license-form",
                messagePosition: "inline"
            });
        }
    </script>
@stop
@section('scripts')
    <script src="{{ asset('installer/js/jQuery-2.2.0.min.js') }}"></script>
    <script src="{{ asset('installer/froiden-helper/helper.js')}}"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
@endsection
