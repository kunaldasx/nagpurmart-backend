<?php

namespace App\Http\Requests\HighlightedSection;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\HighlightedSection\HighlightedSectionItemTypeEnum;
use App\Enums\HighlightedSection\HighlightedSectionScopeEnum;
use App\Enums\HighlightedSection\HighlightedSectionTemplateEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreHighlightedSectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:1000',
            'template' => ['required', new Enum(HighlightedSectionTemplateEnum::class)],
            'scope_type' => ['required', new Enum(HighlightedSectionScopeEnum::class)],
            'scope_id' => 'required_if:scope_type,category|nullable|exists:categories,id',
            'background_color' => 'nullable|string|max:30',
            'font_color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer|min:0',
            'status' => ['nullable', new Enum(ActiveInactiveStatusEnum::class)],
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:highlighted_section_items,id',
            'items.*.item_type' => ['required', new Enum(HighlightedSectionItemTypeEnum::class)],
            'items.*.item_id' => 'required|integer',
            'items.*.title' => 'required|string|max:255',
            'items.*.subtitle' => 'nullable|string|max:1000',
            'items.*.sort_order' => 'nullable|integer|min:0',
            'items.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ];
    }
}