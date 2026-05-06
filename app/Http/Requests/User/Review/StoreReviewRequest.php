<?php

namespace App\Http\Requests\User\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_item_id' => 'required|exists:order_items,id',
            'rating'     => 'required|integer|min:1|max:5',
            'title'      => 'required|string|max:255',
            'comment'    => 'nullable|string|max:1000',
            'review_images.*' => 'nullable|image|max:2048|mimes:jpeg,png,jpg,webp',
        ];
    }

    public function messages(): array
    {
        return [
            'order_item_id.required' => __('messages.order_item_id_required'),
            'order_item_id.exists'   => __('messages.order_item_not_found'),
            'rating.required'     => __('messages.rating_required'),
            'rating.integer'      => __('messages.rating_must_be_integer'),
            'rating.min'          => __('messages.rating_must_be_at_least_1'),
            'rating.max'          => __('messages.rating_must_not_exceed_5'),
            'title.required'      => __('messages.title_required'),
            'title.max'           => __('messages.title_max_length'),
            'comment.max'         => __('messages.comment_max_length'),
            'review_images.*.image' => __('messages.review_image_must_be_image'),
            'review_images.*.max' => __('messages.review_image_max_size'),
            'review_images.*.mimes' => __('messages.review_image_allowed_types'),
        ];
    }
}
