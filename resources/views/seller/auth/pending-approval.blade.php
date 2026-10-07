@extends('layouts.seller.guest')

@section('title', 'Seller access approval')

@section('content')
    <div class="text-center mb-4">
        <a href="{{ route('seller.login') }}" class="navbar-brand navbar-brand-autodark">
            @if(!empty($systemSettings['logo']))
                <img src="{{ $systemSettings['logo'] }}" alt="{{ $systemSettings['appName'] ?? config('app.name') }}" width="150">
            @else
                <img src="{{ asset('logos/hyper-local-logo.png') }}" alt="{{ $systemSettings['appName'] ?? config('app.name') }}" width="150">
            @endif
        </a>
    </div>
    <div class="card card-md">
        <div class="card-body text-center p-4 p-md-5">
            <span class="avatar avatar-lg bg-yellow-lt text-yellow mb-3">
                <i class="ti ti-clock-hour-4 fs-1" aria-hidden="true"></i>
            </span>
            <h1 class="h2 mb-2">Approval pending</h1>
            <p class="text-secondary mb-4">
                Your sign-in request was sent to the seller administrator. This page will stay here until access is approved.
            </p>
            <div class="alert alert-warning text-start" role="status" aria-live="polite" id="approval-status-message">
                Your request is still waiting for approval.
            </div>
            <button type="button" class="btn btn-primary w-100" id="check-approval-button"
                    data-status-url="{{ route('seller.login.pending.status') }}">
                <i class="ti ti-refresh me-1" aria-hidden="true"></i>
                Check approval and open dashboard
            </button>
            <div class="small text-secondary mt-3" id="approval-check-feedback" aria-live="polite"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('check-approval-button');
            const message = document.getElementById('approval-status-message');
            const feedback = document.getElementById('approval-check-feedback');

            button.addEventListener('click', async function () {
                button.disabled = true;
                feedback.textContent = 'Checking approval status…';

                try {
                    const response = await fetch(button.dataset.statusUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const result = await response.json();

                    if (response.status === 401) {
                        window.location.assign(@json(route('seller.login')));
                        return;
                    }
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Unable to check approval status.');
                    }

                    if (result.data.approved) {
                        message.className = 'alert alert-success text-start';
                        message.textContent = 'Access approved. Opening your dashboard…';
                        window.location.assign(result.data.redirect_url);
                        return;
                    }

                    message.className = 'alert alert-warning text-start';
                    message.textContent = 'Your request is still waiting for approval. Please check again after the seller administrator approves it.';
                    feedback.textContent = 'Not approved yet.';
                } catch (error) {
                    feedback.textContent = error.message || 'Unable to check approval status. Please try again.';
                } finally {
                    button.disabled = false;
                }
            });
        });
    </script>
@endpush
