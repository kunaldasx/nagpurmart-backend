<?php

namespace App\Http\Requests\OfferBanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateOfferBannerTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^T_[A-Z0-9_]+$/', Rule::unique('offer_banner_templates', 'code')->ignore($this->route('id'))],
            'name' => ['required', 'string', 'max:255'],
            'preview_image' => [$this->isMethod('POST') ? 'required' : 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}