<?php

namespace App\Http\Requests\OfferBanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateOfferBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:offer_banners,title,' . ($this->route()->id ?? '')],
            'template_code' => ['required', 'string', 'exists:offer_banner_templates,code'],
            'position' => ['required', 'in:top,carousel'],
            'scope_type' => ['required', 'in:global,category'],
            'scope_id' => ['required_if:scope_type,category', 'nullable', 'exists:categories,id'],
            'visibility_status' => ['required', 'in:published,draft'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'offer_items' => ['nullable', 'array'],
            'offer_items.*.title' => ['nullable', 'string', 'max:255'],
            'offer_items.*.subtitle' => ['nullable', 'string', 'max:255'],
            'offer_items.*.item_type' => ['nullable', 'in:product,category'],
            'offer_items.*.item_id' => ['nullable', 'integer'],
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ];
    }
}
