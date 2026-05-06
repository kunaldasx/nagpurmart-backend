<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSellerFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seller_id' => 'required|exists:sellers,id',
            'order_item_id' => 'required|exists:order_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'seller_id.required' => __('messages.seller_id_required'),
            'seller_id.exists' => __('messages.seller_not_found'),
            'order_item_id.required' => __('messages.order_item_id_required'),
            'order_item_id.exists' => __('messages.order_item_not_found'),
            'rating.required' => __('messages.rating_required'),
            'rating.integer' => __('messages.rating_must_be_integer'),
            'rating.min' => __('messages.rating_must_be_at_least_1'),
            'rating.max' => __('messages.rating_must_not_exceed_5'),
            'title.required' => __('messages.title_required'),
            'title.max' => __('messages.title_max_length'),
            'description.required' => __('messages.description_required'),
            'description.max' => __('messages.description_max_length'),
        ];
    }
}
