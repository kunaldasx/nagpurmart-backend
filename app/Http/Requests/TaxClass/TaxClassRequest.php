<?php

namespace App\Http\Requests\TaxClass;

use Illuminate\Foundation\Http\FormRequest;

class TaxClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can add authorization logic if needed
    }

    public function rules(): array
    {
        $id = $this->route('tax_class');
        return [
            'title' => 'required|string|max:255|unique:tax_classes,title,' . $this->route('id'),
            'tax_rate_ids' => 'required|array|min:1',
            'tax_rate_ids.*' => 'exists:tax_rates,id',
        ];
    }
}
