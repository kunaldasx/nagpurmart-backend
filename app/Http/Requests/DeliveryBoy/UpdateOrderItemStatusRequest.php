<?php

namespace App\Http\Requests\DeliveryBoy;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemStatusRequest extends FormRequest
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
     * This defines all possible validation rules for Scramble documentation.
     * The actual validation logic may be more complex and is handled in the controller.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|string|in:collected,delivered',
            'otp' => 'sometimes|required|string|min:6|max:6',
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
            'status.required' => __('validation.status_required'),
            'status.in' => __('validation.status_in'),
            'otp.required' => __('validation.otp_required'),
            'otp.min' => __('validation.otp_min'),
            'otp.max' => __('validation.otp_max'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'order status',
            'otp' => 'verification code',
        ];
    }
}
