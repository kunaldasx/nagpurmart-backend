@extends('layouts.admin.app', ['page' => $menuAdmin['gift_section']['active'] ?? ''])

@section('title', __('labels.edit_gift_section'))
@section('header_data')
    @php($page_title = __('labels.edit_gift_section'))
@endsection

@section('admin-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('labels.edit_gift_section') }}</h3>
                </div>
                <div class="card-body">
                    <form id="gift-section-form" class="form-submit" method="POST" action="{{ route('admin.gift-section.update') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('labels.heading') }}</label>
                                <input type="text" class="form-control @error('heading') is-invalid @enderror" name="heading" value="{{ old('heading', $giftSection->heading) }}" required>
                                @error('heading')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('labels.sub_heading') }}</label>
                                <input type="text" class="form-control @error('sub_heading') is-invalid @enderror" name="sub_heading" value="{{ old('sub_heading', $giftSection->sub_heading) }}" required>
                                @error('sub_heading')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('labels.bg_color') }}</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color @error('bg_color') is-invalid @enderror" name="bg_color" value="{{ old('bg_color', $giftSection->bg_color) }}" required style="flex: 0 0 60px;">
                                    <input type="text" class="form-control @error('bg_color') is-invalid @enderror" name="bg_color_text" value="{{ old('bg_color', $giftSection->bg_color) }}" placeholder="#F5E6C8" readonly>
                                </div>
                                @error('bg_color')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('labels.font_color') }}</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color @error('font_color') is-invalid @enderror" name="font_color" value="{{ old('font_color', $giftSection->font_color) }}" required style="flex: 0 0 60px;">
                                    <input type="text" class="form-control @error('font_color') is-invalid @enderror" name="font_color_text" value="{{ old('font_color', $giftSection->font_color) }}" placeholder="#222222" readonly>
                                </div>
                                @error('font_color')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('labels.icon_image') }}</label>
                                <input type="file" class="form-control @error('icon_image') is-invalid @enderror" name="icon_image" accept="image/*">
                                <small class="text-muted">{{ __('messages.supported_formats') }}: JPEG, PNG, JPG, GIF, WebP, SVG ({{ __('labels.max_size') }}: 2MB)</small>
                                @error('icon_image')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror

                                @if($giftSection->icon_image)
                                    <div class="mt-3">
                                        <p><strong>{{ __('labels.current_image') }}:</strong></p>
                                        <img src="{{ $giftSection->icon_image }}" alt="Gift Icon" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 5px;">
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-footer mt-4">
                            <a href="{{ route('admin.gift-section.index') }}" class="btn btn-secondary">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('labels.save_changes') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update text input when color picker changes
        const bgColorPicker = document.querySelector('input[name="bg_color"]');
        const bgColorText = document.querySelector('input[name="bg_color_text"]');
        const fontColorPicker = document.querySelector('input[name="font_color"]');
        const fontColorText = document.querySelector('input[name="font_color_text"]');

        bgColorPicker?.addEventListener('change', function() {
            bgColorText.value = this.value;
        });

        fontColorPicker?.addEventListener('change', function() {
            fontColorText.value = this.value;
        });
    });
</script>
@endpush
