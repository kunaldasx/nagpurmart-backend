@extends('layouts.admin.app', ['page' => 'popular_searches', 'sub_page' => 'sort'])

@section('title', __('labels.sort_popular_searches'))
@section('header_data')
    @php($page_title = __('labels.sort_popular_searches'))
@endsection

@section('admin-content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">{{ __('labels.sort_popular_searches') }}</h3>
            <a href="{{ route('admin.popular-searches.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> {{ __('labels.back_to_list') }}</a>
        </div>
        <div class="card-body">
            <p class="text-muted">{{ __('labels.drag_drop_popular_searches') }}</p>
            <div id="popular-search-sortable" class="list-group">
                @forelse($popularSearches as $item)
                    <div class="list-group-item d-flex align-items-center" data-id="{{ $item->id }}">
                        <i class="fas fa-grip-vertical text-muted me-3"></i>
                        <span class="flex-grow-1"><strong>{{ $item->category->title }}</strong>@if($item->category->parent) <small class="text-muted">({{ $item->category->parent->title }})</small>@endif</span>
                        <span class="badge {{ $item->status === 'active' ? 'bg-success-lt' : 'bg-danger-lt' }}">{{ $item->status }}</span>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">{{ __('labels.no_popular_searches_found') }}</div>
                @endforelse
            </div>
        </div>
        @if($editPermission && $popularSearches->isNotEmpty())
            <div class="card-footer text-end"><button class="btn btn-primary" id="save-popular-search-order"><i class="fas fa-save"></i> {{ __('labels.save_order') }}</button></div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/sortablejs/sortable.min.js') }}"></script>
    <script src="{{ asset('assets/js/popular-searches.js') }}" defer></script>
@endpush