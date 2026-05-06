<?php

namespace App\Http\Requests\DeliveryFeedback;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Update this with any authorization logic if required
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
        ];
    }
}
