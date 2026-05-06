@extends('layouts.admin.app', ['page' => $menuAdmin['system_updates']['active'] ?? ""])


@section('title', __('labels.system_updates'))

@section('header_data')
    @php
        $page_title = __('labels.system_updates');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.system_updates'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="">
        @if (session('update_log'))
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-bet  ween align-items-center">
                    <span>{{ __('labels.latest_update_log') }} @if(session('update_version'))
                            (v{{ session('update_version') }})
                        @endif @if(session('update_id'))
                            #{{ session('update_id') }}
                        @endif</span>
                    <button class="btn btn-dark" type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('latest-update-log').innerText);this.innerText='{{ __('labels.copied') }}';setTimeout(()=>this.innerText='{{ __('labels.copy') }}',1500);">{{ __('labels.copy') }}</button>
                </div>
                <div class="card-body">
                    <pre id="latest-update-log" class="mb-0"
                         style="white-space: pre-wrap; word-break: break-word; max-height: 300px; overflow: auto;">{{ session('update_log') }}</pre>
                </div>
            </div>
        @endif
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">{{ __('labels.system_updates') }}</h3>
                <div class="text-muted">{{ __('labels.current_version') }}:
                    <strong>v{{ $currentVersion ?? config('app.version') }}</strong></div>
            </div>
            <div class="card-body">
                @if($canUpdate)
                    <form id="update-form" method="POST" action="{{ route('admin.system-updates.store') }}"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="package" class="form-label">{{ __('labels.update_zip_file') }}</label>
                            <input type="file" class="form-control" id="package" name="package" required>
                            <div class="form-text">{{ __('labels.update_zip_help') }}</div>
                        </div>
                        <button id="apply-update-btn" type="submit"
                                class="btn btn-primary">{{ __('labels.apply_update') }}</button>
                    </form>

                    <!-- Live Log Panel -->
                    <div id="live-log-card" class="card mt-4 d-none">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>{{ __('labels.live_update_log') }} <span id="live-log-version"
                                                                           class="text-muted"></span></span>
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <span id="live-log-status"
                                      class="badge bg-secondary-lt me-2">{{ __('labels.starting') }}</span>
                                <button class="btn btn-sm btn-outline-secondary" type="button"
                                        id="copy-live-log">{{ __('labels.copy') }}</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <pre id="live-log-text" class="mb-0"
                                 style="white-space: pre-wrap; word-break: break-word; max-height: 300px; overflow: auto;"></pre>
                        </div>
                    </div>
                @else
                    <div class="text-muted">{{ __('labels.no_update_permission') }}</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">{{ __('labels.update_history') }}</h3>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
                <div class="card-actions">
                    <button class="btn btn-outline-primary" id="refresh">
                        {{ __('labels.refresh') }}
                    </button>
                </div>
            </div>
            <div class="card-table">
                <div class="row w-full p-3">
                    <x-datatable id="system-updates-table" :columns="$columns"
                                 route="{{ route('admin.system-updates.datatable') }}"
                                 :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('update-form');
            if (!form) return;

            const liveCard = document.getElementById('live-log-card');
            const liveText = document.getElementById('live-log-text');
            const liveStatus = document.getElementById('live-log-status');
            const liveVersion = document.getElementById('live-log-version');
            const copyBtn = document.getElementById('copy-live-log');
            const applyBtn = document.getElementById('apply-update-btn');

            const latestUrl = '{{ route('admin.system-updates.latest') }}';
            const logUrlTmpl = '{{ route('admin.system-updates.log', ['update' => '__ID__']) }}';

            let pollTimer = null;
            let currentUpdateId = null;

            function setStatus(text, variant) {
                liveStatus.textContent = text;
                liveStatus.className = 'badge ' + (variant || 'bg-secondary');
            }

            function startPolling() {
                if (pollTimer) clearInterval(pollTimer);
                pollTimer = setInterval(async () => {
                    try {
                        let res;
                        if (!currentUpdateId) {
                            res = await fetch(latestUrl, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                        } else {
                            const url = logUrlTmpl.replace('__ID__', currentUpdateId);
                            res = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                        }
                        if (!res.ok) return;
                        const data = await res.json();
                        if (!data) return;

                        // Capture update id asap
                        if (!currentUpdateId && data.id) currentUpdateId = data.id;

                        // Update UI
                        liveText.textContent = data.log || '';
                        if (data.version) liveVersion.textContent = `(v${data.version} #${data.id})`;

                        // Auto-scroll to bottom
                        liveText.scrollTop = liveText.scrollHeight;

                        // Update status
                        const status = (data.status || '').toLowerCase();
                        if (status === 'pending') setStatus("{{ __('labels.processing') }}", 'badge bg-warning-lt');
                        else if (status === 'applied') {
                            setStatus("{{ __('labels.applied') }}", 'badge bg-success-lt');
                            stopPolling();
                            enableForm();
                        } else if (status === 'failed') {
                            setStatus("{{ __('labels.failed') }}", 'badge bg-danger-lt');
                            stopPolling();
                            enableForm();
                        }
                    } catch (e) { /* ignore one-off errors */
                    }
                }, 1000);
            }

            function stopPolling() {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            }

            function disableForm() {
                if (applyBtn) {
                    applyBtn.disabled = true;
                    applyBtn.textContent = "{{ __('labels.applying') }}";
                }
            }

            function enableForm() {
                if (applyBtn) {
                    applyBtn.disabled = false;
                    applyBtn.textContent = "{{ __('labels.apply_update') }}";
                }
            }

            if (copyBtn) {
                copyBtn.addEventListener('click', function () {
                    navigator.clipboard.writeText(liveText.textContent || '');
                    this.textContent = "{{ __('labels.copied') }}";
                    setTimeout(() => this.textContent = "{{ __('labels.copy') }}", 1500);
                });
            }

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (!form.package.files.length) return;

                // Reset UI
                liveCard.classList.remove('d-none');
                liveText.textContent = '';
                liveVersion.textContent = '';
                currentUpdateId = null;
                setStatus("{{ __('labels.starting') }}", 'badge bg-secondary');
                disableForm();

                // Start polling immediately (the backend will create the update row early)
                startPolling();

                // Submit the form asynchronously
                const formData = new FormData(form);
                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {'X-Requested-With': 'XMLHttpRequest'},
                        body: formData
                    });
                    // We ignore the response body; polling reflects the truth
                    if (!res.ok) {
                        // Try to force one more poll to capture failure state
                        setTimeout(() => {
                        }, 0);
                    }
                } catch (err) {
                    // Network error; keep polling a bit in case server still processing
                }
            });
        })();
    </script>
@endpush
