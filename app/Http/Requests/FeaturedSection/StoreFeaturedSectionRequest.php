<?php

namespace App\Http\Requests\FeaturedSection;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\FeaturedSection\FeaturedSectionStyleEnum;
use App\Enums\FeaturedSection\FeaturedSectionTypeEnum;
use App\Enums\HomePageScopeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreFeaturedSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:1000',
            'style' => ['required', new Enum(FeaturedSectionStyleEnum::class)],
            'section_type' => ['required', new Enum(FeaturedSectionTypeEnum::class)],
            'status' => ['nullable', new Enum(ActiveInactiveStatusEnum::class)],
            'scope_type' => ['required', new Enum(HomePageScopeEnum::class)],
            'scope_id' => [
                'required_if:scope_type,' . HomePageScopeEnum::CATEGORY(),
                'nullable',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('parent_id');
                }),
            ],
            'background_type' => 'nullable|in:image,color',
            'background_color' => 'nullable|string|max:255',
            'text_color' => 'nullable|string|max:255',
            'desktop_4k_background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'desktop_fdh_background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'tablet_background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'mobile_background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('labels.title'),
            'slug' => __('labels.slug'),
            'short_description' => __('labels.short_description'),
            'style' => __('labels.style'),
            'section_type' => __('labels.section_type'),
            'sort_order' => __('labels.sort_order'),
            'is_active' => __('labels.status'),
            'scope_type' => __('labels.scope_type'),
            'scope_id' => __('labels.scope_category'),
            'background_type' => __('labels.background_type'),
            'background_color' => __('labels.background_color'),
            'categories' => __('labels.categories'),
            'categories.*' => __('labels.category'),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('validation.featured_section_title_required'),
            'slug.required' => __('validation.featured_section_slug_required'),
            'slug.unique' => __('validation.featured_section_slug_unique'),
            'section_type.required' => __('validation.featured_section_type_required'),
            'categories.*.exists' => __('validation.featured_section_categories_exists'),
        ];
    }
}
