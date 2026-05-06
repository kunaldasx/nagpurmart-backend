@extends('vendor.installer.layouts.master')

@section('title', trans('installer_messages.final.title'))
@section('container')
    <div class="paragraph" style="max-width: 600px; margin: 0 auto;">
        <h3 style="text-align:center; margin-bottom: 15px;">Installation Finished</h3>
        <p style="text-align:center;">Following user has been created for admin access:</p>

        <?php
        // Prefer explicit details passed via query parameters (no sessions)
        $details = isset($details) && is_array($details) ? $details : [
            'name' => null,
            'email' => null,
            'mobile' => null,
            'password' => null,
        ];
        ?>

        <ul style="list-style: none; padding: 0;">
            <li><strong>Name:</strong> {{ $details['name'] ?? ($user->name ?? '-') }}</li>
            <li><strong>Email:</strong> {{ $details['email'] ?? ($user->email ?? '-') }}</li>
            <li><strong>Mobile:</strong> {{ $details['mobile'] ?? ($user->mobile ?? '-') }}</li>
            <li><strong>Password:</strong> {{ $details['password'] ?? 'Not available' }}</li>
        </ul>
    </div>
    <div class="buttons">
        <a href="{{ url('/admin') }}" class="btn btn-primary">{{ trans('installer_messages.final.exit') }}</a>
    </div>
@stop
