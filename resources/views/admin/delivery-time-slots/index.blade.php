@extends('layouts.admin.app', ['page' => $menuAdmin['delivery_slots']['active'] ?? ''])

@section('title', __('labels.delivery_slots'))
@section('header_data')
    @php($page_title = __('labels.delivery_slots'))
@endsection

@section('admin-content')
    <div class="row"><div class="col-12"><div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">{{ __('labels.delivery_slots') }}</h3>
            @if(auth()->user()->can('delivery_slot.create'))
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#delivery-slot-modal">Add delivery slot</button>
            @endif
        </div>
        <div class="card-body">
            <x-datatable id="delivery-slot-table" :columns="$columns" route="{{ route('admin.delivery-slots.datatable') }}" :options="['order' => [[2, 'asc']], 'pageLength' => 10]"/>
        </div>
    </div></div></div>

    @if(auth()->user()->can('delivery_slot.create') || auth()->user()->can('delivery_slot.edit'))
        <div class="modal fade" id="delivery-slot-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add delivery slot</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form class="form-submit" method="POST" action="{{ route('admin.delivery-slots.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label required">Store</label><select class="form-select" name="store_id" id="delivery-slot-store" required><option value="">Search store</option></select></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label required">Day</label><select class="form-select" name="day_of_week" required>@foreach(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)<option value="{{ $day }}">{{ ucfirst($day) }}</option>@endforeach</select></div>
                            <div class="col-md-6 mb-3"><label class="form-label required">Orders per day</label><input class="form-control" type="number" name="max_orders" min="1" value="10" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label required">Start time</label><input class="form-control" type="time" name="start_time" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label required">End time</label><input class="form-control" type="time" name="end_time" required></div>
                        </div>
                        <label class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><span class="form-check-label">Active</span></label>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save slot</button></div>
                </form>
            </div></div>
        </div>
    @endif
@endsection

@push('scripts')<script src="{{ asset('assets/js/delivery-time-slot.js') }}" defer></script>@endpush