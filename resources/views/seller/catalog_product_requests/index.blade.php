@extends('layouts.seller.app', ['page' => $menuSeller['products']['active'] ?? '', 'sub_page' => 'product_requests'])

@section('title', 'Product Requests')

@section('header_data')
    @php
        $page_title = 'Product Requests';
        $page_pretitle = __('labels.seller') . ' Products';
    @endphp
@endsection

@section('seller-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="container-xl">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title">Request a Missing Product</h3></div>
                    <form method="POST" action="{{ route('seller.product-requests.store') }}">
                        @csrf
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="product_name">Product name *</label>
                                    <input class="form-control @error('product_name') is-invalid @enderror" id="product_name" name="product_name" value="{{ old('product_name') }}" required maxlength="255">
                                    @error('product_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="brand_name">Brand</label>
                                    <input class="form-control" id="brand_name" name="brand_name" value="{{ old('brand_name') }}" maxlength="255">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="category_name">Category</label>
                                    <input class="form-control" id="category_name" name="category_name" value="{{ old('category_name') }}" maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="reference_url">Reference URL</label>
                                    <input class="form-control" type="url" id="reference_url" name="reference_url" value="{{ old('reference_url') }}" maxlength="2048">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="2" maxlength="5000">{{ old('description') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="request_note">Request note</label>
                                    <textarea class="form-control" id="request_note" name="request_note" rows="2" maxlength="2000">{{ old('request_note') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button class="btn btn-primary" type="submit">Submit Request</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Catalog Product Requests</h3></div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter">
                            <thead><tr><th>Product</th><th>Brand</th><th>Category</th><th>Status</th><th>Admin remark</th><th>Submitted</th></tr></thead>
                            <tbody>
                            @forelse($requests as $productRequest)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $productRequest->product_name }}</div>
                                        @if($productRequest->request_note)<div class="text-secondary small">{{ $productRequest->request_note }}</div>@endif
                                    </td>
                                    <td>{{ $productRequest->brand_name ?: '—' }}</td>
                                    <td>{{ $productRequest->category_name ?: '—' }}</td>
                                    <td><span class="badge bg-{{ $productRequest->status === 'approved' ? 'success' : ($productRequest->status === 'rejected' ? 'danger' : 'yellow') }}-lt text-capitalize">{{ $productRequest->status }}</span></td>
                                    <td>{{ $productRequest->admin_remark ?: '—' }}</td>
                                    <td>{{ $productRequest->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-secondary py-4" colspan="6">No product requests yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($requests->hasPages())<div class="card-footer">{{ $requests->links() }}</div>@endif
                </div>
            </div>
        </div>
    </div>
@endsection