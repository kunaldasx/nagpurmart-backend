@extends('layouts.seller.guest')

@section('title', __('labels.reset_password'))
@section('content')
    <div>
        <div class="page page-center">
            <div class="container container-tight py-4">
                <div class="text-center mb-4">
                    <!-- BEGIN NAVBAR LOGO -->
                    <a href="." class="navbar-brand navbar-brand-autodark">
                        <img src="{{$systemSettings['logo']}}" alt="{{$systemSettings['appName']}}" width="150px">
                    </a>
                    <!-- END NAVBAR LOGO -->
                </div>
                <div class="card card-md">
                    <div class="card-body">
                        <h2 class="h2 text-center mb-4">Reset your password</h2>
                        <p class="text-muted mb-4">Enter your new password below.</p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form action="{{route('seller.password.update')}}" method="post" autocomplete="off" novalidate>
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="email" value="{{ $email }}">

                            <div class="mb-3">
                                <label class="form-label">Email address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       name="email" value="{{ $email ?? old('email') }}"
                                       readonly/>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-group input-group-flat">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                           name="password" placeholder="Enter new password"
                                           autocomplete="off" id="password" required/>
                                    <span class="input-group-text">
                                        <a href="#" class="link-secondary" title="Show password" id="password-toggle"
                                           data-bs-toggle="tooltip">
                                            Show
                                        </a>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group input-group-flat">
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                                           name="password_confirmation" placeholder="Confirm new password"
                                           autocomplete="off" id="password_confirmation" required/>
                                    <span class="input-group-text">
                                        <a href="#" class="link-secondary" title="Show password" id="password-confirmation-toggle"
                                           data-bs-toggle="tooltip">
                                            Show
                                        </a>
                                    </span>
                                </div>
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-footer">
                                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                            </div>
                        </form>
                        <div class="text-center text-muted mt-3">
                            Remember your password? <a href="{{route('seller.login')}}">Sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        document.getElementById('password-toggle').addEventListener('click', function(e) {
            e.preventDefault();
            const passwordField = document.getElementById('password');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
        });

        document.getElementById('password-confirmation-toggle').addEventListener('click', function(e) {
            e.preventDefault();
            const passwordField = document.getElementById('password_confirmation');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
        });
    </script>
@endsection
