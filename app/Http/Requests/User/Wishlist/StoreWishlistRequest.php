<?php

namespace App\Http\Requests\User\Wishlist;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWishlistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'wishlist_title' => 'nullable|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'store_id' => 'required_with:product_id|exists:stores,id',
        ];
    }

    public function messages(): array
    {
        return [
            'wishlist_title.max' => __('validation.wishlist_title_max'),
            'product_id.exists' => __('validation.product_exists'),
            'product_variant_id.exists' => __('validation.product_variant_exists'),
            'store_id.required_with' => __('validation.store_required_with_product'),
            'store_id.exists' => __('validation.store_exists'),
        ];
    }
}
