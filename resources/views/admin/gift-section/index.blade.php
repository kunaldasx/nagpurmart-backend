@extends('layouts.admin.app', ['page' => $menuAdmin['gift_section']['active'] ?? ''])

@section('title', __('labels.gift_section'))
@section('header_data')
    @php($page_title = __('labels.gift_section'))
@endsection

@section('admin-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">{{ __('labels.gift_section_settings') }}</h3>
                    @if($editPermission)
                        <a href="{{ route('admin.gift-section.edit') }}" class="btn btn-primary">{{ __('labels.edit') }}</a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>{{ __('labels.heading') }}:</strong> {{ $giftSection->heading }}</p>
                            <p><strong>{{ __('labels.sub_heading') }}:</strong> {{ $giftSection->sub_heading }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ __('labels.bg_color') }}:</strong>
                                <span style="display: inline-block; width: 20px; height: 20px; background-color: {{ $giftSection->bg_color }}; border: 1px solid #ccc; vertical-align: middle;"></span>
                                {{ $giftSection->bg_color }}
                            </p>
                            <p><strong>{{ __('labels.font_color') }}:</strong>
                                <span style="display: inline-block; width: 20px; height: 20px; background-color: {{ $giftSection->font_color }}; border: 1px solid #ccc; vertical-align: middle;"></span>
                                {{ $giftSection->font_color }}
                            </p>
                        </div>
                    </div>
                    @if($giftSection->hasIconImage())
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <p><strong>{{ __('labels.icon_image') }}:</strong></p>
                                <img src="{{ $giftSection->getIconImageUrl() }}" alt="Gift Icon" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 5px;">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
