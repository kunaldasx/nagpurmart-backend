<?php

namespace App\Http\Requests\HighlightedSection;

use Illuminate\Validation\Rules\Unique;

class UpdateHighlightedSectionRequest extends StoreHighlightedSectionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['title'] = 'required|string|max:255|unique:highlighted_sections,title,' . $this->route('id');
        return $rules;
    }
}