<?php

namespace App\Http\Requests\OfferBanner;

use App\Enums\Banner\BannerPositionEnum;
use App\Enums\Banner\BannerVisibilityStatusEnum;
use App\Enums\HomePageScopeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
            'scope_type' => ['required', new Enum(HomePageScopeEnum::class)],
            'scope_id' => [
                'required_if:scope_type,' . HomePageScopeEnum::CATEGORY(),
                'nullable',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('parent_id');
                }),
            ],
            'position' => ['required', new Enum(BannerPositionEnum::class)],
            'visibility_status' => ['required', new Enum(BannerVisibilityStatusEnum::class)],
            'display_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
            'metadata.images' => 'required|array|min:1',
            'metadata.images.*.title' => 'required|string|max:255',
            'metadata.images.*.type' => ['required', Rule::in(['product', 'category'])],
            'metadata.images.*.product_id' => 'required_if:metadata.images.*.type,product|nullable|exists:products,id',
            'metadata.images.*.category_id' => 'required_if:metadata.images.*.type,category|nullable|exists:categories,id',
            'metadata.images.*.custom_url' => 'nullable|string|max:255',
            'banner_images' => 'nullable|array',
            'banner_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'deleted_banner_images' => 'nullable|array',
            'deleted_banner_images.*' => 'integer|exists:media,id',
        ];
    }
}
