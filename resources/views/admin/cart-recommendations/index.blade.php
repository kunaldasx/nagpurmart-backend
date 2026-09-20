@extends('layouts.admin.app', ['page' => 'cart-recommendations'])

@section('title', 'Cart Recommendations')

@section('header_data')
    @php
        $page_title = 'Cart Recommendations';
        $page_pretitle = 'Manage cart product sections';
    @endphp
@endsection

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="container-xl">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Cart recommendation sections</h3>
                        <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#cart-recommendation-modal">Add section</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead><tr><th>Heading</th><th>Products</th><th>Layout</th><th>Status</th><th>Sort</th><th class="w-1">Actions</th></tr></thead>
                            <tbody>
                            @forelse($sections as $section)
                                <tr>
                                    <td>{{ $section->heading }}</td>
                                    <td>{{ $section->products->count() }}</td>
                                    <td>{{ $section->is_tabular ? 'Tabular' : 'List' }}</td>
                                    <td><span class="badge {{ $section->status === 'active' ? 'bg-success-lt' : 'bg-secondary-lt' }}">{{ ucfirst($section->status) }}</span></td>
                                    <td>{{ $section->sort_order }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-section" data-bs-toggle="modal" data-bs-target="#cart-recommendation-modal"
                                            data-action="{{ route('admin.cart-recommendations.update', $section) }}"
                                            data-heading="{{ $section->heading }}" data-tabular="{{ $section->is_tabular ? 1 : 0 }}"
                                            data-status="{{ $section->status }}" data-sort="{{ $section->sort_order }}"
                                            data-products='@json($section->products->pluck("id"))'>Edit</button>
                                        <form class="d-inline" method="POST" action="{{ route('admin.cart-recommendations.destroy', $section) }}" onsubmit="return confirm('Delete this section?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No cart recommendation sections yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cart-recommendation-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <form method="POST" action="{{ route('admin.cart-recommendations.store') }}" id="cart-recommendation-form">
                @csrf
                <div class="modal-header"><h3 class="modal-title">Add cart recommendation section</h3><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label required">Heading</label><input class="form-control" name="heading" id="recommendation-heading" required></div>
                        <div class="col-md-4"><label class="form-label">Sort order</label><input class="form-control" type="number" min="0" name="sort_order" id="recommendation-sort" value="0"></div>
                        <div class="col-md-6"><label class="form-label required">Status</label><select class="form-select" name="status" id="recommendation-status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                        <div class="col-md-6 d-flex align-items-end"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_tabular" value="1" id="recommendation-tabular"><span class="form-check-label">Show as tabular section</span></label></div>
                        <div class="col-12">
                            <label class="form-label required">Products</label>
                            <div class="border rounded p-2" id="recommendation-products">
                                <div class="row g-2">
                                    @foreach($products as $product)
                                        <div class="col-md-6">
                                            <label class="form-check border rounded p-2 d-flex align-items-center gap-2 h-100">
                                                <input class="form-check-input mt-0 recommendation-product" type="checkbox" name="products[]" value="{{ $product->id }}">
                                                <img src="{{ $product->main_image }}" alt="" width="48" height="48" class="rounded object-fit-cover">
                                                <span class="form-check-label">
                                                    <span class="d-block">{{ $product->title }}</span>
                                                    <small class="text-muted">Product ID: #{{ $product->id }}</small>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-hint">Check the products you want to recommend.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save section</button></div>
            </form>
        </div></div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const modal = $('#cart-recommendation-modal');
    const form = $('#cart-recommendation-form');
    $('.edit-section').on('click', function () {
        const button = $(this);
        form.attr('action', button.data('action'));
        form.attr('method', 'POST');
        form.find('input[name="_method"]').remove();
        form.prepend('<input type="hidden" name="_method" value="PUT">');
        modal.find('.modal-title').text('Edit cart recommendation section');
        $('#recommendation-heading').val(button.data('heading'));
        $('#recommendation-sort').val(button.data('sort'));
        $('#recommendation-status').val(button.data('status'));
        $('#recommendation-tabular').prop('checked', Number(button.data('tabular')) === 1);
        const selected = (button.data('products') || []).map(String);
        $('#recommendation-products .recommendation-product').each(function () {
            $(this).prop('checked', selected.includes(String($(this).val())));
        });
    });
    $('[data-bs-target="#cart-recommendation-modal"]:not(.edit-section)').on('click', function () {
        form.attr('action', '{{ route('admin.cart-recommendations.store') }}');
        form.find('input[name="_method"]').remove();
        modal.find('.modal-title').text('Add cart recommendation section');
        form[0].reset();
        $('#recommendation-products .recommendation-product').prop('checked', false);
    });
});
</script>
@endpush
