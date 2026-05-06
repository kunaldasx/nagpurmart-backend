@extends('vendor.installer.layouts.master')

@section('title', trans('installer_messages.permissions.title'))
@section('container')
    @if (isset($permissions['errors']))
        <div class="alert alert-danger">Please fix the below error and the click  {{ trans('installer_messages.checkPermissionAgain') }}</div>
    @endif
    <ul class="list">
        @foreach($permissions['permissions'] as $permission)
        <li class="list__item list__item--permissions {{ $permission['isSet'] ? 'success' : 'error' }}">
            {{ $permission['folder'] }}<span>{{ $permission['permission'] }}</span>
        </li>
        @endforeach
    </ul>


    <div class="buttons">
        @if ( ! isset($permissions['errors']))
            <a class="btn btn-primary" href="{{ route('LaravelInstaller::database') }}">
                {{ trans('installer_messages.next') }}
            </a>
        @else
            <a class="btn btn-primary" href="javascript:window.location.href='';">
                {{ trans('installer_messages.checkPermissionAgain') }}
            </a>
        @endif
    </div>

@stop
