<?php

namespace App\Http\Requests\GiftSection;

use Illuminate\Foundation\Http\FormRequest;

class StoreGiftSectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'heading' => 'required|string|max:255',
            'sub_heading' => 'required|string|max:255',
            'bg_color' => 'required|string|max:30|regex:/^#[a-fA-F0-9]{6}$/',
            'font_color' => 'required|string|max:30|regex:/^#[a-fA-F0-9]{6}$/',
            'icon_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'bg_color.regex' => 'Background color must be a valid hex color code.',
            'font_color.regex' => 'Font color must be a valid hex color code.',
            'icon_image.image' => 'Icon image must be an image file.',
            'icon_image.mimes' => 'Icon image must be a JPEG, PNG, JPG, GIF, WebP, or SVG file.',
            'icon_image.max' => 'Icon image size must not exceed 10MB.',
        ];
    }
}
