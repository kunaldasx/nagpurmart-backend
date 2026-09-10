@extends('layouts.admin.app', ['page' => $menuAdmin['product_labels']['active'] ?? ''])

@section('title', 'Product Labels')

@section('header_data')
    @php
        $page_title = 'Product Labels';
        $page_pretitle = 'Product management';
    @endphp
@endsection

@section('admin-content')
    <div class="row row-cards">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ $label ? 'Edit Product Label' : 'Add Product Label' }}</h3></div>
                <form action="{{ $label ? route('admin.product-labels.update', $label->id) : route('admin.product-labels.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required">Label</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $label?->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Background color</label>
                            <input type="color" name="bg_color" class="form-control form-control-color" value="{{ old('bg_color', $label?->bg_color ?? '#E5E7EB') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Font color</label>
                            <input type="color" name="font_color" class="form-control form-control-color" value="{{ old('font_color', $label?->font_color ?? '#111827') }}" required>
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ $label ? 'Update Label' : 'Add Label' }}</button>
                        @if($label)
                            <a href="{{ route('admin.product-labels.index') }}" class="btn">Cancel</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Configured Labels</h3></div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead><tr><th>Label</th><th>Preview</th><th>Background</th><th>Font</th><th class="w-1"></th></tr></thead>
                        <tbody>
                        @forelse($labels as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td><span class="badge" style="background-color: {{ $item->bg_color }}; color: {{ $item->font_color }}">{{ $item->name }}</span></td>
                                <td>{{ $item->bg_color }}</td>
                                <td>{{ $item->font_color }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.product-labels.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.product-labels.delete', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this label?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No product labels configured.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
