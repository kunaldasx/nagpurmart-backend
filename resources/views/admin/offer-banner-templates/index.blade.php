@extends('layouts.admin.app')

@section('title', 'Offer Banner Templates')

@section('admin-content')
    <div class="page-body">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Offer Banner Templates</h3>
                <a href="{{ route('admin.offer-banner-templates.create') }}" class="btn btn-primary">Add Template</a>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    @foreach($templates as $template)
                        <div class="col-6 col-md-3">
                            <div class="card h-100">
                                <img src="{{ $template->preview_url }}" alt="{{ $template->name }}" class="card-img-top" style="aspect-ratio: 16 / 9; object-fit: cover;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between gap-2">
                                        <strong>{{ $template->name }}</strong>
                                        <span class="badge {{ $template->is_active ? 'bg-green-lt' : 'bg-secondary-lt' }}">{{ $template->code }}</span>
                                    </div>
                                    <div class="text-secondary mt-2">{{ $template->is_active ? 'Active' : 'Inactive' }} · Order {{ $template->display_order }}</div>
                                </div>
                                <div class="card-footer d-flex gap-2">
                                    <a href="{{ route('admin.offer-banner-templates.edit', $template->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('admin.offer-banner-templates.delete', $template->id) }}" class="form-submit">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection