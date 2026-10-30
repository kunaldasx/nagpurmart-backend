<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CustomerGstinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('gstin') && is_string($this->input('gstin'))) {
            $this->merge(['gstin' => strtoupper(trim($this->input('gstin')))]);
        }

        if ($this->has('legal_name') && is_string($this->input('legal_name'))) {
            $this->merge(['legal_name' => trim($this->input('legal_name')) ?: null]);
        }
    }

    public function rules(): array
    {
        return [
            'gstin' => [
                'required',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/',
            ],
            'legal_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
