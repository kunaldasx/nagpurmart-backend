@extends('layouts.admin.app', ['page' => $menuAdmin['brands']['active'] ?? ""])

@section('title', __('labels.bulk_upload') . ' ' . __('labels.brands'))

@section('header_data')
    @php
        $page_title = __('labels.bulk_upload') . ' ' . __('labels.brands');
        $page_pretitle = __('labels.brands');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.brands'), 'url' => route('admin.brands.index')],
        ['title' => __('labels.bulk_upload'), 'url' => null],
    ];
@endphp

@push('scripts')
<script>
window.BULK_UPLOAD_CFG = {
  uploadUrl: "{{ route('admin.brands.bulk-upload') }}",
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
<script src="{{ hyperAsset('assets/admin/js/bulk-upload.js') }}" defer></script>
@endpush

@section('admin-content')
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">{{ __('labels.bulk_upload') }} {{ __('labels.brands') }}</h3>
          <x-breadcrumb :items="$breadcrumbs"/>
        </div>
        <div class="card-actions">
            <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">
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
                <li>{{ __('labels.bulk_upload_hint') ?? 'Upload a CSV up to 10MB. First row should include headers.' }}</li>
                <li>{{ __('labels.required_fields') ?? 'Required fields' }}: <strong>title</strong></li>
                <li>{{ __('labels.optional_fields') ?? 'Optional fields' }}: description, status, scope_type, scope_id, category_title</li>
              </ul>
              <a href="{{ route('admin.brands.bulk-template') }}" class="btn btn-outline-primary btn-sm">{{ __('labels.download_template') }}</a>
            </div>
          </div>
          <div class="col-12">
            <form id="bulk-upload-form" enctype="multipart/form-data" Method="POST" action="{{ route('admin.brands.bulk-upload') }}">
              @csrf
              <label class="form-label">{{ __('labels.drag_drop_csv') ?? 'Drag & drop CSV here, or click to browse' }}</label>
              <input id="csv-file" type="file" name="csv-file" accept=".csv,text/csv" class="filepond" required data-max-file-size="10MB" data-allow-reorder="false" data-max-files="1">
              <div class="small text-muted">.csv, max 10MB</div>
              <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="bulk-upload-submit">
                  <span class="upload-text">{{ __('labels.upload') }}</span>
                  <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <a href="{{ route('admin.brands.bulk-template') }}" class="btn btn-outline-secondary">{{ __('labels.download_template') }}</a>
              </div>
            </form>
          </div>
        </div>

        <div class="mt-4 d-none" id="bulk-upload-result">
          <div class="alert alert-success" id="bulk-success" role="alert"></div>
          <div class="alert alert-danger d-none" id="bulk-failed" role="alert"></div>
          <div class="table-responsive d-none" id="bulk-failed-table-wrap">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>#</th>
                  <th>{{ __('labels.title') }}</th>
                  <th>{{ __('labels.error') }}</th>
                </tr>
              </thead>
              <tbody id="bulk-failed-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
