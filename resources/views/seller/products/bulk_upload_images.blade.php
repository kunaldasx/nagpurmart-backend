@extends('layouts.seller.app', ['page' => $menuSeller['products']['active'] ?? "", 'sub_page' => $menuSeller['products']['route']['bulk_upload']['sub_active'] ?? null])

@section('title', __('labels.bulk_upload') . ' ' . __('labels.images'))

@section('header_data')
    @php
        $page_title = __('labels.bulk_upload') . ' ' . __('labels.images');
        $page_pretitle = __('labels.products');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.products'), 'url' => route('seller.products.index')],
        ['title' => __('labels.bulk_upload') . ' ' . __('labels.images'), 'url' => null],
    ];
@endphp

@section('seller-content')
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">{{ __('labels.bulk_upload') }} {{ __('labels.images') }}</h3>
          <x-breadcrumb :items="$breadcrumbs"/>
        </div>
        <div class="card-actions">
            <a href="{{ route('seller.products.bulk-upload.page') }}" class="btn btn-outline-secondary">
                {{ __('labels.back') ?? 'Back' }}
            </a>
        </div>
      </div>
      <div class="card-body">
        <div class="row g-4">
          <div class="col-12">
            <div class="alert alert-info" role="alert">
              <div class="alert-title">{{ __('labels.instructions') ?? 'Instructions' }}</div>
              <ul class="mb-2">
                <li>Upload a ZIP file containing product and variant images.</li>
                <li>File names should follow:
                  <ul>
                    <li>productId.ext → main image (e.g., 10.jpg)</li>
                    <li>productId-n.ext → additional images (e.g., 10-1.jpg, 10-2.png)</li>
                    <li>productId_variantId.ext → variant image (e.g., 10_101.webp)</li>
                  </ul>
                </li>
                <li>Supported extensions: jpg, jpeg, png, webp. Max ZIP size: 200MB.</li>
                <li>Only existing products/variants will be processed. Others will be listed as failed.</li>
              </ul>
            </div>
          </div>
          <div class="col-12 d-none" id="images-upload-warning">
            <div class="alert alert-warning" role="alert">
              <div class="alert-title">Previous upload interrupted</div>
              The previous images upload appears to have been interrupted before it started. Please select the ZIP and try again.
            </div>
          </div>
          <div class="col-12">
            <form id="images-upload-form" enctype="multipart/form-data" Method="POST" action="{{ route('seller.products.images-upload') }}">
              @csrf
              <label class="form-label">Images ZIP</label>
              <input id="images-zip" type="file" name="images-zip" class="form-control" required>
              <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="images-upload-submit">
                  <span class="upload-text">{{ __('labels.upload') }}</span>
                  <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <a href="{{ route('seller.products.bulk-upload.page') }}" class="btn btn-outline-secondary">{{ __('labels.bulk_upload') }} {{ __('labels.products') }}</a>
              </div>
            </form>
          </div>
        </div>

        <div class="mt-3 d-none" id="images-upload-progress">
          <div class="d-flex justify-content-between mb-1">
            <span>Processing images...</span>
            <span id="images-progress-text">0%</span>
          </div>
          <div class="progress">
            <div id="images-progress-bar" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
        </div>

        <div class="mt-4 d-none" id="images-upload-result">
          <div class="alert alert-success" id="images-success" role="alert"></div>
          <div class="alert alert-danger d-none" id="images-failed" role="alert"></div>
          <div class="table-responsive d-none" id="images-failed-table-wrap">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>#</th>
                  <th>{{ __('labels.title') }}</th>
                  <th>{{ __('labels.error') }}</th>
                </tr>
              </thead>
              <tbody id="images-failed-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
    <script>
        window.BULK_IMG_UPLOAD_CFG = {
            uploadUrl: "{{ route('seller.products.images-upload') }}",
            statusBaseUrl: "{{ route('seller.products.images-upload.status', ['token' => 'TOKEN']) }}".replace('TOKEN',''),
            csrf: "{{ csrf_token() }}",
            i18n: {
                upload_success_count: "{{ __('labels.upload_success_count') }}",
                upload_failed_count: "{{ __('labels.upload_failed_count') }}",
                some_rows_failed: "{{ __('labels.some_rows_failed') }}",
                upload_failed_generic: "{{ __('labels.upload_failed_generic') ?? 'Upload failed' }}",
                unexpected_error: "{{ __('labels.unexpected_error') ?? 'Unexpected error occurred' }}"
            }
        };
    </script>
    <script>
        (function(){
            document.addEventListener('DOMContentLoaded', function(){
                var form = document.getElementById('images-upload-form');
                var submitBtn = document.getElementById('images-upload-submit');
                if(!form || !submitBtn) return;
                var spinner = submitBtn.querySelector('.spinner-border');
                var uploadText = submitBtn.querySelector('.upload-text');
                var resultWrap = document.getElementById('images-upload-result');
                var successEl = document.getElementById('images-success');
                var failedEl = document.getElementById('images-failed');
                var failedTableWrap = document.getElementById('images-failed-table-wrap');
                var failedTbody = document.getElementById('images-failed-tbody');
                var progressWrap = document.getElementById('images-upload-progress');
                var progressBar = document.getElementById('images-progress-bar');
                var progressText = document.getElementById('images-progress-text');
                var warningWrap = document.getElementById('images-upload-warning');

                var LS_KEY = 'bulk_img_upload_token';
                var LS_PENDING = 'bulk_img_upload_pending';
                var pollTimer = null;
                var inFlight = false; // true while initial POST is in progress and token not yet received

                function clearResults(){
                    if (resultWrap) resultWrap.classList.add('d-none');
                    if (failedEl) failedEl.classList.add('d-none');
                    if (failedTableWrap) failedTableWrap.classList.add('d-none');
                    if (failedTbody) failedTbody.innerHTML = '';
                }

                function updateProgress(total, processed){
                    if (!progressWrap || !progressBar) return;
                    var pct = 0;
                    if (total > 0) pct = Math.floor((processed / total) * 100);
                    progressWrap.classList.remove('d-none');
                    progressBar.style.width = pct + '%';
                    progressBar.setAttribute('aria-valuenow', pct);
                    if (progressText) progressText.textContent = pct + '%';
                }

                function stopPolling(){
                    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
                }

                function finish(token){
                    try { localStorage.removeItem(LS_KEY); } catch(e) {}
                    stopPolling();
                    if (progressWrap) progressWrap.classList.add('d-none');
                }

                function renderFinal(json){
                    if (!json || !json.data) return;
                    var d = json.data;
                    if (resultWrap) resultWrap.classList.remove('d-none');
                    var successMsg =
                        (window.BULK_IMG_UPLOAD_CFG.i18n.upload_success_count || 'Successfully processed') +
                        ': ' + (d.success_count || 0) + '. ' +
                        (window.BULK_IMG_UPLOAD_CFG.i18n.upload_failed_count || 'Failed') +
                        ': ' + (d.failed_count || 0) + '.';
                    if (successEl) successEl.textContent = successMsg;

                    if ((d.failed_rows || []).length) {
                        if (failedEl) {
                            failedEl.classList.remove('d-none');
                            failedEl.textContent = window.BULK_IMG_UPLOAD_CFG.i18n.some_rows_failed || 'Some items failed';
                        }
                        if (failedTableWrap) failedTableWrap.classList.remove('d-none');
                        d.failed_rows.forEach(function (r) {
                            if (!failedTbody) return;
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + (r.row || '') + '</td>' +
                                '<td>' + (r.title || '') + '</td>' +
                                '<td>' + (r.error || '') + '</td>';
                            failedTbody.appendChild(tr);
                        });
                    }
                }

                function pollStatus(token){
                    if (!token) return;
                    var url = (window.BULK_IMG_UPLOAD_CFG.statusBaseUrl || '') + token;
                    stopPolling();
                    pollTimer = setInterval(function(){
                        axios.get(url)
                            .then(function(resp){
                                var json = resp.data;
                                if (!json || !json.success) return;
                                var d = json.data || {};
                                updateProgress(d.total || 0, d.processed || 0);
                                if (d.status === 'completed' || d.status === 'failed') {
                                    finish(token);
                                    renderFinal(json);
                                }
                            })
                            .catch(function(){ /* ignore, keep polling */ });
                    }, 2000);
                }

                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    var fd = new FormData(form);
                    // mark as pending and protect against accidental refresh until we get a token
                    try { localStorage.setItem(LS_PENDING, '1'); } catch(e) {}
                    inFlight = true;
                    window.onbeforeunload = function(){ return 'Upload in progress. Are you sure you want to leave?'; };
                    submitBtn.disabled = true;
                    if (spinner) spinner.classList.remove('d-none');
                    if (uploadText) uploadText.classList.add('d-none');
                    clearResults();

                    axios.post(window.BULK_IMG_UPLOAD_CFG.uploadUrl, fd)
                        .then(function(resp){
                            var json = resp.data || {};
                            if (json && json.success && json.data && json.data.token) {
                                try { localStorage.setItem(LS_KEY, json.data.token); } catch(e) {}
                                // initial request completed; it is now safe to allow refresh
                                inFlight = false;
                                try { localStorage.removeItem(LS_PENDING); } catch(e) {}
                                window.onbeforeunload = null;
                                // Begin polling immediately
                                pollStatus(json.data.token);
                            } else {
                                // Fallback: show message
                                if (resultWrap) resultWrap.classList.remove('d-none');
                                if (failedEl) {
                                    failedEl.classList.remove('d-none');
                                    failedEl.textContent = json?.message || (window.BULK_IMG_UPLOAD_CFG.i18n.upload_failed_generic || 'Upload failed');
                                }
                            }
                        })
                        .catch(function(){
                            if (resultWrap) resultWrap.classList.remove('d-none');
                            if (failedEl) {
                                failedEl.classList.remove('d-none');
                                failedEl.textContent = window.BULK_IMG_UPLOAD_CFG.i18n.unexpected_error || 'Unexpected error occurred';
                            }
                        })
                        .finally(function(){
                            submitBtn.disabled = false;
                            if (spinner) spinner.classList.add('d-none');
                            if (uploadText) uploadText.classList.remove('d-none');
                            // If the request finished without yielding a token, keep LS_PENDING for recovery message
                        });
                });

                // Resume polling if there is an ongoing upload (page refreshed)
                try {
                    var existingToken = localStorage.getItem(LS_KEY);
                    if (existingToken) {
                        pollStatus(existingToken);
                    }
                    // If the previous attempt was pending but there is no token, show a warning to retry
                    var wasPending = localStorage.getItem(LS_PENDING);
                    if (!existingToken && wasPending && warningWrap) {
                        warningWrap.classList.remove('d-none');
                        // Clear the pending flag since there is nothing to resume
                        localStorage.removeItem(LS_PENDING);
                    }
                } catch(e) {}

                // As a safety, also clear the beforeunload handler when navigating within SPA behaviors
                window.addEventListener('pageshow', function(){ if (!inFlight) window.onbeforeunload = null; });
            });
        })();
    </script>
@endpush
