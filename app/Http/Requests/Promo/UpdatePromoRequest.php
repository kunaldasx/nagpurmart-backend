<?php

namespace App\Http\Requests\Promo;

use App\Enums\PromoDiscountTypeEnum;
use App\Enums\PromoModeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdatePromoRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $promoId = $this->route('promo') ?? $this->route('id');

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('promo', 'code')->ignore($promoId)
            ],
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'discount_type' => ['required', new Enum(PromoDiscountTypeEnum::class)],
            'discount_amount' => 'required_if:discount_type,' . PromoDiscountTypeEnum::PERCENTAGE() . ',' . PromoDiscountTypeEnum::FIXED() . '|min:0',
            'promo_mode' => ['nullable', new Enum(PromoModeEnum::class)],
//            'individual_use' => 'nullable|integer|in:0,1',
            'max_total_usage' => 'nullable|integer|min:1',
            'max_usage_per_user' => 'nullable|integer|min:1',
            'min_order_total' => 'nullable|numeric|min:0',
            'max_discount_value' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'code.required' => __('messages.promo_code_required'),
            'code.unique' => __('messages.promo_code_unique'),
            'start_date.required' => __('messages.start_date_required'),
            'end_date.required' => __('messages.end_date_required'),
            'end_date.after' => __('messages.end_date_after'),
            'discount_type.required' => __('messages.discount_type_required'),
            'discount_type.in' => __('messages.discount_type_in'),
            'discount_amount.required' => __('messages.discount_amount_required'),
            'discount_amount.min' => __('messages.discount_amount_min'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'usage_count' => $this->usage_count ?? 0,
            'individual_use' => $this->individual_use ?? 0,
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional validation for percentage discount
            if ($this->discount_type === PromoDiscountTypeEnum::PERCENTAGE() && $this->discount_amount > 100) {
                $validator->errors()->add('discount_amount', __('messages.percentage_discount_max'));
            }

            // Validate max_discount_value for percentage discounts
            if ($this->discount_type === PromoDiscountTypeEnum::PERCENTAGE() && empty($this->max_discount_value)) {
                $validator->errors()->add('max_discount_value', __('messages.max_discount_value_required_for_percentage'));
            }

            if ($this->discount_type === PromoDiscountTypeEnum::PERCENTAGE() || $this->discount_type === PromoDiscountTypeEnum::FIXED()) {
                if ($this->min_order_total > 0 && $this->discount_amount > $this->min_order_total) {
                    $validator->errors()->add('discount_amount', __('messages.discount_amount_exceeds_min_order_total'));
                }
            }
        });
    }
}
