<?php

namespace App\Http\Requests\DeliveryFeedback;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Update this with any authorization logic if required
    }

    public function rules(): array
    {
        return [
            'delivery_boy_id' => ['required', 'integer', 'exists:delivery_boys,id'],
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}
