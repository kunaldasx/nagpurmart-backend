<?php

namespace App\Http\Requests\User\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateItemReturnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,png,webp,jpeg'],
        ];
    }

    /**
     * Custom validation messages (optional).
     */
    public function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'exists' => 'The selected :attribute is invalid.',
            'string' => 'The :attribute must be a valid string.',
            'array' => 'The :attribute must be an array.',
        ];
    }

    /**
     * Human-friendly field labels.
     */
    public function attributes(): array
    {
        return [
            'reason' => 'Return Reason',
            'images' => 'Return Images',
        ];
    }
}
