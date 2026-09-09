@php
    use App\Enums\HighlightedSection\HighlightedSectionTemplateEnum;
    use App\Enums\HomePageScopeEnum;
@endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['highlighted_section']['active'] ?? ''])

@section('title', __('labels.highlighted_sections'))
@section('header_data')
    @php($page_title = __('labels.highlighted_sections'))
@endsection

@section('admin-content')
    <style>
        #highlighted-section-modal select[name="template"],
        #highlighted-section-modal select[name="template"] option {
            text-transform: none !important;
            letter-spacing: normal !important;
        }
    </style>
    <div class="row"><div class="col-12"><div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">{{ __('labels.highlighted_sections') }}</h3>
            @if($createPermission)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#highlighted-section-modal">Add highlighted section</button>
            @endif
        </div>
        <div class="card-body">
            <x-datatable id="highlighted-section-table" :columns="$columns" route="{{ route('admin.highlighted-sections.datatable') }}" :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
        </div>
    </div></div></div>

    @if($createPermission || $editPermission)
        <div class="modal fade" id="highlighted-section-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add highlighted section</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form class="form-submit" method="POST" action="{{ route('admin.highlighted-sections.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label required">Heading</label><input class="form-control" name="title" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Subheading</label><input class="form-control" name="subtitle"></div>
                            <div class="col-md-4 mb-3"><label class="form-label required">Template</label><select class="form-select" name="template" required>
                                <option value="">Select template</option>
                                @foreach(HighlightedSectionTemplateEnum::cases() as $template)<option value="{{ $template->value }}">{{ str($template->name)->headline() }}</option>@endforeach
                            </select></div>
                            <div class="col-md-4 mb-3"><label class="form-label required">Scope</label><select class="form-select" name="scope_type" id="highlighted-scope-type" required>@foreach(HomePageScopeEnum::values() as $scope)<option value="{{ $scope }}">{{ ucfirst($scope) }}</option>@endforeach</select></div>
                            <div class="col-md-4 mb-3" id="highlighted-scope-category-field" style="display:none"><label class="form-label required">Category</label><select class="form-select" name="scope_id" id="highlighted-scope-category"><option value="">Search category</option></select></div>
                            <div class="col-md-3 mb-3"><label class="form-label">Background color</label><input type="color" class="form-control form-control-color w-100" name="background_color" value="#ffffff"></div>
                            <div class="col-md-3 mb-3"><label class="form-label">Font color</label><input type="color" class="form-control form-control-color w-100" name="font_color" value="#000000"></div>
                            <div class="col-md-3 mb-3"><label class="form-label">Sort order</label><input type="number" class="form-control" name="sort_order" min="0" value="0"></div>
                            <div class="col-md-3 mb-3 d-flex align-items-end"><label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="status" value="active" checked><span class="form-check-label">Active</span></label></div>
                        </div>
                        <hr><div class="d-flex justify-content-between align-items-center mb-2"><h4 class="mb-0">Items</h4><button type="button" class="btn btn-outline-primary btn-sm" id="add-highlighted-item">Add item</button></div>
                        <div id="highlighted-items"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save section</button></div>
                </form>
            </div></div>
        </div>
    @endif
@endsection

@push('scripts')<script src="{{ asset('assets/js/highlighted-section.js') }}" defer></script>@endpush