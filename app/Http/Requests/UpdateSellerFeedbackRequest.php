<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSellerFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => 'nullable|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.integer' => __('messages.rating_must_be_integer'),
            'rating.min' => __('messages.rating_must_be_at_least_1'),
            'rating.max' => __('messages.rating_must_not_exceed_5'),
            'title.max' => __('messages.title_max_length'),
            'description.max' => __('messages.description_max_length'),
        ];
    }
}
