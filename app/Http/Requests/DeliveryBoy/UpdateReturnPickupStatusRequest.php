<?php

namespace App\Http\Requests\DeliveryBoy;

use App\Enums\Order\OrderItemReturnPickupStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReturnPickupStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:' . OrderItemReturnPickupStatusEnum::PICKED_UP() . ',' . OrderItemReturnPickupStatusEnum::DELIVERED_TO_SELLER()],
            'otp' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => __('validation.status_required'),
            'status.in' => __('validation.status_in'),
            'otp.required' => __('validation.otp_required'),
            'otp.digits' => __('validation.otp_min'),
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'pickup status',
            'otp' => 'verification code',
        ];
    }
}
