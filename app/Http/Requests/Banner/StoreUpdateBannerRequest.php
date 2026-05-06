<?php

namespace App\Http\Requests\Banner;

use App\Enums\Banner\BannerPositionEnum;
use App\Enums\Banner\BannerTypeEnum;
use App\Enums\Banner\BannerVisibilityStatusEnum;
use App\Enums\HomePageScopeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreUpdateBannerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'scope_type' => ['required', new Enum(HomePageScopeEnum::class)],
            'scope_id' => [
                'required_if:scope_type,' . HomePageScopeEnum::CATEGORY(),
                'nullable',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('parent_id');
                }),
            ],
            'type' => ['required', new Enum(BannerTypeEnum::class)],
            'title' => ['required', 'string', 'max:255', 'unique:banners,title,' . ($this->route()->id ?? '')],
            'custom_url' => ['required_if:type,==,' . BannerTypeEnum::CUSTOM(), 'nullable', 'string', 'max:255'],
            'product_id' => 'required_if:type,==,' . BannerTypeEnum::PRODUCT() . '|nullable|exists:products,id',
            'category_id' => 'required_if:type,==,' . BannerTypeEnum::CATEGORY() . '|nullable|exists:categories,id',
            'brand_id' => 'required_if:type,==,' . BannerTypeEnum::BRAND() . '|nullable|exists:brands,id',
            'position' => ['required', new Enum(BannerPositionEnum::class)],
            'visibility_status' => ['required', new Enum(BannerVisibilityStatusEnum::class)],
            'display_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
            'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }
}
