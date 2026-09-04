@extends('layouts.admin.app', ['page' => $menuAdmin['customers']['active'] ?? ''])

@section('title', 'Grocery lists')

@section('header_data')
    @php
        $page_title = 'Grocery lists';
        $page_pretitle = $customer->name;
    @endphp
@endsection

@section('admin-content')
    @php
        $breadcrumbs = [
            ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
            ['title' => __('labels.customers'), 'url' => route('admin.customers.index')],
            ['title' => 'Grocery scan history', 'url' => null],
        ];
    @endphp

    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Grocery lists for {{ $customer->name }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                        <div class="text-secondary">{{ $customer->email }} / {{ $customer->mobile }}</div>
                    </div>
                    <div class="card-actions">
                        <a class="btn btn-outline-secondary" href="{{ route('admin.customers.index') }}">Back to customers</a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($lists as $list)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $list->created_at?->format('Y-m-d H:i') }}</strong>
                                <span class="badge bg-{{ $list->status === 'completed' ? 'success' : 'danger' }}">{{ $list->status }}</span>
                            </div>
                            <div class="text-secondary mt-2">
                                Scan #{{ $list->id }} - {{ $list->items->count() }} item{{ $list->items->count() === 1 ? '' : 's' }}
                            </div>
                            @if($list->language)
                                <div class="text-secondary">Language: {{ $list->language }}</div>
                            @endif
                            @if($list->rejection_reason)
                                <div class="text-danger mt-2">{{ $list->rejection_reason }}</div>
                            @endif
                            @if($list->extracted_text)
                                <div class="mt-2"><strong>Extracted text:</strong> {{ $list->extracted_text }}</div>
                            @endif
                            <div class="table-responsive mt-3">
                                <table class="table table-sm">
                                    <thead><tr><th>Item</th><th>Matched product</th><th>Confidence</th></tr></thead>
                                    <tbody>
                                    @forelse($list->items as $item)
                                        <tr>
                                            <td>{{ $item->extracted_name }}</td>
                                            <td>{{ $item->product?->title ?: 'No match' }}</td>
                                            <td>{{ $item->confidence !== null ? number_format($item->confidence * 100, 1) . '%' : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-secondary">No items extracted.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="empty">No grocery lists have been uploaded by this customer.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection