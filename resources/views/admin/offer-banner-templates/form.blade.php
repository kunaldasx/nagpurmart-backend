@extends('layouts.admin.app')

@section('title', isset($template) ? 'Edit Offer Banner Template' : 'Add Offer Banner Template')

@section('admin-content')
    <div class="page-body">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ isset($template) ? 'Edit Offer Banner Template' : 'Add Offer Banner Template' }}</h3>
            </div>
            <form class="form-submit" method="POST" enctype="multipart/form-data" action="{{ isset($template) ? route('admin.offer-banner-templates.update', $template->id) : route('admin.offer-banner-templates.store') }}">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Code</label>
                            <input class="form-control" name="code" value="{{ old('code', $template->code ?? '') }}" placeholder="T_9" required>
                            <div class="form-hint">Use a unique code such as T_9 or T_SALE.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Name</label>
                            <input class="form-control" name="name" value="{{ old('name', $template->name ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label {{ isset($template) ? '' : 'required' }}">Preview Image</label>
                            <input class="form-control" type="file" name="preview_image" accept="image/*" {{ isset($template) ? '' : 'required' }}>
                            @if(isset($template))
                                <img src="{{ $template->preview_url }}" alt="{{ $template->name }}" class="mt-3 rounded" style="max-width: 320px;">
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Display Order</label>
                            <input class="form-control" type="number" name="display_order" min="0" value="{{ old('display_order', $template->display_order ?? 0) }}">
                        </div>
                        <div class="col-md-3 pt-md-4">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-3">
                    <a href="{{ route('admin.offer-banner-templates.index') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">{{ isset($template) ? 'Update Template' : 'Create Template' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection