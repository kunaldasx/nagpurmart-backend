@extends('layouts.admin.app', ['page' => $menuAdmin['products']['active'] ?? '', 'sub_page' => 'product_requests'])

@section('title', 'Product Requests')

@section('header_data')
    @php
        $page_title = 'Product Requests';
        $page_pretitle = 'Catalog';
    @endphp
@endsection

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="container-xl">
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Missing Product Requests</h3></div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter">
                            <thead><tr><th>Product</th><th>Brand / category</th><th>Seller</th><th>Details</th><th>Status</th><th>Submitted</th><th>Decision</th></tr></thead>
                            <tbody>
                            @forelse($requests as $productRequest)
                                <tr>
                                    <td class="fw-medium">{{ $productRequest->product_name }}</td>
                                    <td>{{ $productRequest->brand_name ?: '—' }}<br><span class="text-secondary">{{ $productRequest->category_name ?: '—' }}</span></td>
                                    <td>{{ $productRequest->seller?->user?->name ?? $productRequest->user?->name ?? '—' }}</td>
                                    <td>
                                        @if($productRequest->description)<div>{{ $productRequest->description }}</div>@endif
                                        @if($productRequest->reference_url)<a href="{{ $productRequest->reference_url }}" target="_blank" rel="noopener noreferrer">Reference</a>@endif
                                        @if($productRequest->request_note)<div class="text-secondary small">{{ $productRequest->request_note }}</div>@endif
                                    </td>
                                    <td><span class="badge bg-{{ $productRequest->status === 'approved' ? 'success' : ($productRequest->status === 'rejected' ? 'danger' : 'yellow') }}-lt text-capitalize">{{ $productRequest->status }}</span></td>
                                    <td>{{ $productRequest->created_at?->format('Y-m-d H:i') }}</td>
                                    <td style="min-width: 240px">
                                        @if($canProcess && $productRequest->status === 'pending')
                                            <form method="POST" action="{{ route('admin.product-requests.decision', $productRequest->id) }}">
                                                @csrf
                                                <textarea class="form-control form-control-sm mb-2" name="admin_remark" rows="2" maxlength="2000" placeholder="Optional note">{{ old('admin_remark') }}</textarea>
                                                <div class="btn-list">
                                                    <button class="btn btn-sm btn-success" name="status" value="approved">Approve</button>
                                                    <button class="btn btn-sm btn-danger" name="status" value="rejected">Reject</button>
                                                </div>
                                            </form>
                                        @else
                                            <span>{{ $productRequest->admin_remark ?: '—' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-secondary py-4" colspan="7">No product requests found.</td></tr>
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