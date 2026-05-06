<?php

namespace App\Http\Requests\ProductCondition;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id,' . $this->route('id'),
            'title' => 'required|string|max:255',
            'alignment' => 'required|in:strip',
        ];
    }
}
