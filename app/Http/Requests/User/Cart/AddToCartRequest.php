<?php

namespace App\Http\Requests\User\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => 'required|integer|exists:product_variants,id',
            'store_id' => 'required|integer|exists:stores,id',
            'quantity' => 'sometimes|integer|min:1|max:999',
            'replace_quantity' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => __('validation.product_id_required'),
            'product_id.exists' => __('validation.product_id_exists'),
            'product_variant_id.required' => __('validation.product_variant_id_required'),
            'product_variant_id.exists' => __('validation.product_variant_id_exists'),
            'store_id.required' => __('validation.store_id_required'),
            'store_id.exists' => __('validation.store_id_exists'),
            'quantity.integer' => __('validation.quantity_integer'),
            'quantity.min' => __('validation.quantity_min'),
            'quantity.max' => __('validation.quantity_max')
        ];
    }
}
