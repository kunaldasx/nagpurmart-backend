<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $labelId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('product_labels', 'name')->ignore($labelId)],
            'bg_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
