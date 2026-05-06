<?php

namespace App\Http\Requests\Faq;

use App\Enums\ActiveInactiveStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreUpdateFaqRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'question' => 'required|string|max:1000',
            'answer' => 'required|string|max:5000',
            'status' => ['nullable', new Enum(ActiveInactiveStatusEnum::class)],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'status' => $this->status ?? 'active',
        ]);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'question.required' => 'The question field is required.',
            'question.max' => 'The question may not be greater than 1000 characters.',
            'answer.required' => 'The answer field is required.',
            'answer.max' => 'The answer may not be greater than 5000 characters.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
