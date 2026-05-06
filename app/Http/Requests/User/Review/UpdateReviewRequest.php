<?php

namespace App\Http\Requests\User\Review;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating'  => 'nullable|integer|min:1|max:5',
            'title'   => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.integer'      => __('messages.rating_must_be_integer'),
            'rating.min'          => __('messages.rating_must_be_at_least_1'),
            'rating.max'          => __('messages.rating_must_not_exceed_5'),
            'title.max'           => __('messages.title_max_length'),
            'comment.max'         => __('messages.comment_max_length'),
        ];
    }
}
